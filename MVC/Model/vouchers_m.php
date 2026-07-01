<?php
class vouchers_m extends connectDB
{
    public function __construct()
    {
        parent::__construct();
    }
    public function vouchers_selectAll()
    {
        $sql = "SELECT * FROM vouchers ORDER BY id DESC";
        return mysqli_query($this->con, $sql);
    }
    public function vouchers_delete($id)
    {
        $sql = "DELETE FROM vouchers WHERE id=$id";
        return mysqli_query($this->con, $sql);
    }
    public function vouchers_insert($code, $description, $discount_type, $discount_value, $max_discount_amount, $min_order_value, $usage_limit, $start_date, $end_date, $status = 1)
    {


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

        return mysqli_query($this->con, $sql);
    }
    public function vouchers_update($id, $usage_limit, $start_date, $end_date, $discount_value, $min_order_value, $max_discount_amount, $status)
    {

        // 1. Xử lý logic cho Max Discount (Giảm tối đa)
        // Nếu người dùng bỏ trống hoặc nhập 0 -> Lưu là NULL trong database (nghĩa là Không giới hạn)
        if ($max_discount_amount === '' || $max_discount_amount === 'NULL' || $max_discount_amount == 0) {
            $max_discount_sql = "NULL";
        } else {
            $max_discount_sql = $max_discount_amount;
        }

        // 2. Xử lý min_order nếu rỗng thì cho về 0
        if ($min_order_value === '') {
            $min_order_value = 0;
        }

        // 3. Câu lệnh SQL Update
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
        return mysqli_query($this->con, $sql);
    }
    // Hàm tìm kiếm voucher theo từ khóa
    function vouchers_select_search($keyword) {
        // Nếu từ khóa rỗng thì lấy tất cả
        if(empty($keyword)){
            $sql = "SELECT * FROM vouchers ORDER BY id DESC";
        } else {
            
            $keyword = mysqli_real_escape_string($this->con, $keyword);
            $sql = "SELECT * FROM vouchers 
                    WHERE code LIKE '%$keyword%' 
                    OR description LIKE '%$keyword%' 
                    ORDER BY id DESC";
        }
        
        return mysqli_query($this->con, $sql);
    }
}
