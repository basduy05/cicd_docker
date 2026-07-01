<?php
// Định nghĩa lớp vouchers_m kế thừa từ lớp kết nối cơ sở dữ liệu connectDB (Tầng Model trong cấu trúc MVC)
class vouchers_m extends connectDB
{
    // Hàm khởi tạo (Constructor) của Model vouchers_m
    public function __construct()
    {
        // Gọi hàm khởi tạo của lớp cha (connectDB) để thiết lập biến kết nối database ($this->con)
        parent::__construct();
    }
    // Hàm lấy toàn bộ danh sách voucher, sắp xếp theo ID giảm dần (mã mới tạo lên đầu)
    public function vouchers_selectAll()
    {
        $sql = "SELECT * FROM vouchers ORDER BY id DESC"; // Câu lệnh SQL truy vấn tất cả các cột trong bảng vouchers
        return mysqli_query($this->con, $sql); // Thực thi câu lệnh SQL và trả về tập kết quả (Result set)
    }
    // Hàm xóa một mã voucher ra khỏi cơ sở dữ liệu dựa theo ID
    public function vouchers_delete($id)
    {
        $sql = "DELETE FROM vouchers WHERE id=$id"; // Câu lệnh SQL xóa bản ghi theo ID cụ thể
        return mysqli_query($this->con, $sql); // Thực thi câu lệnh DELETE, trả về true nếu thành công, false nếu thất bại
    }
    // Hàm thêm mới một voucher vào cơ sở dữ liệu với đầy đủ các thuộc tính cấu hình giảm giá
    public function vouchers_insert($code, $description, $discount_type, $discount_value, $max_discount_amount, $min_order_value, $usage_limit, $start_date, $end_date, $status = 1)
    {


        // Câu lệnh SQL chèn các giá trị cấu hình của voucher mới vào bảng vouchers
        $sql = "INSERT INTO vouchers (
                code,
                description,
                discount_type,
                discount_value,
                max_discount_amount,
                min_order_value,
                usage_limit,
                used_count,
                start_date,
                end_date,
                status
            )
            VALUES (
                '$code',
                '$description',
                '$discount_type',
                $discount_value,
                $max_discount_amount,
                $min_order_value,
                $usage_limit,
                0, -- used_count mặc định ban đầu là 0
                '$start_date',
                '$end_date',
                $status
            )";

        return mysqli_query($this->con, $sql); // Thực thi câu lệnh INSERT và trả về kết quả thành công/thất bại
    }
    // Hàm cập nhật (sửa đổi) thông tin chi tiết cấu hình của một voucher đã tồn tại theo ID
    public function vouchers_update($id, $usage_limit, $start_date, $end_date, $discount_value, $min_order_value, $max_discount_amount, $status)
    {

        // 1. Xử lý logic cho Max Discount (Giảm tối đa)
        // Nếu người dùng bỏ trống hoặc nhập 0 -> Lưu là NULL trong database (nghĩa là Không giới hạn)
        if ($max_discount_amount === '' || $max_discount_amount === 'NULL' || $max_discount_amount == 0) {
            $max_discount_sql = "NULL"; // Tạo chuỗi chữ NULL để đưa trực tiếp vào câu lệnh SQL
        } else {
            $max_discount_sql = $max_discount_amount; // Giữ nguyên giá trị số tiền giảm tối đa nếu hợp lệ
        }

        // 2. Xử lý min_order nếu rỗng thì cho về 0
        if ($min_order_value === '') {
            $min_order_value = 0; // Gán mặc định giá trị đơn hàng tối thiểu là 0 nếu người dùng bỏ trống
        }

        // 3. Câu lệnh SQL Update
        // Tiến hành cập nhật các trường thông tin điều kiện sử dụng, thời gian và giá trị giảm của voucher
        $sql = "UPDATE vouchers SET
                usage_limit = '$usage_limit',
                start_date = '$start_date',
                end_date = '$end_date',
                discount_value = '$discount_value',
                min_order_value = '$min_order_value',
                max_discount_amount = $max_discount_sql,
                status = '$status'
            WHERE id = '$id'";

        // 4. Thực thi
        return mysqli_query($this->con, $sql); // Thực thi câu lệnh UPDATE và trả về kết quả true/false
    }
    // Hàm tìm kiếm voucher theo từ khóa
    function vouchers_select_search($keyword) {
        // Nếu từ khóa rỗng thì lấy tất cả
        if(empty($keyword)){
            $sql = "SELECT * FROM vouchers ORDER BY id DESC"; // Câu lệnh SQL lấy toàn bộ danh sách khi không nhập từ khóa
        } else {
            
            // Xử lý bảo mật chống lỗi SQL Injection bằng cách làm sạch chuỗi từ khóa nhập vào
            $keyword = mysqli_real_escape_string($this->con, $keyword);
            // Câu lệnh SQL tìm kiếm gần đúng (LIKE) dựa theo mã voucher hoặc phần mô tả của voucher
            $sql = "SELECT * FROM vouchers 
                    WHERE code LIKE '%$keyword%' 
                    OR description LIKE '%$keyword%' 
                    ORDER BY id DESC";
        }
        
        return mysqli_query($this->con, $sql); // Thực thi truy vấn tìm kiếm dữ liệu và trả về tập kết quả thu được
    }
}?>