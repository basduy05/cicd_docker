Khi user truy cập trang danh mục, server sẽ phân tích URL thông qua router app.php, xác định
được controller categories_list và action Get_data, sau đó gọi hàm đó để load view
categories_list_v.php hiển thị giao diện. Khi trang load xong, JavaScript bên trong view sẽ tự
động gọi API api_get_data() thông qua fetch để lấy dữ liệu danh mục từ database, controller sẽ
kiểm tra có tham số tìm kiếm q không, nếu có thì gọi model categories_select() để tìm kiếm
bằng LIKE, không thì gọi categories_selectAll() lấy tất cả, rồi model query database và trả kết
quả về controller dạng mảng, controller encode thành JSON và trả về cho frontend. JavaScript
nhận JSON này sẽ parse và cập nhật bảng HTML để hiển thị dữ liệu danh mục cho user.

Khi user muốn thêm, sửa, hoặc xóa danh mục, họ sẽ submit form hoặc nhấn nút tương ứng,
JavaScript sẽ gửi POST hoặc GET request đến API add(), update(), hoặc delete(), controller sẽ
validate dữ liệu đầu vào, nếu có file ảnh thì upload vào thư mục, sau đó gọi model để insert,
update, hoặc delete vào database, rồi trả JSON response về frontend, JavaScript sẽ kiểm tra
kết quả và hiển thị thông báo SweetAlert, nếu thành công thì gọi lại loadData() để reload bảng.
Tương tự với export Excel và import Excel, controller sẽ xử lý và trả dữ liệu hoặc file về client.

Sửa tắt là: View gọi API $\rightarrow$ Controller xử lý validate + gọi Model $\rightarrow$
Viết tắt là: View gọi API $\rightarrow$ Controller xử lý validate + gọi Model $\rightarrow$

Model query database $\rightarrow$ Controller trả JSON $\rightarrow$ View cập nhật UI.
