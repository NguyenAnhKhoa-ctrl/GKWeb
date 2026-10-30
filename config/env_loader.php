<?php
/**
 * File: config/env_loader.php
 * Nhiệm vụ: Đọc các biến cấu hình từ file .env vào môi trường PHP
 * và thiết lập chế độ xử lý lỗi phù hợp với môi trường (Development / Production).
 */

function loadEnv(string $filePath): void {
    if (!file_exists($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Bỏ qua các dòng ghi chú bắt đầu bằng dấu #
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        // Tách tên biến và giá trị theo dấu '=' đầu tiên
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            // Loại bỏ dấu nháy kép hoặc nháy đơn ở hai đầu nếu có
            $value = trim($value, "\"'");

            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Nạp file .env ở thư mục gốc của dự án (nếu chưa có thì dùng .env.example)
$rootDir = dirname(__DIR__);
if (file_exists($rootDir . '/.env')) {
    loadEnv($rootDir . '/.env');
} else {
    loadEnv($rootDir . '/.env.example');
}

// Thiết lập chế độ báo lỗi dựa vào môi trường (APP_ENV)
$appEnv = $_ENV['APP_ENV'] ?? 'production';
$appDebug = filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

if ($appEnv === 'production' && !$appDebug) {
    // Trên máy chủ thật (Production): Tắt hiển thị lỗi ra màn hình để tránh lộ thông tin nhạy cảm
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);

    // Ghi lỗi ngầm vào file log trên máy chủ để quản trị viên kiểm tra
    $logDir = $rootDir . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    ini_set('log_errors', '1');
    ini_set('error_log', $logDir . '/app_error.log');
} else {
    // Trên máy cá nhân (Development): Bật hiển thị lỗi để dễ sửa lỗi (Debug)
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}
