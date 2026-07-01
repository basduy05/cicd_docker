<?php
class categories_m extends connectDB {
    function __construct() {
        parent::__construct();
    }
    // Hàm kiểm tra trùng lặp slug
    public function checkDuplicate($slug) {
        $sql = "SELECT id FROM categories WHERE slug = '$slug'";
        $result = mysqli_query($this->con, $sql);
        return mysqli_num_rows($result);
    }
    // Hàm liệt kê tất cả danh mục theo thứ tự giảm dần của id
    function categories_selectAll() {
        $sql = "SELECT * FROM categories ORDER BY id DESC";
        return mysqli_query($this->con, $sql);
    }
    // Hàm thêm danh mục
    function categories_insert($name, $slug, $thumbnail) {
        $sql = "INSERT INTO categories (name, slug, thumbnail) VALUES ('$name', '$slug', '$thumbnail')";
        return mysqli_query($this->con, $sql);
    }
    // Hàm cập nhật danh mục
    function categories_update($id, $name, $slug, $thumbnail) {
        $sql = "UPDATE categories SET name='$name', slug='$slug', thumbnail='$thumbnail' WHERE id=$id";
        return mysqli_query($this->con, $sql);
    }
    //Hàm xoá danh mục
    function categories_delete($id) {
        $sql = "DELETE FROM categories WHERE id=$id";
        return mysqli_query($this->con, $sql);
    }
    // Hàm tìm kiếm tương đối theo tên danh mục
    function categories_select($name) {
        $sql = "SELECT * FROM categories WHERE name LIKE '%$name%' ORDER BY id DESC";
        return mysqli_query($this->con, $sql);
    }

}