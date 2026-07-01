<?php
// Định nghĩa lớp register xử lý luồng đăng ký tài khoản khách hàng (Kế thừa từ lớp controllers hệ thống)
class register extends controllers {
    // Thuộc tính private để lưu trữ đối tượng Model xử lý dữ liệu người dùng
    private $userModel;

    // Hàm khởi tạo (Constructor) - Tự động chạy khi luồng đăng ký được gọi
    function __construct() {
        parent::__construct(); // Gọi hàm khởi tạo của lớp cha (controllers) để kế thừa các thiết lập hệ thống
        $this->userModel = $this->model("users_m"); // Nạp Model users_m để sẵn sàng thao tác với bảng dữ liệu người dùng
    }

    // Hàm private bổ trợ nhằm thiết lập các Header tiêu chuẩn cho một API
    private function setApiHeader() {
        header('Access-Control-Allow-Origin: *'); // Cho phép các domain khác nhau gọi API này (CORS)
        header('Content-Type: application/json; charset=utf-8'); // Thiết lập định dạng dữ liệu trả về bắt buộc là JSON UTF-8
    }

    // API CHÍNH: TIẾP NHẬN VÀ XỬ LÝ DỮ LIỆU ĐĂNG KÝ TỪ FORM
    function do_register() {
        $this->setApiHeader(); // Gọi hàm thiết lập cấu hình Header API ở trên

        // Kiểm tra phương thức gửi lên hệ thống, bắt buộc phải là POST để đảm bảo an toàn bảo mật dữ liệu
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Tiếp nhận dữ liệu thông tin cá nhân cơ bản từ form gửi lên (Sử dụng toán tử ?? để tránh lỗi nếu trường bị trống)
            $full_name = $_POST['full_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $password = $_POST['password'] ?? ''; 
            
            // Tiếp nhận dữ liệu liên quan đến thông tin địa chỉ giao hàng mặc định của khách hàng
            $province_code = $_POST['province_code'] ?? ''; // Mã Tỉnh/Thành phố
            $district_code = $_POST['district_code'] ?? ''; // Mã Quận/Huyện
            $ward_code = $_POST['ward_code'] ?? '';     // Mã Phường/Xã
            $address_detail = $_POST['address_detail'] ?? ''; // Số nhà, tên đường cụ thể

            // Tiếp nhận các thông số mở rộng (Hỗ trợ định danh khi đăng nhập bằng Google hoặc ảnh đại diện)
            $google_id = $_POST['google_id'] ?? "";
            $avatar = $_POST['avatar'] ?? "default.png"; // Nếu không có ảnh, gán mặc định là file default.png

            // Gọi hàm xử lý thêm mới tài khoản từ Model và truyền toàn bộ các tham số đã thu thập
            $kq = $this->userModel->users_insert_default(
                $full_name, $email, $phone, $password, 
                $google_id, $avatar, 
                $province_code, $district_code, $ward_code, $address_detail
            );
            
            // RẼ NHÁNH KẾT QUẢ TRẢ VỀ TỪ MODEL ĐỂ PHẢN HỒI JSON PHÙ HỢP CHO FRONTEND
            if ($kq === "EMAIL_EXISTED") {
                // Trường hợp Model phát hiện Email này đã có tài khoản khác đăng ký sử dụng trước đó
                echo json_encode(['success' => false, 'message' => 'Email này đã được sử dụng. Vui lòng chọn email khác.']);
            } elseif ($kq === "PHONE_EXISTED") {
                // Trường hợp Model phát hiện Số điện thoại này đã tồn tại trong hệ thống
                echo json_encode(['success' => false, 'message' => 'Số điện thoại này đã được sử dụng.']);
            } elseif ($kq === true) {
                // Trường hợp thêm mới thành công, tạo tài khoản mới thành công vào Database
                echo json_encode(['success' => true, 'message' => 'Đăng ký tài khoản thành công! Bạn có thể đăng nhập ngay bây giờ.']);
            } else {
                // Trường hợp Model trả về lỗi truy vấn dữ liệu hoặc lỗi kết nối hệ thống database
                echo json_encode(['success' => false, 'message' => 'Đăng ký thất bại do lỗi hệ thống. Vui lòng thử lại.']);
            }
        } else {
            // Trả về lỗi khi client cố tình truy cập API bằng các phương thức khác như GET, PUT, DELETE...
            echo json_encode(['success' => false, 'message' => 'Lỗi phương thức']);
        }
        exit; // Kết thúc script để đảm bảo không có dữ liệu thừa nào lọt xuống luồng đầu ra của API
    }
}
?>