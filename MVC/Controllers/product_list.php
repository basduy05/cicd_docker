<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

class product_list extends controllers
{
    private $pdlist;

    // Hàm khởi tạo, gán model sản phẩm cho controller.
    public function __construct()
    {
        parent::__construct();
        $this->pdlist = $this->model("product_m");
    }



    // Thiết lập header cho API trả về dữ liệu JSON.
    private function setApiHeader()
    {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json; charset=utf-8');
    }

    // Tạo slug thân thiện cho tên sản phẩm để dùng trong URL.
    private function create_slug($string)
    {
        $search = ['#(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)#', '#(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)#', '#(ì|í|ị|ỉ|ĩ)#', '#(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)#', '#(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)#', '#(ỳ|ý|ỵ|ỷ|ỹ)#', '#(đ)#', '#(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)#', '#(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)#', '#(Ì|Í|Ị|Ỉ|Ĩ)#', '#(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)#', '#(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)#', '#(Ỳ|Ý|Ỵ|Ỷ|Ỹ)#', '#(Đ)#', "/[^a-zA-Z0-9\-\_]/"];
        $replace = ['a', 'e', 'i', 'o', 'u', 'y', 'd', 'A', 'E', 'I', 'O', 'U', 'Y', 'D', '-'];
        $string = preg_replace($search, $replace, $string);
        return strtolower(preg_replace('/(-)+/', '-', $string));
    }

    // Tải ảnh từ URL bên ngoài và lưu vào thư mục hình ảnh của hệ thống.
    private function download_image_from_url($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) return '';
        try {
            $imageContent = @file_get_contents($url);
            if ($imageContent === false) return '';

            $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) $ext = 'jpg';

            $newFileName = time() . '_' . rand(1000, 9999) . '.' . $ext;
            $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/web_qlsp/Public/Picture/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            
            file_put_contents($target_dir . $newFileName, $imageContent);
            return $newFileName;
        } catch (Exception $e) { return ''; }
    }


    // Hiển thị giao diện danh sách sản phẩm.
    public function Get_data()
    {
        $this->view('Master', ['Page' => 'product_list_v']);
    }

    // API: Lấy danh sách sản phẩm, có thể hỗ trợ tìm kiếm theo từ khóa.
    public function api_get_data()
    {
        $this->setApiHeader();
        $search = isset($_GET['q']) ? trim($_GET['q']) : '';

        $products_result = $this->pdlist->products_select('', $search);
        $data = [];

        if ($products_result && mysqli_num_rows($products_result) > 0) {
            while ($product = mysqli_fetch_assoc($products_result)) {
                $product_id = $product['id'];
                
                $variants = [];
                $variants_result = $this->pdlist->get_variants_by_product($product_id);
                if ($variants_result && mysqli_num_rows($variants_result) > 0) {
                    while ($variant = mysqli_fetch_assoc($variants_result)) {
                        $variants[] = $variant;
                    }
                }
                
                $product['variants'] = $variants;
                $data[] = $product;
            }
        }
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    // API: Xóa một sản phẩm theo ID.
    public function api_delete($id)
    {
        $this->setApiHeader();
        $kq = $this->pdlist->products_delete($id);
        echo json_encode(['success' => $kq, 'message' => $kq ? 'Đã xóa sản phẩm thành công' : 'Lỗi khi xóa']);
        exit;
    }

    // ==========================================
    // PHẦN 2: QUẢN LÝ CHỈNH SỬA SẢN PHẨM & BIẾN THỂ
    // ==========================================

    // Load giao diện chỉnh sửa sản phẩm theo ID.
    public function sua($id)
    {
        $this->view('Master', [
            'Page' => 'product_edit_v',
            'product_id' => $id
        ]);
    }

    // API: Lấy toàn bộ dữ liệu của một sản phẩm, bao gồm biến thể và danh mục.
    public function api_get_product_detail($id)
    {
        $this->setApiHeader();
        
        $product_result = $this->pdlist->products_select($id, '');
        if (!$product_result || mysqli_num_rows($product_result) == 0) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm']); exit;
        }
        $product = mysqli_fetch_assoc($product_result);

        $variants = [];
        $variants_result = $this->pdlist->get_variants_by_product($id);
        if ($variants_result && mysqli_num_rows($variants_result) > 0) {
            while ($variant = mysqli_fetch_assoc($variants_result)) {
                $v_id = $variant['id'];
                $images = [];
                $images_result = $this->pdlist->get_images_by_variant($v_id);
                if ($images_result && mysqli_num_rows($images_result) > 0) {
                    while ($img = mysqli_fetch_assoc($images_result)) {
                        $images[] = $img;
                    }
                }
                $variant['images'] = $images;
                $variants[] = $variant;
            }
        }

        $categories = [];
        $cat_res = $this->pdlist->categories_selectAll();
        while ($c = mysqli_fetch_assoc($cat_res)) { $categories[] = $c; }

        $collections = [];
        $col_res = $this->pdlist->collections_selectAll();
        while ($c = mysqli_fetch_assoc($col_res)) { $collections[] = $c; }

        echo json_encode([
            'success' => true,
            'product' => $product,
            'variants' => $variants,
            'categories' => $categories,
            'collections' => $collections
        ]);
        exit;
    }

    // API: Cập nhật thông tin cơ bản của sản phẩm.
    public function api_update_product()
    {
        $this->setApiHeader();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Lỗi phương thức']); exit;
        }

        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $slug = $_POST['slug'] ?? '';
        $base_price = $_POST['base_price'] ?? 0;
        $category_id = $_POST['category_id'] ?? '';
        $collection_id = $_POST['collection_id'] ?? '';
        $description = $_POST['description'] ?? '';
        $gender = $_POST['gender'] ?? 'Nam';
        $is_sale = isset($_POST['is_sale']) ? 1 : 0;

        if (empty($name) || empty($base_price)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đủ Tên và Giá bán']); exit;
        }

        $thumbnail = '';
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
            $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/web_qlsp/Public/Picture/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $file_name = time() . '_' . basename($_FILES["thumbnail"]["name"]);
            if (move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $target_dir . $file_name)) {
                $thumbnail = $file_name;
            }
        } else {
            $product_data = $this->pdlist->products_select($id, '');
            if ($product_data && mysqli_num_rows($product_data) > 0) {
                $old_product = mysqli_fetch_assoc($product_data);
                $thumbnail = $old_product['thumbnail'];
            }
        }

        $kq = $this->pdlist->products_update($id, $name, $slug, $base_price, $category_id, $collection_id, $description, $thumbnail, $gender, $is_sale);
        echo json_encode(['success' => $kq, 'message' => $kq ? 'Đã lưu thông tin sản phẩm' : 'Lỗi cập nhật dữ liệu']);
        exit;
    }

    // API: Cập nhật thông tin biến thể của sản phẩm.
    public function api_update_variant()
    {
        $this->setApiHeader();
        $variant_id = $_POST['variant_id'] ?? '';
        $color = $_POST['color'] ?? '';
        $size = $_POST['size'] ?? '';
        $input_price = $_POST['input_price'] ?? 0;
        $stock = $_POST['stock'] ?? 0;

        $result = $this->pdlist->variant_update($variant_id, $color, $size, $input_price, $stock);
        echo json_encode(['success' => $result, 'message' => $result ? 'Đã cập nhật Biến thể' : 'Lỗi cập nhật']);
        exit;
    }

    // API: Tải lên và lưu nhiều ảnh cho một biến thể.
    public function api_upload_variant_images()
    {
        $this->setApiHeader();
        if (isset($_FILES['detail_images'])) {
            $variant_id = $_POST['variant_id'];
            $files = $_FILES['detail_images'];
            $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/web_qlsp/Public/Picture/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $upload_count = 0;

            for ($i = 0; $i < count($files['name']); $i++) {
                if (!empty($files['name'][$i]) && $files['error'][$i] == 0) {
                    $original_name = iconv('UTF-8', 'UTF-8//IGNORE', $files["name"][$i]);
                    $file_name = time() . '_' . $i . '_' . $original_name;
                    if (move_uploaded_file($files["tmp_name"][$i], $target_dir . $file_name)) {
                        $this->pdlist->product_images_insert($variant_id, $file_name, 0);
                        $upload_count++;
                    }
                }
            }
            echo json_encode(['success' => true, 'message' => "Đã tải lên $upload_count ảnh mới"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy file ảnh']);
        }
        exit;
    }

    // API: Xóa một ảnh của biến thể khỏi hệ thống.
    public function api_delete_variant_image($image_id)
    {
        $this->setApiHeader();
        $result = $this->pdlist->get_images_by_id($image_id);
        if ($result && mysqli_num_rows($result) > 0) {
            $image = mysqli_fetch_assoc($result);
            $file_path = $_SERVER['DOCUMENT_ROOT'] . "/web_qlsp/Public/Picture/" . $image['image_url'];
            if (file_exists($file_path)) unlink($file_path);
            
            $kq = $this->pdlist->product_image_delete($image_id);
            echo json_encode(['success' => $kq, 'message' => 'Đã xóa ảnh']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy ảnh']);
        }
        exit;
    }

    // API: Xóa toàn bộ biến thể của sản phẩm.
    public function api_delete_variant($variant_id)
    {
        $this->setApiHeader();
        $kq = $this->pdlist->variant_delete($variant_id);
        echo json_encode(['success' => $kq, 'message' => $kq ? 'Đã xóa biến thể' : 'Lỗi khi xóa']);
        exit;
    }

    // ==========================================
    // PHẦN 3: NHẬP & XUẤT EXCEL
    // ==========================================

    // Xuất dữ liệu sản phẩm ra file Excel.
    public function export()
    {
        if (!class_exists('PHPExcel')) require_once "./MVC/Bridge.php";

        $objExcel = new PHPExcel();
        $objExcel->setActiveSheetIndex(0);
        $sheet = $objExcel->getActiveSheet()->setTitle('DanhSachSanPham');

        $rowCount = 1;
        $columns  = [
            'A' => 'ID Sản phẩm', 'B' => 'Tên Sản Phẩm', 'C' => 'ID Danh Mục', 'D' => 'ID Bộ Sưu Tập',
            'E' => 'Giá Bán', 'F' => 'Giá Nhập', 'G' => 'Màu', 'H' => 'Size',
            'I' => 'Kho', 'J' => 'Giới Tính', 'K' => 'Ngày Tạo',
        ];

        foreach ($columns as $col => $title) {
            $sheet->setCellValue($col . $rowCount, $title);
            $sheet->getStyle($col . $rowCount)->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER],
                'fill' => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => 'E0E0E0']],
            ]);
        }

        $keyword = $_GET['q'] ?? '';
        $data = $this->pdlist->get_products_export_full($keyword);

        if ($data) {
            while ($row = mysqli_fetch_array($data)) {
                $rowCount++;
                $sheet->setCellValue('A' . $rowCount, $row['id']);
                $sheet->setCellValue('B' . $rowCount, $row['name']);
                $sheet->setCellValue('C' . $rowCount, $row['category_id']);
                $sheet->setCellValue('D' . $rowCount, $row['collection_id']);
                $sheet->setCellValue('E' . $rowCount, $row['base_price']);
                $sheet->setCellValue('F' . $rowCount, $row['input_price'] ?? '');
                $sheet->setCellValue('G' . $rowCount, $row['color'] ?? '');
                $sheet->setCellValue('H' . $rowCount, $row['size'] ?? '');
                $sheet->setCellValue('I' . $rowCount, $row['stock'] ?? '');
                $sheet->setCellValue('J' . $rowCount, $row['gender']);
                $sheet->setCellValue('K' . $rowCount, $row['created_at']);
            }
        }

        foreach (array_keys($columns) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = "Export_Products_" . date('Y_m_d_H_i_s') . ".xlsx";
        if (ob_get_contents()) ob_end_clean();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

    // API: Nhập dữ liệu sản phẩm hoặc biến thể từ file Excel.
    public function api_import_excel()
    {
        $this->setApiHeader();

        if (!isset($_FILES['txtfile']) || empty($_FILES['txtfile']['tmp_name'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng chọn file!']); exit;
        }

        $file = $_FILES['txtfile']['tmp_name'];
        $importType = $_POST['importType'] ?? 'product';

        try {
            require_once __DIR__ . '/../../Public/Classes/PHPExcel/IOFactory.php';
            $objReader = PHPExcel_IOFactory::createReaderForFile($file);
            $objExcel  = $objReader->load($file);
            $sheetData = $objExcel->getSheet(0)->toArray(null, true, true, true);

            $countNewProduct = 0;
            $countNewVariant = 0;

            if ($importType === 'product') {
                for ($i = 2; $i <= count($sheetData); $i++) {
                    $name = trim($sheetData[$i]["A"] ?? '');
                    if (empty($name)) continue;

                    $cat_id = (int)($sheetData[$i]["B"] ?? 0);
                    $base_price = (float)($sheetData[$i]["C"] ?? 0);
                    $description = $sheetData[$i]["D"] ?? '';
                    $gender = trim($sheetData[$i]["E"] ?? 'Nam');
                    $is_sale = (int)($sheetData[$i]["F"] ?? 0);
                    $collection_id = (int)($sheetData[$i]["G"] ?? 0);
                    $imgInput = trim($sheetData[$i]["H"] ?? '');

                    $listImages = [];
                    $thumbnail = '';
                    if (!empty($imgInput)) {
                        $rawArr = explode(',', $imgInput);
                        foreach ($rawArr as $imgName) {
                            $imgName = trim($imgName);
                            if (!empty($imgName)) {
                                if (filter_var($imgName, FILTER_VALIDATE_URL)) {
                                    $savedFileName = $this->download_image_from_url($imgName);
                                    if ($savedFileName) $listImages[] = $savedFileName;
                                } else {
                                    $listImages[] = $imgName;
                                }
                            }
                        }
                        if (count($listImages) > 0) $thumbnail = $listImages[0];
                    }

                    if ($this->pdlist->get_id_by_name($name) == 0) {
                        $slug = $this->create_slug($name);
                        $cost_price = $base_price * 0.7; 
                        
                        $result_array = $this->pdlist->products_insert(
                            $name, $slug, $cat_id, $collection_id, $description, $thumbnail,
                            $cost_price, $base_price, '', '', 0, $is_sale, $gender
                        );

                        if ($result_array) {
                            $variant_id = $result_array['variant_id'];
                            $countNewProduct++;
                            if (count($listImages) > 0 && $variant_id) {
                                foreach ($listImages as $key => $imgUrl) {
                                    $this->pdlist->product_images_insert($variant_id, $imgUrl, ($key == 0 ? 1 : 0));
                                }
                            }
                        }
                    }
                }
                echo json_encode(['success' => true, 'message' => "Đã thêm $countNewProduct sản phẩm mới."]);

            } else if ($importType === 'variant') {
                for ($i = 2; $i <= count($sheetData); $i++) {
                    $product_name = trim($sheetData[$i]["A"] ?? '');
                    if (empty($product_name)) continue;

                    $color = trim($sheetData[$i]["B"] ?? '');
                    $size = trim($sheetData[$i]["C"] ?? '');
                    $stock = (int)($sheetData[$i]["D"] ?? 0);
                    $input_price = (float)($sheetData[$i]["E"] ?? 0);
                    $imgInput = trim($sheetData[$i]["F"] ?? '');

                    $product_id = $this->pdlist->get_id_by_name($product_name);
                    if ($product_id == 0) continue; 

                    $listImages = [];
                    if (!empty($imgInput)) {
                        $rawArr = explode(',', $imgInput);
                        foreach ($rawArr as $imgName) {
                            $imgName = trim($imgName);
                            if (!empty($imgName)) {
                                if (filter_var($imgName, FILTER_VALIDATE_URL)) {
                                    $savedFileName = $this->download_image_from_url($imgName);
                                    if ($savedFileName) $listImages[] = $savedFileName;
                                } else {
                                    $listImages[] = $imgName;
                                }
                            }
                        }
                    }

                    if (!$this->pdlist->variant_exists($product_id, $color, $size)) {
                        $variant_id = $this->pdlist->variant_insert($product_id, $color, $size, $input_price, $stock);
                        if ($variant_id) {
                            $countNewVariant++;
                            if (count($listImages) > 0) {
                                foreach ($listImages as $key => $imgUrl) {
                                    $this->pdlist->product_images_insert($variant_id, $imgUrl, ($key == 0 ? 1 : 0));
                                }
                            }
                        }
                    }
                }
                echo json_encode(['success' => true, 'message' => "Đã thêm $countNewVariant biến thể mới."]);
            }

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => "Lỗi: " . $e->getMessage()]);
        }
        exit;
    }

    // Tải file mẫu Excel để người dùng nhập dữ liệu sản phẩm.
    public function downloadProductTemplate()
    {
        if (!class_exists('PHPExcel')) require_once "./MVC/Bridge.php";
        $objExcel = new PHPExcel(); $sheet = $objExcel->getActiveSheet()->setTitle('Products');
        $headerStyle = ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => '4472C4']]];
        $headers = ['A' => 'Tên Sản Phẩm', 'B' => 'Mã Danh Mục', 'C' => 'Giá Bán', 'D' => 'Mô Tả', 'E' => 'Giới Tính', 'F' => 'Sale (0/1)', 'G' => 'Mã BST', 'H' => 'Link/Tên Ảnh'];
        foreach ($headers as $col => $title) { $sheet->setCellValue($col.'1', $title); $sheet->getStyle($col.'1')->applyFromArray($headerStyle); }
        $sheet->setCellValue('A2', 'Áo thun'); $sheet->setCellValue('B2', '1'); $sheet->setCellValue('C2', '199000'); $sheet->setCellValue('H2', 'ao1.jpg');
        foreach (range('A', 'H') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Template_SanPham.xlsx"');
        $objWriter = PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007'); $objWriter->save('php://output'); exit;
    }

    // Tải file mẫu Excel để người dùng nhập dữ liệu biến thể.
    public function downloadVariantTemplate()
    {
        if (!class_exists('PHPExcel')) require_once "./MVC/Bridge.php";
        $objExcel = new PHPExcel(); $sheet = $objExcel->getActiveSheet()->setTitle('Variants');
        $headerStyle = ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => '70AD47']]];
        $headers = ['A' => 'Tên Sản Phẩm', 'B' => 'Màu Sắc', 'C' => 'Kích Thước', 'D' => 'Số Lượng', 'E' => 'Giá Nhập', 'F' => 'Link/Tên Ảnh'];
        foreach ($headers as $col => $title) { $sheet->setCellValue($col.'1', $title); $sheet->getStyle($col.'1')->applyFromArray($headerStyle); }
        $sheet->setCellValue('A2', 'Áo thun'); $sheet->setCellValue('B2', 'Đen'); $sheet->setCellValue('C2', 'M'); $sheet->setCellValue('D2', '50'); $sheet->setCellValue('E2', '120000');
        foreach (range('A', 'F') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Template_BienThe.xlsx"');
        $objWriter = PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007'); $objWriter->save('php://output'); exit;
    }
}
?>