<?php
/**
 * File: api/tasks.php
 * Nhiệm vụ: Cung cấp API quản lý Danh sách hạng mục triển khai (Deployment Checklist)
 * Hỗ trợ: Xem (GET), Thêm mới (POST), Khôi phục mẫu (POST action=reset), Đổi trạng thái (PUT), Xóa (DELETE).
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Danh sách 6 hạng mục triển khai chuẩn ban đầu
function getDefaultSampleTasks(): array {
    return [
        ['id' => 1, 'title' => 'Cài đặt Web Server (Apache/Nginx) và PHP 8.2 trên máy chủ Ubuntu', 'category' => 'Server Setup', 'status' => 'completed'],
        ['id' => 2, 'title' => 'Tạo Database MySQL và cấp quyền tối thiểu cho tài khoản deploy_user', 'category' => 'Database', 'status' => 'completed'],
        ['id' => 3, 'title' => 'Trỏ bản ghi DNS (A Record) từ tên miền về địa chỉ IP Public của máy chủ', 'category' => 'Domain & HTTPS', 'status' => 'completed'],
        ['id' => 4, 'title' => 'Cài đặt chứng chỉ số SSL/TLS miễn phí Let\'s Encrypt bằng công cụ Certbot', 'category' => 'Domain & HTTPS', 'status' => 'completed'],
        ['id' => 5, 'title' => 'Cấu hình file .env và tắt hiển thị lỗi chi tiết (display_errors = 0)', 'category' => 'Security & Optimization', 'status' => 'in_progress'],
        ['id' => 6, 'title' => 'Thiết lập tự động sao lưu cơ sở dữ liệu (mysqldump) mỗi ngày lúc 02:00 sáng', 'category' => 'Database', 'status' => 'pending']
    ];
}

$fallbackFile = __DIR__ . '/../logs/fallback_tasks.json';
function getFallbackTasks(string $file): array {
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true) ?: [];
    }
    $default = getDefaultSampleTasks();
    @mkdir(dirname($file), 0755, true);
    file_put_contents($file, json_encode($default, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    return $default;
}

function saveFallbackTasks(string $file, array $tasks): void {
    @mkdir(dirname($file), 0755, true);
    file_put_contents($file, json_encode(array_values($tasks), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

try {
    $pdo = getDatabaseConnection();

    if ($method === 'GET') {
        $stmt = $pdo->query('SELECT id, title, category, status, created_at FROM deployment_tasks ORDER BY id ASC');
        $tasks = $stmt->fetchAll();
        echo json_encode(['success' => true, 'source' => 'MySQL Database', 'data' => $tasks], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST') {
        // Kiểm tra nếu người dùng bấm nút "Khôi phục 6 bước mẫu" (Gợi ý 5)
        if (($input['action'] ?? '') === 'reset') {
            $pdo->exec('TRUNCATE TABLE deployment_tasks');
            $stmt = $pdo->prepare('INSERT INTO deployment_tasks (title, category, status) VALUES (:title, :category, :status)');
            foreach (getDefaultSampleTasks() as $item) {
                $stmt->execute([
                    ':title' => $item['title'],
                    ':category' => $item['category'],
                    ':status' => $item['status']
                ]);
            }
            echo json_encode(['success' => true, 'message' => 'Đã khôi phục 6 bước triển khai mẫu thành công!'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $title = trim($input['title'] ?? '');
        $category = $input['category'] ?? 'Server Setup';

        if ($title === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Tên hạng mục không được để trống']);
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO deployment_tasks (title, category, status) VALUES (:title, :category, :status)');
        $stmt->execute([
            ':title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
            ':category' => $category,
            ':status' => 'pending'
        ]);

        echo json_encode(['success' => true, 'message' => 'Đã thêm hạng mục triển khai mới', 'id' => $pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'PUT') {
        $id = (int)($input['id'] ?? 0);
        $status = $input['status'] ?? 'pending';

        $stmt = $pdo->prepare('UPDATE deployment_tasks SET status = :status WHERE id = :id');
        $stmt->execute([':status' => $status, ':id' => $id]);

        echo json_encode(['success' => true, 'message' => 'Đã cập nhật trạng thái hạng mục'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'DELETE') {
        $id = (int)($input['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM deployment_tasks WHERE id = :id');
        $stmt->execute([':id' => $id]);

        echo json_encode(['success' => true, 'message' => 'Đã xóa hạng mục'], JSON_UNESCAPED_UNICODE);
        exit;
    }

} catch (PDOException $e) {
    error_log('Database Error in tasks.php: ' . $e->getMessage());
    $tasks = getFallbackTasks($fallbackFile);

    if ($method === 'POST') {
        if (($input['action'] ?? '') === 'reset') {
            $default = getDefaultSampleTasks();
            saveFallbackTasks($fallbackFile, $default);
            echo json_encode(['success' => true, 'source' => 'Fallback Storage', 'message' => 'Đã khôi phục 6 bước mẫu!'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $title = trim($input['title'] ?? '');
        $category = $input['category'] ?? 'Server Setup';
        $nextId = count($tasks) > 0 ? max(array_column($tasks, 'id')) + 1 : 1;
        $tasks[] = [
            'id' => $nextId,
            'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
            'category' => $category,
            'status' => 'pending'
        ];
        saveFallbackTasks($fallbackFile, $tasks);
        echo json_encode(['success' => true, 'source' => 'Fallback Storage', 'id' => $nextId], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'PUT') {
        $id = (int)($input['id'] ?? 0);
        $status = $input['status'] ?? 'pending';
        foreach ($tasks as &$t) {
            if ((int)$t['id'] === $id) {
                $t['status'] = $status;
                break;
            }
        }
        saveFallbackTasks($fallbackFile, $tasks);
        echo json_encode(['success' => true, 'source' => 'Fallback Storage'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'DELETE') {
        $id = (int)($input['id'] ?? 0);
        $tasks = array_filter($tasks, fn($t) => (int)$t['id'] !== $id);
        saveFallbackTasks($fallbackFile, $tasks);
        echo json_encode(['success' => true, 'source' => 'Fallback Storage'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['success' => true, 'source' => 'Fallback Storage', 'data' => $tasks], JSON_UNESCAPED_UNICODE);
}
