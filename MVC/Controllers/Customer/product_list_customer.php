<?php
// Định nghĩa lớp product_list_customer xử lý danh sách sản phẩm phía giao diện khách hàng (Kế thừa từ controllers_customer)
class product_list_customer extends controllers_customer {
    // Khai báo các thuộc tính private để lưu trữ các đối tượng Model tương ứng
    private $prd_list;
    private $menu_categories;
    private $home_model;
    
    // Hàm khởi tạo (Constructor) - Tự động nạp các Model cần thiết khi Controller này được gọi
    function __construct() {
        
        $this->prd_list = $this->model("product_list_m"); // Model xử lý danh sách và bộ lọc sản phẩm
        $this->menu_categories = $this->model('master_customer_m'); // Model lấy danh mục cho thanh menu chính
        $this->home_model = $this->model('home_m'); // Model bổ trợ lấy dữ liệu trang chủ (Flash Sale, biến thể, ảnh)
    }
    
    // 1. CHỈ TẢI GIAO DIỆN CHÍNH CỦA TRANG DANH SÁCH SẢN PHẨM
    function Get_data() {
        $user_info = null;
        // Kiểm tra nếu khách hàng đã đăng nhập thì lấy thông tin tài khoản qua ID lưu trong Session
        if (isset($_SESSION['user_id'])) {
            $user_info = $this->model('profile_m')->user_getById($_SESSION['user_id']);
        }
        
        // Gọi View Master_customer (Giao diện khung) và truyền mảng dữ liệu để đổ vào trang con product_list_v
        $this->view('Master_customer', [
            'Page' => 'product_list_v', // Xác định trang nội dung hiển thị ở vùng trung tâm
            'menu_categories' => $this->menu_categories->categories_selectAll(), // Danh mục trên Header
            'categories' => $this->prd_list->categories_selectAll(), // Danh mục dùng cho bộ lọc cạnh bên
            'user_info' => $user_info // Thông tin người dùng hiện tại
        ]);
    }

    // 2. API TRẢ VỀ JSON: LẤY DANH SÁCH SẢN PHẨM VÀ BIẾN THỂ DỰA THEO CÁC TIÊU CHÍ LỌC (AJAX)
    function api_get_data() {
        // Tắt các cảnh báo nhỏ (Notice, Warning, Deprecated) để tránh lẫn tạp chất vào chuỗi JSON đầu ra
        error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
        header('Access-Control-Allow-Origin: *'); // Cho phép gọi API liên miền (CORS)
        header('Content-Type: application/json; charset=utf-8'); // Thiết lập định dạng dữ liệu trả về là JSON UTF-8

        try {
            // Nhận và làm sạch các tham số lọc truyền lên từ phương thức GET
            $category = isset($_GET['category']) ? trim($_GET['category']) : ''; // Lọc theo danh mục (slug)
            $collection = isset($_GET['collection']) ? trim($_GET['collection']) : ''; // Lọc theo bộ sưu tập
            $price_range = isset($_GET['price']) ? trim($_GET['price']) : ''; // Khoảng giá (Ví dụ: 100000-300000)
            $sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'default'; // Tiêu chí sắp xếp (mới nhất, giá tăng/giảm...)
            $search = isset($_GET['search']) ? trim($_GET['search']) : ''; // Từ khóa tìm kiếm
            $filter = isset($_GET['filter']) ? trim($_GET['filter']) : ''; // Bộ lọc đặc biệt (bestseller, new)
            $sale = isset($_GET['sale']) ? intval($_GET['sale']) : 0; // Trạng thái lọc hàng giảm giá (1: Đang giảm giá)
            
            // Khởi tạo khoảng giá mặc định rộng nhất
            $min_price = 0; $max_price = 999999999;
            if($price_range) {
                $price_parts = explode('-', $price_range); // Tách chuỗi khoảng giá bằng dấu gạch ngang
                if(count($price_parts) == 2) {
                    $min_price = intval($price_parts[0]); // Giá tối thiểu
                    $max_price = intval($price_parts[1]); // Giá tối đa
                }
            }
            
            $products_rs = null; // Biến lưu tập kết quả truy vấn sản phẩm thô từ Database
            
            // --- HỆ THỐNG LOGIC PHÂN LUỒNG BỘ LỌC SẢN PHẨM (RẼ NHÁNH TỐI ƯU) ---
            if($sale === 1) {
                // Xử lý riêng cho luồng hàng Flash Sale: Tìm chương trình Flash Sale đang kích hoạt
                $flashSlug = '';
                $sections = $this->home_model->sections_getActive();
                if($sections) {
                    // Chuẩn hóa dữ liệu trả về từ Model (hỗ trợ cả đối tượng MySQLi Result hoặc Mảng thuần)
                    $sec_array = [];
                    if (is_object($sections)) {
                        while($s = mysqli_fetch_assoc($sections)) $sec_array[] = $s;
                    } elseif (is_array($sections)) {
                        $sec_array = $sections;
                    }
                    // Tìm đúng khu vực cấu hình kiểu 'flash_sale' và đang bật (status = 1) để lấy Slug của Collection đó
                    foreach($sec_array as $sec) {
                        if(strtolower($sec['section_type']) === 'flash_sale' && intval($sec['status']) === 1) {
                            $collectionId = intval($sec['collection_id']);
                            if($collectionId > 0) $flashSlug = $this->home_model->collection_getSlug($collectionId);
                            break;
                        }
                    }
                }
                
                // Nếu tìm thấy chiến dịch Flash Sale, truy vấn sản phẩm thuộc Collection đó kết hợp điều kiện đi kèm
                if($flashSlug) {
                    if($category && $price_range) $products_rs = $this->prd_list->products_selectByCollectionCategoryAndPrice($flashSlug, $category, $min_price, $max_price, $sort);
                    elseif($category) $products_rs = $this->prd_list->products_selectByCollectionAndCategory($flashSlug, $category, $sort);
                    elseif($price_range) $products_rs = $this->prd_list->products_selectByCollectionAndPriceRange($flashSlug, $min_price, $max_price, $sort);
                    else $products_rs = $this->prd_list->products_selectByCollection($flashSlug, $sort);
                }
                // Dự phòng: Nếu không cấu hình Flash Sale cụ thể, tự động lấy toàn bộ sản phẩm
                if(!$products_rs) $products_rs = $this->prd_list->products_selectAll($sort);
            }
            // Luồng bộ lọc sản phẩm bán chạy nhất (Bestseller) kết hợp danh mục và giá
            elseif($filter === 'bestseller' && $category && $price_range) $products_rs = $this->prd_list->products_selectBestsellerByCategoryAndPrice($category, $min_price, $max_price, $sort);
            elseif($filter === 'bestseller' && $category) $products_rs = $this->prd_list->products_selectBestsellerByCategory($category, $sort);
            elseif($filter === 'bestseller' && $price_range) $products_rs = $this->prd_list->products_selectBestsellerByPrice($min_price, $max_price, $sort);
            elseif($filter === 'bestseller') $products_rs = $this->prd_list->products_selectBestseller($sort);
            // Luồng bộ lọc sản phẩm mới về (New Arrival) kết hợp danh mục và giá
            elseif($filter === 'new' && $category && $price_range) $products_rs = $this->prd_list->products_selectNewByCategoryAndPrice($category, $min_price, $max_price, $sort);
            elseif($filter === 'new' && $category) $products_rs = $this->prd_list->products_selectNewByCategory($category, $sort);
            elseif($filter === 'new' && $price_range) $products_rs = $this->prd_list->products_selectNewByPrice($min_price, $max_price, $sort);
            elseif($filter === 'new') $products_rs = $this->prd_list->products_selectNew($sort);
            // Luồng xử lý ô tìm kiếm (Search) kết hợp danh mục và khoảng giá
            elseif($search && $category && $price_range) $products_rs = $this->prd_list->products_searchByCategoryPriceAndKeyword($category, $min_price, $max_price, $search, $sort);
            elseif($search && $category) $products_rs = $this->prd_list->products_searchByCategoryAndKeyword($category, $search, $sort);
            elseif($search && $price_range) $products_rs = $this->prd_list->products_searchByPriceAndKeyword($min_price, $max_price, $search, $sort);
            elseif($search) {
                // Tối ưu hóa tìm kiếm: Nếu nhập từ khóa chung như 'Áo', 'Quần', dùng hàm tối ưu Prefix (Tìm kiếm đầu chuỗi)
                if(in_array($search, ['Áo', 'Quần', 'Ao', 'Quan'])) $products_rs = $this->prd_list->products_searchByKeywordPrefix($search, $sort);
                else $products_rs = $this->prd_list->products_searchByKeyword($search, $sort); // Tìm kiếm từ khóa thông thường
            } 
            // Các trường hợp kết hợp bộ lọc cơ bản ở thanh điều hướng cạnh bên (Sidebar)
            elseif($category && $price_range) $products_rs = $this->prd_list->products_selectByCategoryAndPrice($category, $min_price, $max_price, $sort);
            elseif($category) $products_rs = $this->prd_list->products_selectByCategory($category, $sort);
            elseif($price_range) $products_rs = $this->prd_list->products_selectByPriceRange($min_price, $max_price, $sort);
            elseif($collection) $products_rs = $this->prd_list->products_selectByCollection($collection, $sort);
            // Mặc định: Nếu không chọn bất kỳ bộ lọc nào, tải toàn bộ danh sách sản phẩm theo tiêu chí sắp xếp
            else $products_rs = $this->prd_list->products_selectAll($sort);


            // --- XỬ LÝ AN TOÀN TRÁNH LỖI N+1 QUERIES & CHUYỂN ĐỔI KIỂU DỮ LIỆU ĐẦU VÀO ---
            $items_array = [];
            if ($products_rs) {
                // Đọc toàn bộ các bản ghi sản phẩm đổ vào mảng PHP chung
                if (is_object($products_rs)) {
                    while ($p = mysqli_fetch_assoc($products_rs)) $items_array[] = $p;
                } elseif (is_array($products_rs)) {
                    $items_array = $products_rs;
                }
            }

            $count = count($items_array); // Đếm tổng số lượng sản phẩm khớp bộ lọc
            $products = []; // Mảng chứa danh sách sản phẩm hoàn chỉnh sau khi gộp đầy đủ thông tin biến thể

            // Duyệt từng sản phẩm để lấy thông tin chi tiết về Màu sắc, Ảnh chi tiết và Mã màu Hex tương ứng
            foreach ($items_array as $p) {
                $colors = []; // Lưu danh sách tên các màu sắc của sản phẩm hiện tại
                $variant_map = []; // Bản đồ liên kết dữ liệu theo từng màu (ID biến thể, ảnh, mã hex)
                
                // Lấy danh sách các biến thể (kích thước, màu sắc) của sản phẩm hiện tại từ database
                $variants_result = $this->home_model->get_variants_by_product($p['id']);
                $v_array = [];
                if ($variants_result) {
                    if (is_object($variants_result)) {
                        while ($v = mysqli_fetch_assoc($variants_result)) $v_array[] = $v;
                    } elseif (is_array($variants_result)) {
                        $v_array = $variants_result;
                    }
                }

                // Gom nhóm thông tin biến thể theo tên Màu sắc
                foreach ($v_array as $v) {
                    if (!in_array($v['color'], $colors)) {
                        $colors[] = $v['color']; // Thêm màu mới vào danh sách nếu chưa tồn tại
                        
                        // Lấy tập hợp các ảnh chi tiết của riêng biến thể màu này từ database
                        $images_result = $this->home_model->get_images_by_variant($v['id']);
                        $images = [];
                        if ($images_result) {
                            if (is_object($images_result)) {
                                while ($img = mysqli_fetch_assoc($images_result)) $images[] = $img['image_url'];
                            } elseif (is_array($images_result)) {
                                foreach ($images_result as $img) $images[] = $img['image_url'];
                            }
                        }
                        // Dự phòng: Nếu biến thể không có hình ảnh riêng, sử dụng ảnh đại diện (thumbnail) gốc của sản phẩm
                        if (empty($images)) $images[] = $p['thumbnail'];
                        
                        // Đóng gói thông tin đầy đủ cho một nhóm màu của sản phẩm
                        $variant_map[$v['color']] = [
                            'variant_id' => $v['id'],
                            'images' => $images,
                            // Gọi hàm lấy mã màu Hex (Ví dụ: #000000) từ tên màu chữ (Ví dụ: 'Đen'), mặc định là '#ccc' nếu hàm lỗi/thiếu
                            'hex' => method_exists($this->home_model, 'get_color_hex') ? $this->home_model->get_color_hex($v['color']) : '#ccc'
                        ];
                    }
                }
                
                // Trực quan hóa cấu trúc dữ liệu cho Frontend dễ dàng xử lý thao tác đổi màu trên UI
                $p['colors'] = $colors;
                $p['variant_map'] = $variant_map;
                
                // Chống lỗi bể chuỗi JSON: Đệ quy kiểm tra và ép toàn bộ dữ liệu dạng chuỗi (String) về bảng mã UTF-8 chuẩn sạch
                array_walk_recursive($p, function(&$item) {
                    if (is_string($item)) {
                        $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
                    }
                });

                $products[] = $p; // Đẩy sản phẩm đã xử lý hoàn chỉnh vào mảng tổng
            }

            // Gói tất cả thông tin kết quả trả về thành một cấu trúc đồng nhất
            $response = [
                'success' => true,
                'count' => $count,
                'products' => $products,
                'current_filter' => $sale === 1 ? 'sale' : $filter,
                'search_keyword' => $search
            ];

            // Render dữ liệu ra định dạng chuỗi JSON
            $json_string = json_encode($response);
            // Kiểm tra an toàn: Nếu xảy ra lỗi không thể chuyển đổi sang JSON (ví dụ lỗi ký tự đặc biệt)
            if ($json_string === false) {
                echo json_encode(['success' => false, 'message' => 'Lỗi mã hóa JSON: ' . json_last_error_msg()]);
                exit;
            }

            echo $json_string; // In chuỗi dữ liệu JSON ra màn hình cho client nhận kết quả
            exit;

        } catch (Throwable $e) {
            // Khối try-catch bắt lại toàn bộ lỗi runtime nghiêm trọng của PHP và trả ra JSON thông báo tường minh vị trí lỗi
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi PHP: ' . $e->getMessage() . ' tại dòng ' . $e->getLine()
            ]);
            exit;
        }
    }
}
?>