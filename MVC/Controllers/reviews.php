<?php
// Định nghĩa lớp reviews kế thừa từ lớp cơ sở controllers (mô hình MVC)
class reviews extends controllers {
    // Biến private để lưu trữ đối tượng Model xử lý dữ liệu liên quan đến reviews
    private $review;
    
    // Hàm khởi tạo (Constructor) - tự động chạy khi class được khởi tạo
    function __construct() {
        // Lấy chuỗi URI hiện tại của request (ví dụ: /reviews/add)
        $current_uri = $_SERVER['REQUEST_URI'];

    // Kiểm tra xem request hiện tại có phải là các API dành cho khách hàng hay không
    $is_customer_api = (
        strpos($current_uri, '/reviews/add') !== false || 
        strpos($current_uri, '/reviews/edit_user') !== false || 
        strpos($current_uri, '/reviews/delete_user') !== false || 
        strpos($current_uri, '/reviews/api_get_by_product') !== false
    );

    // Nếu là API của khách hàng, xử lý riêng để tối ưu hiệu năng (bỏ qua middleware hoặc logic của admin)
    if ($is_customer_api) {
        // Nếu Session chưa được kích hoạt thì tiến hành khởi động Session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Khởi tạo model 'reviews_m' để tương tác với cơ sở dữ liệu
        $this->review = $this->model('reviews_m'); 
        
        return; // Kết thúc hàm khởi tạo sớm cho luồng API khách hàng
    }
        // Nếu không phải API khách hàng, gọi hàm khởi tạo của class cha (controllers) để xử lý xác thực/phân quyền admin
        parent::__construct();
        // Khởi tạo model 'reviews_m' cho luồng thông thường hoặc luồng của admin
        $this->review = $this->model('reviews_m');
    }
    
    // Hàm hiển thị giao diện quản lý đánh giá (thường dành cho Admin)
    function Get_data() {
        // Gọi đến View 'Master' và truyền cấu hình giao diện chính là trang 'reviews_v'
        $this->view('Master', [
            'Page' => 'reviews_v' 
        ]);
    }

    // Hàm bổ trợ thiết lập header định dạng API trả về dữ liệu dạng JSON và cho phép gọi từ bên ngoài (CORS)
    private function setApiHeader() {
        header('Access-Control-Allow-Origin: *'); // Cho phép mọi tên miền truy cập API này
        header('Content-Type: application/json; charset=utf-8'); // Thiết lập kiểu nội dung trả về là JSON, mã hóa UTF-8
    }

    // API lấy danh sách các đánh giá theo ID sản phẩm
    function api_get_by_product() {
        $this->setApiHeader(); // Cài đặt cấu hình JSON Header
        // Lấy product_id từ phương thức GET, ép kiểu về số nguyên, mặc định là 0 nếu không có
        $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
        // Gọi Model để truy vấn các đánh giá của sản phẩm này trong database
        $reviews_result = $this->review->reviews_selectByProduct($product_id);
        
        $reviews = []; // Khởi tạo mảng rỗng để chứa dữ liệu đánh giá
        // Kiểm tra nếu có kết quả trả về và số lượng dòng dữ liệu lớn hơn 0
        if ($reviews_result && mysqli_num_rows($reviews_result) > 0) {
            // Duyệt qua từng dòng kết quả thu được từ MySQL
            while ($row = mysqli_fetch_assoc($reviews_result)) {
                $reviews[] = $row; // Thêm dòng dữ liệu vào mảng danh sách
            }
        }
        // Trả dữ liệu về Client dưới dạng chuỗi JSON và thông báo thành công
        echo json_encode(['success' => true, 'data' => $reviews]);
        exit; // Dừng chương trình
    }

    // API lấy toàn bộ danh sách đánh giá của hệ thống (phục vụ trang quản trị)
    function api_get_all() {
        $this->setApiHeader(); // Cài đặt cấu hình JSON Header
        // Gọi Model để truy vấn toàn bộ đánh giá của tất cả sản phẩm
        $reviews_result = $this->review->reviews_selectAllAdmin();
        $reviews = []; // Khởi tạo mảng chứa dữ liệu
        // Kiểm tra và duyệt dữ liệu nếu có kết quả trả về từ database
        if ($reviews_result && mysqli_num_rows($reviews_result) > 0) {
            while ($row = mysqli_fetch_assoc($reviews_result)) {
                $reviews[] = $row; // Đẩy dữ liệu vào mảng
            }
        }
        // Trả về chuỗi dữ liệu JSON của toàn bộ đánh giá
        echo json_encode(['success' => true, 'data' => $reviews]);
        exit; // Dừng chương trình
    }
    
    // API xử lý việc thêm mới một đánh giá từ khách hàng
    function add() {
        $this->setApiHeader(); // Cài đặt cấu hình JSON Header
        // Kiểm tra phương thức gửi lên, nếu không phải là POST thì từ chối xử lý
        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận POST']); exit;
        }

        // Đảm bảo Session đã được bật để kiểm tra thông tin người dùng
        if (session_status() === PHP_SESSION_NONE) session_start();
        // Kiểm tra xem người dùng đã đăng nhập hay chưa (kiểm tra tồn tại user_id trong Session)
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để đánh giá!']); exit;
        }

        // Lấy các thông tin từ Session và dữ liệu POST gửi lên
        $user_id = $_SESSION['user_id']; // ID người dùng đang đăng nhập
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0; // ID sản phẩm cần đánh giá
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 5; // Số sao đánh giá, mặc định là 5 sao
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : ''; // Nội dung đánh giá, đã cắt bỏ khoảng trắng thừa

        // Kiểm tra nếu phần nội dung đánh giá bị bỏ trống
        if (empty($comment)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập nội dung đánh giá!']); exit;
        }

        // Gọi Model kiểm tra xem tài khoản này đã mua sản phẩm này và nhận hàng thành công chưa
        $order_id = $this->review->reviews_checkEligible($user_id, $product_id);

        // Nếu không thỏa mãn điều kiện mua hàng (Model trả về false), từ chối cho đánh giá
        if (!$order_id) {
            echo json_encode(['success' => false, 'message' => 'Bạn chỉ được đánh giá 1 lần cho sản phẩm đã mua và nhận hàng thành công!']); exit;
        }

        // Tiến hành ghi nhận dữ liệu đánh giá mới vào cơ sở dữ liệu
        $kq = $this->review->reviews_insert($user_id, $product_id, $order_id, $rating, $comment);
        // Trả kết quả về cho client tùy thuộc vào biến $kq thành công (true) hay thất bại (false)
        echo json_encode(['success' => $kq, 'message' => $kq ? 'Cảm ơn bạn đã đánh giá sản phẩm!' : 'Lỗi hệ thống, vui lòng thử lại']);
        exit; // Dừng chương trình
    }

    // API cho phép người dùng tự chỉnh sửa lại đánh giá của chính họ
    function edit_user() {
        $this->setApiHeader(); // Cài đặt cấu hình JSON Header
        if (session_status() === PHP_SESSION_NONE) session_start(); // Kích hoạt Session nếu chưa có
        // Bắt buộc người dùng phải đăng nhập mới thực hiện được thao tác này
        if (!isset($_SESSION['user_id'])) { 
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']); exit; 
        }
        
        // Nhận dữ liệu ID người dùng, ID đánh giá, số sao và nội dung mới
        $u_id = $_SESSION['user_id'];
        $review_id = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 5;
        $comment = trim($_POST['comment'] ?? ''); // Lấy nội dung, xử lý trường hợp biến không tồn tại bằng toán tử ??

        // Kiểm tra dữ liệu đầu vào cơ bản: ID đánh giá phải hợp lệ (>0) và nội dung không rỗng
        if ($review_id <= 0 || empty($comment)) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ!']); exit;
        }

        // Tìm bản ghi đánh giá cũ trong cơ sở dữ liệu dựa theo ID
        $res = $this->review->reviews_getById($review_id);
        if ($res && $row = mysqli_fetch_assoc($res)) {
            // Kiểm tra bảo mật: Nếu người đang sửa không phải là người đã viết đánh giá này thì chặn lại
            if ($row['user_id'] != $u_id) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền sửa đánh giá này!']); exit;
            }
        } else {
            // Trường hợp không tồn tại đánh giá nào trùng với ID gửi lên
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy đánh giá!']); exit;
        }

        // Gọi Model thực thi câu lệnh UPDATE sửa đổi đánh giá trong database
        $kq = $this->review->reviews_update_by_user($review_id, $u_id, $rating, $comment);
        // Trả phản hồi JSON thông báo trạng thái cập nhật thành công hay thất bại
        echo json_encode(['success' => $kq, 'message' => $kq ? 'Đã cập nhật đánh giá thành công!' : 'Lỗi hệ thống!']);
        exit; // Dừng chương trình
    }

    // API cho phép người dùng tự xóa bài đánh giá của mình thông qua ID truyền trên URL
    function delete_user($id) {
        $this->setApiHeader(); // Cài đặt cấu hình JSON Header
        if (session_status() === PHP_SESSION_NONE) session_start(); // Khởi tạo Session
        // Yêu cầu bắt buộc đăng nhập để xác định danh tính
        if (!isset($_SESSION['user_id'])) { 
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']); exit; 
        }
        
        $u_id = $_SESSION['user_id']; // Lấy ID người dùng hiện tại
        // Gọi Model thực hiện lệnh xóa bản ghi dựa trên ID đánh giá và ID người dùng (để tránh xóa nhầm của người khác)
        $kq = $this->review->reviews_delete_by_user($id, $u_id);
        // Trả phản hồi trạng thái xóa về cho phía Client
        echo json_encode(['success' => $kq, 'message' => $kq ? 'Đã xóa đánh giá của bạn!' : 'Lỗi khi xóa!']);
        exit; // Dừng chương trình
    }
    
    // API dành cho Admin: Bật/Tắt trạng thái hiển thị của một đánh giá (Ẩn/Hiện đánh giá ngoài giao diện)
    function toggle($id) {
        $this->setApiHeader(); // Cài đặt cấu hình JSON Header
        // Gọi Model thực thi chuyển đổi trạng thái (ví dụ từ Active sang Inactive và ngược lại)
        $kq = $this->review->reviews_toggleStatus($id);
        // Trả về kết quả xử lý dưới dạng JSON
        echo json_encode(['success' => $kq, 'message' => $kq ? 'Đã thay đổi trạng thái' : 'Lỗi thay đổi trạng thái']);
        exit; // Dừng chương trình
    }
    
    // API dành cho Admin: Xóa hoàn toàn một đánh giá bất kỳ trong hệ thống quản trị
    function delete($id) {
        $this->setApiHeader(); // Cài đặt cấu hình JSON Header
        // Gọi Model trực tiếp thực hiện lệnh xóa đánh giá theo ID mà không cần check user_id (vì đây là quyền Admin)
        $kq = $this->review->reviews_delete($id);
        // Trả về thông báo JSON hoàn tất tác vụ
        echo json_encode(['success' => $kq, 'message' => $kq ? 'Đã xóa đánh giá thành công' : 'Lỗi khi xóa']);
        exit; // Dừng chương trình
    }
}
?>