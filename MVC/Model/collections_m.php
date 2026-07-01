<?php
// Định nghĩa lớp collections_m kế thừa từ lớp kết nối cơ sở dữ liệu connectDB (Tầng Model trong MVC)
class collections_m extends connectDB {
    // Hàm khởi tạo (Constructor) của Model
    function __construct() {
        // Gọi hàm khởi tạo của lớp cha (connectDB) để thiết lập và thiết lập biến kết nối database ($this->con)
        parent::__construct();
    }
    // Hàm lấy toàn bộ danh sách bộ sưu tập (collections), sắp xếp theo ID giảm dần (mới nhất xếp trước)
    function collections_selectAll() {
        $sql = "SELECT * FROM collections ORDER BY id DESC"; // Câu lệnh SQL truy vấn tất cả các cột từ bảng collections
        return mysqli_query($this->con, $sql); // Thực thi câu lệnh SQL và trả về tập kết quả (Result set)
    }
    // Hàm thêm mới một bộ sưu tập vào cơ sở dữ liệu
    function collections_insert($name, $slug, $thumbnail) {
        // Câu lệnh SQL chèn dữ liệu tên, đường dẫn thân thiện (slug) và ảnh đại diện vào bảng collections
        $sql = "INSERT INTO collections (name, slug, thumbnail) VALUES ('$name', '$slug', '$thumbnail')";
        return mysqli_query($this->con, $sql); // Thực thi câu lệnh INSERT, trả về true nếu thành công, false nếu thất bại
    }
    // Hàm cập nhật (sửa) thông tin của một bộ sưu tập dựa trên ID cụ thể
    function collections_update($id, $name, $slug, $thumbnail) {
        // Câu lệnh SQL thay đổi các giá trị dữ liệu mới cho bản ghi có id trùng khớp
        $sql = "UPDATE collections SET name='$name', slug='$slug', thumbnail='$thumbnail' WHERE id=$id";
        return mysqli_query($this->con, $sql); // Thực thi câu lệnh UPDATE, trả về true/false
    }
    // Hàm xóa một bộ sưu tập ra khỏi hệ thống theo ID
    function collections_delete($id) {
        $sql = "DELETE FROM collections WHERE id=$id"; // Câu lệnh SQL xóa bản ghi theo ID trong bảng collections
        return mysqli_query($this->con, $sql); // Thực thi câu lệnh DELETE, trả về true/false
    }
    // Hàm tìm kiếm các bộ sưu tập dựa theo tên (tìm kiếm gần đúng)
    function collections_select($name) {
        // Câu lệnh SQL sử dụng mệnh đề LIKE để tìm tên chứa chuỗi ký tự cần tìm, sắp xếp ID giảm dần
        $sql = "SELECT * FROM collections WHERE name LIKE '%$name%' ORDER BY id DESC";
        return mysqli_query($this->con, $sql); // Thực thi câu lệnh truy vấn và trả về danh sách kết quả tìm được
    }
    // Trong file CollectionModel.php
     // Hàm tính tổng số lượng sản phẩm thuộc về một bộ sưu tập cụ thể
     function countProductsInCollection($collectionId) {
    // Đếm số dòng trong bảng products có collection_id tương ứng
    $sql = "SELECT COUNT(*) as total FROM products WHERE collection_id = '$collectionId'"; // Câu lệnh SQL đếm tổng số dòng thỏa mãn điều kiện và đặt bí danh là 'total'
    $result = mysqli_query($this->con, $sql); // Thực thi câu lệnh đếm dữ liệu
    
    // Kiểm tra và lấy kết quả
    if ($result) { // Nếu câu lệnh SQL chạy thành công và trả về kết quả hợp lệ
        $row = mysqli_fetch_assoc($result); // Chuyển đổi dòng kết quả thành mảng kết hợp (associative array)
        return $row['total']; // Trả về con số tổng số lượng sản phẩm đếm được
    }
    return 0; // Trả về 0 nếu lỗi hoặc không có sp
}
}