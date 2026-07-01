<?php
class reviews extends controllers {
    private $review;
    
    // Khởi tạo controller và load model đánh giá, đồng thời xử lý API dành cho khách hàng.
    function __construct() {
        $current_uri = $_SERVER['REQUEST_URI'];

    $is_customer_api = (
        strpos($current_uri, '/reviews/add') !== false || 
        strpos($current_uri, '/reviews/edit_user') !== false || 
        strpos($current_uri, '/reviews/delete_user') !== false || 
        strpos($current_uri, '/reviews/api_get_by_product') !== false
    );

    if ($is_customer_api) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->review = $this->model('reviews_m'); 
        
        return; 
    }
        parent::__construct();
        $this->review = $this->model('reviews_m');
    }
    
    // Hiển thị giao diện quản lý đánh giá.
    function Get_data() {
        $this->view('Master', [
            'Page' => 'reviews_v' 
        ]);
    }

    // Thiết lập header trả về dữ liệu JSON cho API.
    private function setApiHeader() {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json; charset=utf-8');
    }

    // Lấy danh sách đánh giá theo sản phẩm.
    function api_get_by_product() {
        $this->setApiHeader();
        $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
        $reviews_result = $this->review->reviews_selectByProduct($product_id);
        
        $reviews = [];
        if ($reviews_result && mysqli_num_rows($reviews_result) > 0) {
            while ($row = mysqli_fetch_assoc($reviews_result)) {
                $reviews[] = $row;
            }
        }
        echo json_encode(['success' => true, 'data' => $reviews]);
        exit;
    }

    // Lấy toàn bộ đánh giá cho trang quản trị.
    function api_get_all() {
        $this->setApiHeader();
        $reviews_result = $this->review->reviews_selectAllAdmin();
        $reviews = [];
        if ($reviews_result && mysqli_num_rows($reviews_result) > 0) {
            while ($row = mysqli_fetch_assoc($reviews_result)) {
                $reviews[] = $row;
            }
        }
        echo json_encode(['success' => true, 'data' => $reviews]);
        exit;
    }
    
    // Thêm đánh giá mới từ khách hàng đã mua hàng.
    function add() {
        $this->setApiHeader();
        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận POST']); exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để đánh giá!']); exit;
        }

        $user_id = $_SESSION['user_id'];
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 5;
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

        if (empty($comment)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập nội dung đánh giá!']); exit;
        }

        $order_id = $this->review->reviews_checkEligible($user_id, $product_id);

        if (!$order_id) {
            echo json_encode(['success' => false, 'message' => 'Bạn chỉ được đánh giá 1 lần cho sản phẩm đã mua và nhận hàng thành công!']); exit;
        }

        $kq = $this->review->reviews_insert($user_id, $product_id, $order_id, $rating, $comment);
        echo json_encode(['success' => $kq, 'message' => $kq ? 'Cảm ơn bạn đã đánh giá sản phẩm!' : 'Lỗi hệ thống, vui lòng thử lại']);
        exit;
    }

    // Cho phép người dùng chỉnh sửa đánh giá của chính mình.
    function edit_user() {
        $this->setApiHeader();
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) { 
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']); exit; 
        }
        
        $u_id = $_SESSION['user_id'];
        $review_id = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 5;
        $comment = trim($_POST['comment'] ?? '');

        if ($review_id <= 0 || empty($comment)) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ!']); exit;
        }

        $res = $this->review->reviews_getById($review_id);
        if ($res && $row = mysqli_fetch_assoc($res)) {
            if ($row['user_id'] != $u_id) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền sửa đánh giá này!']); exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy đánh giá!']); exit;
        }

        $kq = $this->review->reviews_update_by_user($review_id, $u_id, $rating, $comment);
        echo json_encode(['success' => $kq, 'message' => $kq ? 'Đã cập nhật đánh giá thành công!' : 'Lỗi hệ thống!']);
        exit;
    }

    // Xóa đánh giá của người dùng hiện tại.
    function delete_user($id) {
        $this->setApiHeader();
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) { 
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']); exit; 
        }
        
        $u_id = $_SESSION['user_id'];
        $kq = $this->review->reviews_delete_by_user($id, $u_id);
        echo json_encode(['success' => $kq, 'message' => $kq ? 'Đã xóa đánh giá của bạn!' : 'Lỗi khi xóa!']);
        exit;
    }
    
    // Đổi trạng thái hiển thị của đánh giá.
    function toggle($id) {
        $this->setApiHeader();
        $kq = $this->review->reviews_toggleStatus($id);
        echo json_encode(['success' => $kq, 'message' => $kq ? 'Đã thay đổi trạng thái' : 'Lỗi thay đổi trạng thái']);
        exit;
    }
    
    // Xóa đánh giá khỏi hệ thống.
    function delete($id) {
        $this->setApiHeader();
        $kq = $this->review->reviews_delete($id);
        echo json_encode(['success' => $kq, 'message' => $kq ? 'Đã xóa đánh giá thành công' : 'Lỗi khi xóa']);
        exit;
    }
}
?>