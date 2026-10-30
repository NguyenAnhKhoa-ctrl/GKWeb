<?php
/**
 * File: config/database.php
 * Nhiệm vụ: Khởi tạo kết nối CSDL MySQL bằng thư viện PDO an toàn,
 * sử dụng thông số lấy từ file cấu hình môi trường (.env).
 */

require_once __DIR__ . '/env_loader.php';

function getDatabaseConnection(): PDO {
    $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $dbName = $_ENV['DB_NAME'] ?? 'deploytrack_db';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASS'] ?? '';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";

    $options = [
        // Báo lỗi dưới dạng ngoại lệ (Exception) để dễ kiểm soát bằng khối try-catch
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        // Trả về dữ liệu dưới dạng mảng kết hợp (key-value)
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Tắt giả lập Prepared Statements để chống tấn công SQL Injection triệt để
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    return new PDO($dsn, $user, $pass, $options);
}
