# DeployTrack – Ứng Dụng Mẫu Minh Họa Triển Khai Ứng Dụng Web (Chủ Đề 5)
**Lý Hạo Danh** – MSSV: `52400175`
**Nguyễn Anh Khoa** – MSSV: `52400051`

---

## 1. Giới thiệu dự án
**DeployTrack** là ứng dụng Web được xây dựng bằng bộ công nghệ thuần **HTML5, CSS3, JavaScript (Fetch API), PHP (PDO) và MySQL** nhằm minh họa trực quan quy trình đưa một ứng dụng Web từ máy tính cá nhân (Development) lên máy chủ thực tế (Production).

### Các tính năng cốt lõi bám sát Chủ đề 5:
- **Tách biệt cấu hình môi trường (`.env`):** Không lưu cứng (hard-code) mật khẩu CSDL trong mã nguồn PHP.
- **Tự động điều chỉnh cơ chế báo lỗi:** Tự động tắt `display_errors` khi `APP_ENV=production` và ghi lỗi ngầm vào `logs/app_error.log`.
- **Kiểm tra trạng thái máy chủ (`api/health.php`):** Tự động phát hiện ứng dụng đang chạy qua giao thức `HTTP` hay `HTTPS (SSL/TLS)`, phiên bản PHP và trạng thái kết nối CSDL MySQL.
- **Quản lý danh sách công việc triển khai (`api/tasks.php`):** Ứng dụng CRUD kết nối MySQL qua thư viện **PDO** với **Prepared Statements** chống SQL Injection.
- **Cấu hình Web Server thực tế:** Kèm theo file `.htaccess` (cho Apache / Shared Hosting) và `deploy/nginx.conf` (cho VPS Nginx + Certbot Let's Encrypt SSL).

---

## 2. Cấu trúc thư mục
```text
source_code/
├── .env.example          # File mẫu cấu hình biến môi trường (Database, APP_ENV, HTTPS)
├── .htaccess             # Cấu hình Apache: ép buộc HTTPS 301 & chặn truy cập file .env
├── index.html            # Giao diện chính của ứng dụng (HTML5)
├── assets/
│   ├── css/style.css     # Định dạng giao diện (CSS3)
│   └── js/app.js         # Xử lý bất đồng bộ với Fetch API (JavaScript)
├── config/
│   ├── env_loader.php    # Trình đọc file .env và thiết lập chế độ lỗi Production
│   └── database.php      # Kết nối MySQL an toàn bằng PDO
├── api/
│   ├── health.php        # API kiểm tra sức khỏe máy chủ, SSL/HTTPS và Database
│   └── tasks.php         # API thêm, sửa, xóa, lấy danh sách hạng mục triển khai
├── database/
│   └── schema.sql        # Kịch bản tạo bảng và dữ liệu mẫu MySQL
└── deploy/
    └── nginx.conf        # Cấu hình mẫu cho máy chủ Nginx + PHP-FPM + SSL
```

---

## 3. Hướng dẫn cài đặt và chạy thử
### Cách 1: Chạy trên máy cá nhân (Localhost với XAMPP / Laragon / PHP Built-in Server)
1. Sao chép file `.env.example` thành `.env`:
   ```bash
   cp .env.example .env
   ```
2. Khởi tạo cơ sở dữ liệu MySQL (tùy chọn – nếu chưa bật MySQL, ứng dụng vẫn tự động chạy với dữ liệu dự phòng):
   ```bash
   mysql -u root -p < database/schema.sql
   ```
3. Chạy máy chủ PHP tích hợp sẵn tại thư mục `source_code`:
   ```bash
   php -S localhost:8000
   ```
4. Mở trình duyệt truy cập: `http://localhost:8000`

### Cách 2: Triển khai lên máy chủ thực tế (Ubuntu VPS / Shared Hosting)
1. Tải toàn bộ mã nguồn lên thư mục gốc của Web Server (`/var/www/deploytrack` hoặc `public_html`).
2. Tạo cơ sở dữ liệu và tài khoản MySQL riêng biệt, sau đó nạp file `database/schema.sql`.
3. Tạo file `.env` với `APP_ENV=production`, `APP_DEBUG=false` và điền thông tin tài khoản MySQL.
4. Cài đặt chứng chỉ SSL miễn phí bằng Certbot:
   ```bash
   sudo certbot --nginx -d deploytrack-demo.id.vn
   ```
