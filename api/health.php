<?php
/**
 * File: api/health.php
 * Nhiệm vụ: Kiểm tra sức khỏe máy chủ (Server Health Check) & các thư viện PHP cần thiết
 * khi triển khai ứng dụng lên môi trường thực tế (Production).
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

// 1. Kiểm tra kết nối đang dùng có được mã hóa HTTPS (SSL/TLS) hay không
$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
);

// 2. Kiểm tra kết nối Cơ sở dữ liệu MySQL
$dbConnected = false;
$dbMessage = 'Kết nối cơ sở dữ liệu MySQL thành công (PDO)';

try {
    $pdo = getDatabaseConnection();
    $pdo->query('SELECT 1');
    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $dbMessage = 'Chưa kết nối được MySQL (Đang dùng dữ liệu dự phòng)';
    error_log('Database Health Check Error: ' . $e->getMessage());
}

// 3. Kiểm tra các thư viện PHP (Extensions) và thông số cấu hình máy chủ (Gợi ý 3)
$serverChecks = [
    'pdo_mysql' => [
        'label' => 'Thư viện PDO MySQL',
        'passed' => extension_loaded('pdo_mysql'),
        'value' => extension_loaded('pdo_mysql') ? 'Đã bật (Sẵn sàng kết nối DB)' : 'Chưa bật'
    ],
    'openssl' => [
        'label' => 'Thư viện mã hóa OpenSSL',
        'passed' => extension_loaded('openssl'),
        'value' => extension_loaded('openssl') ? 'Đã bật (Hỗ trợ HTTPS/SSL)' : 'Chưa bật'
    ],
    'mbstring' => [
        'label' => 'Thư viện chuỗi đa byte (mbstring)',
        'passed' => extension_loaded('mbstring'),
        'value' => extension_loaded('mbstring') ? 'Đã bật (Hỗ trợ Tiếng Việt UTF-8)' : 'Chưa bật'
    ],
    'curl' => [
        'label' => 'Thư viện giao tiếp mạng cURL',
        'passed' => extension_loaded('curl'),
        'value' => extension_loaded('curl') ? 'Đã bật (Gọi API ngoài)' : 'Chưa bật'
    ],
    'memory_limit' => [
        'label' => 'Giới hạn RAM cho PHP (memory_limit)',
        'passed' => true,
        'value' => ini_get('memory_limit') ?: '128M'
    ],
    'upload_max_filesize' => [
        'label' => 'Dung lượng tải file tối đa (upload_max)',
        'passed' => true,
        'value' => ini_get('upload_max_filesize') ?: '2M'
    ],
    'display_errors' => [
        'label' => 'Ẩn lỗi nhạy cảm (display_errors)',
        'passed' => (ini_get('display_errors') === '0' || ini_get('display_errors') === ''),
        'value' => (ini_get('display_errors') === '0' || ini_get('display_errors') === '')
            ? 'Đã tắt (An toàn cho Production)'
            : 'Đang bật (Chế độ Debug)'
    ]
];

// 4. Tổng hợp kết quả trả về dạng JSON cho giao diện JavaScript
$response = [
    'status' => 'ok',
    'environment' => $_ENV['APP_ENV'] ?? 'production',
    'app_name' => $_ENV['APP_NAME'] ?? 'DeployTrack',
    'https_enabled' => $isHttps,
    'protocol' => $isHttps ? 'HTTPS (Đã mã hóa SSL/TLS)' : 'HTTP (Chưa mã hóa)',
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'PHP Built-in / Apache / Nginx',
    'php_version' => PHP_VERSION,
    'database' => [
        'connected' => $dbConnected,
        'message' => $dbMessage,
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1'
    ],
    'server_checks' => $serverChecks,
    'checked_at' => date('Y-m-d H:i:s')
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
