<?php
// Bỏ qua các cảnh báo không ảnh hưởng đến ứng dụng (Hàm bị lỗi thời Deprecated và các thông báo Notice)
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

// Định nghĩa lớp categories_list kế thừa từ lớp cơ sở controllers (Mô hình MVC)
class categories_list extends controllers {
    // Biến private chứa đối tượng Model thao tác với bảng danh mục sản phẩm
    private $ctlist;
    
    // Hàm khởi tạo (Constructor) - tự động chạy khi class được gọi đến
    function __construct(){
                // Gọi hàm khởi tạo của lớp cha controllers để xử lý các thiết lập mặc định của hệ thống
                parent::__construct();

        // Khởi tạo model "categories_m" để tương tác với cơ sở dữ liệu danh mục
        $this->ctlist = $this->model("categories_m");
    }

    // 1. LUỒNG DÀNH CHO TRÌNH DUYỆT WEB (Load giao diện rỗng)
    // Hàm hiển thị giao diện danh sách danh mục (giao diện tĩnh, dữ liệu sẽ được gọi bằng Ajax/JS sau)
    function Get_data(){
        // Gọi đến View 'Master' chính và truyền trang nội dung là 'categories_list_v'
        $this->view('Master',[
            'Page' => 'categories_list_v'
        ]);
    }

    // Thiết lập Header cho API
    // Hàm cấu hình CORS và định dạng trả về là chuỗi dữ liệu JSON
    private function setApiHeader() {
        header('Access-Control-Allow-Origin: *'); // Cho phép mọi domain khác gọi tới API này
        header('Content-Type: application/json; charset=utf-8'); // Định dạng nội dung là JSON mã hóa UTF-8
    }

    // 2. LUỒNG API DÀNH CHO JAVASCRIPT (Lấy dữ liệu + Tìm kiếm)
    function api_get_data() {
        $this->setApiHeader(); // Cài đặt Header JSON cho API
        
        // Lấy từ khóa tìm kiếm 'q' trên URL thông qua phương thức GET, cắt bỏ khoảng trắng thừa
        $search = isset($_GET['q']) ? trim($_GET['q']) : '';
        
        // Nếu có từ khóa thì tìm kiếm, không thì lấy tất cả
        if ($search !== '') {
            // Gọi Model thực hiện câu lệnh tìm kiếm danh mục theo từ khóa
            $result = $this->ctlist->categories_select($search);
        } else {
            // Gọi Model lấy ra toàn bộ danh mục hiện có trong cơ sở dữ liệu
            $result = $this->ctlist->categories_selectAll();
        }

        $data = []; // Khởi tạo mảng rỗng để lưu trữ các dòng danh mục
        // Kiểm tra tập dữ liệu trả về hợp lệ và có số dòng lớn hơn 0
        if ($result && mysqli_num_rows($result) > 0) {
            // Duyệt qua từng bản ghi trả về từ MySQL
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row; // Đẩy dữ liệu dòng hiện tại vào mảng danh sách
            }
        }

        // Chuyển mảng dữ liệu thành chuỗi JSON và gửi phản hồi thành công về Client
        echo json_encode(['success' => true, 'data' => $data]);
        exit; // Ngắt tiến trình
    }

    // API: Thêm Danh Mục
    function add(){
        $this->setApiHeader(); // Cài đặt Header JSON cho API
        // Ràng buộc phương thức truyền dữ liệu bắt buộc phải là POST
        if($_SERVER['REQUEST_METHOD'] !== 'POST'){
            echo json_encode(['success' => false, 'message' => 'Lỗi phương thức']); exit;
        }

        // Lấy thông tin tên danh mục và slug từ mảng $_POST, sử dụng toán tử ?? để tránh lỗi nếu biến không tồn tại
        $name = $_POST['name'] ?? '';
        $slug = $_POST['slug'] ?? '';

        // Kiểm tra điều kiện bắt buộc: Tên danh mục không được để trống
        if(empty($name)){
            echo json_encode(['success' => false, 'message' => 'Tên danh mục không được để trống']); exit;
        }

        $thumbnail = ''; // Khởi tạo chuỗi rỗng lưu tên file ảnh đại diện
        // Kiểm tra xem phía Client có đăng tải file hình ảnh lên và không gặp lỗi tải lên
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            // Xác định đường dẫn thư mục tuyệt đối trên máy chủ để lưu ảnh danh mục
            $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/web_qlsp/Public/Picture/categories/";
            // Nếu thư mục lưu ảnh chưa tồn tại thì tự động tạo mới với quyền đọc ghi 0777
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            // Tạo tên file duy nhất bằng cách nối mốc thời gian timestamp với tên gốc của file ảnh
            $file_name = time() . '_' . basename($_FILES["image"]["name"]);
            // Thực hiện di chuyển file ảnh tạm thời vào thư mục lưu trữ chính thức
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $file_name)) {
                $thumbnail = $file_name; // Lưu lại tên file mới nếu di chuyển thành công
            }
        }

        // Gọi Model thực thi câu lệnh INSERT chèn dữ liệu danh mục mới vào DB
        $kq = $this->ctlist->categories_insert($name, $slug, $thumbnail);
        // Trả kết quả JSON về cho JavaScript xử lý hiển thị thông báo
        echo json_encode(['success' => $kq, 'message' => $kq ? 'Thêm thành công' : 'Lỗi thêm dữ liệu']);
        exit; // Ngắt tiến trình
    }

    // API: Sửa Danh Mục
    function update(){
        $this->setApiHeader(); // Cài đặt Header JSON cho API
        // Ràng buộc phương thức truyền dữ liệu chỉnh sửa phải là POST
        if($_SERVER['REQUEST_METHOD'] !== 'POST'){
            echo json_encode(['success' => false, 'message' => 'Lỗi phương thức']); exit;
        }

        // Tiếp nhận các thông tin cần sửa đổi từ Form dữ liệu gửi lên
        $id = $_POST['id'];
        $name = $_POST['name'];
        $slug = $_POST['slug'];
        $old_image = $_POST['old_image'] ?? ''; // Tên ảnh cũ hiện tại của danh mục

        // Xác thực dữ liệu đầu vào: Tên danh mục sửa đổi không được rỗng
        if(empty($name)){
            echo json_encode(['success' => false, 'message' => 'Tên danh mục không được để trống']); exit;
        }

        $thumbnail = $old_image; // Mặc định giữ lại ảnh cũ nếu người dùng không chọn tải ảnh mới
        // Kiểm tra xem người dùng có chọn tệp hình ảnh mới hay không
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/web_qlsp/Public/Picture/categories/";
            $file_name = time() . '_' . basename($_FILES["image"]["name"]);
            // Di chuyển file ảnh mới tải lên vào thư mục lưu trữ hình ảnh danh mục
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $file_name)) {
                $thumbnail = $file_name; // Cập nhật biến tên ảnh thành tên tệp mới
                // Xử lý dọn dẹp bộ nhớ: Xóa tệp ảnh cũ khỏi server nếu tệp cũ tồn tại để tránh rác dữ liệu
                if(!empty($old_image) && file_exists($target_dir . $old_image)){
                    unlink($target_dir . $old_image);
                }
            }
        }

        // Gọi Model thực hiện câu lệnh UPDATE cập nhật thông tin danh mục theo ID
        $kq = $this->ctlist->categories_update($id, $name, $slug, $thumbnail);
        // Trả kết quả JSON thông báo trạng thái cập nhật thành công hay thất bại
        echo json_encode(['success' => $kq, 'message' => $kq ? 'Cập nhật thành công' : 'Lỗi cập nhật']);
        exit; // Ngắt tiến trình
    }

    // API: Xóa Danh Mục
    function delete($id){
        $this->setApiHeader(); // Cài đặt Header JSON cho API
        // Gọi Model thực thi xóa bản ghi danh mục trong database theo ID truyền vào
        $kq = $this->ctlist->categories_delete($id);
        // Trả phản hồi trạng thái xóa về cho phía Client giao diện
        echo json_encode(['success' => $kq, 'message' => $kq ? 'Đã xóa thành công' : 'Lỗi khi xóa']);
        exit; // Ngắt tiến trình
    }

    // API: Xuất Excel (Hàm này không trả JSON, mà tải file về)
    function export() {
        $objExcel = new PHPExcel(); // Khởi tạo một đối tượng thư viện PHPExcel mới
        $objExcel->setActiveSheetIndex(0); // Thiết lập làm việc với bảng tính (sheet) đầu tiên
        $sheet = $objExcel->getActiveSheet()->setTitle('Danh_Muc_San_Pham'); // Đặt tên tiêu đề cho sheet

        $rowCount = 1; // Biến theo dõi dòng hiện tại trong file Excel, bắt đầu từ dòng 1 (Tiêu đề cột)
        $sheet->setCellValue('A'.$rowCount, 'ID'); // Điền tiêu đề cột A
        $sheet->setCellValue('B'.$rowCount, 'TÊN DANH MỤC'); // Điền tiêu đề cột B
        $sheet->setCellValue('C'.$rowCount, 'SLUG'); // Điền tiêu đề cột C
        $sheet->setCellValue('D'.$rowCount, 'HÌNH ẢNH'); // Điền tiêu đề cột D
        $sheet->getStyle('A1:D1')->getFont()->setBold(true); // Định dạng in đậm cho toàn bộ dòng tiêu đề 1

        // Lấy từ khóa tìm kiếm hiện tại từ bộ lọc (nếu có) trên thanh tìm kiếm của giao diện
        $search = $_GET['q'] ?? ''; 
        // Phục vụ dữ liệu theo bộ lọc xuất: Nếu đang tìm kiếm thì xuất danh sách tìm kiếm, ngược lại xuất toàn bộ
        if ($search !== '') {
            $data = $this->ctlist->categories_select($search);
        } else {
            $data = $this->ctlist->categories_selectAll();
        }

        // Kiểm tra tập dữ liệu danh mục lấy từ cơ sở dữ liệu có tồn tại hay không
        if($data){
            // Vòng lặp duyệt qua từng bản ghi dữ liệu danh mục thu được
            while($row = mysqli_fetch_array($data)){
                $rowCount++; // Tăng chỉ số dòng lên để ghi thông tin bản ghi tiếp theo
                $sheet->setCellValue('A'.$rowCount, $row['id']); // Điền ID danh mục vào cột A
                $sheet->setCellValue('B'.$rowCount, $row['name']); // Điền tên danh mục vào cột B
                $sheet->setCellValue('C'.$rowCount, $row['slug']); // Điền đường dẫn thân thiện vào cột C
                $sheet->setCellValue('D'.$rowCount, $row['thumbnail']); // Điền tên file ảnh vào cột D
            }
        }

        // Vòng lặp duyệt qua các cột từ A đến D để cấu hình tự động giãn độ rộng cột dựa theo độ dài văn bản
        foreach(range('A','D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Đặt tên file xuất ra đi kèm với thời gian timestamp tránh trùng lặp tệp tải về
        $filename = "Danh_Muc_" . time() . ".xlsx";
        // Nếu có dữ liệu xuất đệm dư thừa trước đó trong bộ nhớ cache thì tiến hành xóa sạch
        if (ob_get_length()) ob_end_clean();

        // Cấu hình các thuộc tính Header của HTTP response ép trình duyệt tải file Excel về máy thay vì hiển thị văn bản
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        // Tạo đối tượng Writer theo chuẩn định dạng Excel 2007 (.xlsx)
        $objWriter = PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');
        $objWriter->save('php://output'); // Đẩy luồng file dữ liệu ra đầu ra hệ thống để trình duyệt tải xuống
        exit; // Ngắt tiến trình để ngăn mã HTML hay ký tự lạ lọt vào file Excel gây lỗi file
    }

    // API: Import Excel
    public function importExcelCat() {
        $this->setApiHeader(); // Cài đặt Header JSON cho API
        
        // Kiểm tra xem phía client có chọn và đẩy tệp tin Excel lên thông qua input tên 'import_file' hay chưa
        if (!isset($_FILES['import_file']) || empty($_FILES['import_file']['tmp_name'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng chọn file Excel']); exit;
        }

        $file = $_FILES['import_file']['tmp_name']; // Đường dẫn tệp tin tạm của file Excel trên máy chủ

        try {
            // Nhúng file thư viện IOFactory để đọc định dạng dữ liệu Excel
            require_once __DIR__ . '/../../Public/Classes/PHPExcel/IOFactory.php';
            $objReader = PHPExcel_IOFactory::createReaderForFile($file); // Tự động nhận diện loại cấu trúc file Excel
            $objExcel  = $objReader->load($file); // Nạp file Excel vào đối tượng xử lý dữ liệu
            $sheet     = $objExcel->getSheet(0); // Lấy bảng tính đầu tiên (sheet index 0)
            $sheetData = $sheet->toArray(null, true, true, true); // Chuyển đổi toàn bộ dữ liệu sheet thành mảng PHP

            // Xác định thư mục đích để chuẩn bị lưu trữ hình ảnh danh mục nếu file Excel chứa ảnh dạng liên kết URL
            $uploadFolder = $_SERVER['DOCUMENT_ROOT'] . "/web_qlsp/Public/Picture/categories/";
            if (!is_dir($uploadFolder)) mkdir($uploadFolder, 0777, true);

            $countSuccess = 0; // Biến đếm tổng số bản ghi danh mục được thêm mới thành công vào DB

            // Vòng lặp đọc dữ liệu hàng từ hàng số 2 (bỏ qua hàng số 1 chứa tiêu đề cột ID, TÊN,...)
            for ($i = 2; $i <= count($sheetData); $i++) {
                $name = trim($sheetData[$i]["A"] ?? ''); // Đọc dữ liệu cột A chứa tên danh mục
                if (empty($name)) continue; // Nếu dòng này không có tên danh mục, bỏ qua để đọc dòng kế tiếp

                $slug = $this->create_slug($name); // Tự động tạo slug từ chuỗi tên danh mục vừa lấy
                $rawImg = trim($sheetData[$i]["B"] ?? ''); // Đọc dữ liệu cột B chứa đường dẫn ảnh hoặc tên file ảnh
                $thumbnail = ''; // Biến lưu trữ tên file ảnh cuối cùng ghi nhận vào DB

                if (!empty($rawImg)) {
                    // Kiểm tra nếu dữ liệu ảnh ở file Excel là một đường dẫn URL hợp lệ từ mạng internet
                    if (filter_var($rawImg, FILTER_VALIDATE_URL)) {
                        // Gọi hàm bổ trợ tải ảnh từ internet về lưu vào thư mục trên máy chủ cục bộ
                        $savedPath = $this->download_image_from_url($rawImg, $uploadFolder);
                        if ($savedPath) {
                            $thumbnail = basename($savedPath); // Chỉ lấy phần tên file lưu vào DB sau khi tải về thành công
                        }
                    } else {
                        $thumbnail = $rawImg; // Nếu chỉ là chuỗi văn bản thông thường, gán trực tiếp làm tên file ảnh đại diện
                    }
                }

                // Gọi Model thực thi chèn bản ghi danh mục mới đọc được từ file Excel vào cơ sở dữ liệu
                $insertResult = $this->ctlist->categories_insert($name, $slug, $thumbnail);
                if ($insertResult) $countSuccess++; // Tăng biến số lượng thành công lên 1 đơn vị
            }

            // Trả về chuỗi kết quả JSON thông báo số dòng danh mục đã nhập thành công
            echo json_encode(['success' => true, 'message' => "Đã nhập thành công $countSuccess danh mục!"]);
            exit;

        } catch (Exception $e) {
            // Xử lý bắt lỗi ngoại lệ nếu file Excel lỗi định dạng cấu trúc hoặc lỗi truy vấn hệ thống
            echo json_encode(['success' => false, 'message' => "Lỗi hệ thống: " . $e->getMessage()]);
            exit;
        }
    }

    // Utility Functions
    // Hàm bổ trợ: Chuyển đổi chuỗi tiếng Việt có dấu thành chuỗi không dấu, cách nhau bởi dấu gạch ngang (Tạo Slug URL)
    private function create_slug($string) {
        $search = [
            '#(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)#', '#(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)#',
            '#(ì|í|ị|ỉ|ĩ)#', '#(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)#',
            '#(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)#', '#(ỳ|ý|ỵ|ỷ|ỹ)#', '#(đ)#',
            '#(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)#', '#(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)#',
            '#(Ì|Í|Ị|Ỉ|Ĩ)#', '#(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)#',
            '#(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)#', '#(Ỳ|Ý|Ỵ|Ỷ|Ỹ)#', '#(Đ)#', "/[^a-zA-Z0-9\-\_]/"
        ];
        $replace = [
            'a', 'e', 'i', 'o', 'u', 'y', 'd', 'A', 'E', 'I', 'O', 'U', 'Y', 'D', '-'
        ];
        $string = preg_replace($search, $replace, $string); // Thay thế ký tự có dấu thành không dấu theo mảng cấu hình
        $string = preg_replace('/(-)+/', '-', $string); // Loại bỏ các ký tự dấu gạch ngang trùng lặp liên tiếp nhau (--- thành -)
        return strtolower($string); // Chuyển đổi toàn bộ chuỗi ký tự về dạng chữ viết thường và trả về
    }

    // Hàm bổ trợ: Tải một hình ảnh từ liên kết URL trên mạng internet về lưu trữ trong thư mục cục bộ của ứng dụng
    private function download_image_from_url($url, $saveFolder) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) return ''; // Trả về chuỗi rỗng nếu định dạng URL truyền vào không hợp lệ
        try {
            $imageContent = @file_get_contents($url); // Đọc nội dung file ảnh dưới dạng nhị phân, ẩn lỗi bằng dấu @ nếu không kết nối được
            if ($imageContent === false) return ''; // Nếu không lấy được nội dung dữ liệu ảnh, trả về rỗng

            $pathInfo = pathinfo(parse_url($url, PHP_URL_PATH)); // Phân tích đường dẫn URL để bóc tách thông tin tên file
            $ext = isset($pathInfo['extension']) ? strtolower($pathInfo['extension']) : 'jpg'; // Lấy định dạng đuôi file mở rộng, mặc định là jpg
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) $ext = 'jpg'; // Kiểm tra tính hợp lệ của đuôi ảnh, nếu lạ thì ép về định dạng chuẩn jpg

            // Tạo tên file ảnh mới duy nhất kết hợp tiền tố cat_, mốc thời gian và một số ngẫu nhiên từ 1000 đến 9999
            $newFileName = 'cat_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $savePath = $saveFolder . $newFileName; // Tổ hợp đường dẫn lưu tệp hoàn chỉnh trên đĩa cứng máy chủ
            file_put_contents($savePath, $imageContent); // Ghi nội dung nhị phân vào file đĩa cứng để khởi tạo ảnh thành công
            
            return $savePath; // Trả về đường dẫn tệp tin tuyệt đối sau khi lưu thành công tệp ảnh
        } catch (Exception $e) {
            return ''; // Trả về chuỗi rỗng nếu xảy ra lỗi trong tiến trình tải ảnh
        }
    }
}
?>