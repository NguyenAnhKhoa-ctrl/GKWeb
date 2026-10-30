-- File: database/schema.sql
-- Kịch bản khởi tạo Cơ sở dữ liệu và bảng dữ liệu trên máy chủ Production
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS deploytrack_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE deploytrack_db;

-- Xóa bảng cũ nếu chạy nạp lại nhiều lần để tránh bị nhân bản dữ liệu
DROP TABLE IF EXISTS deployment_tasks;

CREATE TABLE deployment_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category ENUM('Server Setup', 'Database', 'Domain & HTTPS', 'Security & Optimization') NOT NULL DEFAULT 'Server Setup',
    status ENUM('pending', 'in_progress', 'completed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu mẫu phục vụ kiểm tra sau khi triển khai
INSERT INTO deployment_tasks (title, category, status) VALUES
('Cài đặt Web Server (Apache/Nginx) và PHP 8.2 trên máy chủ Ubuntu', 'Server Setup', 'completed'),
('Tạo Database MySQL và cấp quyền tối thiểu cho tài khoản deploy_user', 'Database', 'completed'),
('Trỏ bản ghi DNS (A Record) từ tên miền về địa chỉ IP Public của máy chủ', 'Domain & HTTPS', 'completed'),
('Cài đặt chứng chỉ số SSL/TLS miễn phí Let''s Encrypt bằng công cụ Certbot', 'Domain & HTTPS', 'completed'),
('Cấu hình file .env và tắt hiển thị lỗi chi tiết (display_errors = 0)', 'Security & Optimization', 'in_progress'),
('Thiết lập tự động sao lưu cơ sở dữ liệu (mysqldump) mỗi ngày lúc 02:00 sáng', 'Database', 'pending');
