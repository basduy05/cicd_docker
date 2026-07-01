<?php
// Định nghĩa lớp ProductModel chuyên xử lý các thao tác dữ liệu liên quan đến sản phẩm
class ProductModel {
    // Biến private để lưu trữ đối tượng kết nối cơ sở dữ liệu PDO
    private $db;
    // Hàm khởi tạo (Constructor) - tự động chạy khi class được tạo đối tượng
    public function __construct() {
        // Thay đổi thông tin theo database của bạn
        // Khởi tạo một kết nối PDO đến cơ sở dữ liệu MySQL với host, tên db, cấu hình bảng mã utf8 và tài khoản truy cập
        $this->db = new PDO("mysql:host=localhost;dbname=ten_db;charset=utf8", "root", "");
    }

    // Hàm lấy ra toàn bộ danh sách sản phẩm có trong bảng database
    public function getAll() {
        $query = "SELECT * FROM products"; // Câu lệnh SQL truy vấn tất cả các cột từ bảng products
        $stmt = $this->db->prepare($query); // Chuẩn bị thực thi câu lệnh SQL (Prepare statement) để đảm bảo an toàn bảo mật
        $stmt->execute(); // Thực hiện chạy câu lệnh SQL đã chuẩn bị trên hệ quản trị cơ sở dữ liệu
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Trả về toàn bộ các dòng kết quả dưới dạng mảng kết hợp (mảng key-value)
    }
}?>