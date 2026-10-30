/**
 * File: assets/js/app.js
 * Nhiệm vụ: Giao tiếp bất đồng bộ (AJAX / Fetch API) với Backend PHP,
 * kiểm tra thư viện máy chủ, lọc/tìm kiếm công việc, khôi phục mẫu và tạo file cấu hình .env / .htaccess.
 */

let allTasks = [];
let currentCategoryFilter = 'ALL';
let currentSearchKeyword = '';

document.addEventListener('DOMContentLoaded', () => {
    checkServerHealth();
    loadTasks();
    generateDeploymentConfigs();

    // Sự kiện kiểm tra lại máy chủ
    document.getElementById('btn-refresh-health').addEventListener('click', checkServerHealth);

    // Sự kiện thêm hạng mục mới
    document.getElementById('task-form').addEventListener('submit', handleAddTask);

    // Sự kiện khôi phục 6 bước mẫu ban đầu (Gợi ý 5)
    document.getElementById('btn-reset-tasks').addEventListener('click', handleResetTasks);

    // Sự kiện lọc theo nhóm hạng mục (Gợi ý 2)
    document.querySelectorAll('#filter-tabs .filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#filter-tabs .filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentCategoryFilter = btn.getAttribute('data-category');
            renderTasksTable();
        });
    });

    // Sự kiện tìm kiếm nhanh theo từ khóa (Gợi ý 2)
    document.getElementById('search-input').addEventListener('input', (e) => {
        currentSearchKeyword = e.target.value.trim().toLowerCase();
        renderTasksTable();
    });

    // Sự kiện tạo nhanh file .env & .htaccess (Gợi ý 4)
    document.getElementById('btn-generate-config').addEventListener('click', generateDeploymentConfigs);
});

// 1. Gọi API kiểm tra trạng thái máy chủ, HTTPS & các thư viện PHP (Gợi ý 3)
async function checkServerHealth() {
    try {
        const res = await fetch('api/health.php');
        const data = await res.json();

        document.getElementById('env-val').textContent =
            data.environment === 'production' ? 'Production (Máy chủ thực tế)' : 'Development (Máy cá nhân)';
        document.getElementById('https-val').textContent = data.protocol;
        document.getElementById('db-val').textContent = data.database.message;
        document.getElementById('php-val').textContent = `PHP ${data.php_version} (${data.server_software})`;

        // Hiển thị danh sách kiểm tra các thư viện PHP (pdo_mysql, openssl, mbstring, curl, memory_limit...)
        const extGrid = document.getElementById('ext-grid');
        extGrid.innerHTML = '';

        if (data.server_checks) {
            Object.values(data.server_checks).forEach(check => {
                const div = document.createElement('div');
                div.className = 'ext-badge';
                const badgeClass = check.passed ? 'ext-ok' : 'ext-warn';
                const icon = check.passed ? '✔' : '⚠';
                div.innerHTML = `
                    <span>${check.label}</span>
                    <span class="${badgeClass}">${icon} ${check.value}</span>
                `;
                extGrid.appendChild(div);
            });
        }
    } catch (error) {
        console.error('Lỗi khi kiểm tra máy chủ:', error);
    }
}

// 2. Tải danh sách hạng mục triển khai từ PHP Backend (api/tasks.php)
async function loadTasks() {
    try {
        const res = await fetch('api/tasks.php');
        const result = await res.json();
        allTasks = result.data || [];
        renderTasksTable();
    } catch (error) {
        console.error('Lỗi khi tải danh sách công việc:', error);
    }
}

// 3. Hiển thị bảng công việc kết hợp Bộ lọc & Tìm kiếm (Gợi ý 2)
function renderTasksTable() {
    const tbody = document.getElementById('task-list');
    tbody.innerHTML = '';

    // Tính tổng tiến độ trên toàn bộ danh sách
    const totalCompleted = allTasks.filter(t => t.status === 'completed').length;
    const percent = allTasks.length > 0 ? Math.round((totalCompleted / allTasks.length) * 100) : 0;
    document.getElementById('progress-badge').textContent =
        `Hoàn thành: ${totalCompleted}/${allTasks.length} (${percent}%)`;

    // Lọc theo nhóm hạng mục và từ khóa tìm kiếm
    const filtered = allTasks.filter(task => {
        const matchCategory = (currentCategoryFilter === 'ALL') || (task.category === currentCategoryFilter);
        const matchKeyword = !currentSearchKeyword ||
            task.title.toLowerCase().includes(currentSearchKeyword) ||
            task.category.toLowerCase().includes(currentSearchKeyword);
        return matchCategory && matchKeyword;
    });

    if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center">Không tìm thấy hạng mục nào phù hợp.</td></tr>`;
        return;
    }

    const statusLabels = {
        'completed': 'Đã hoàn thành',
        'in_progress': 'Đang thực hiện',
        'pending': 'Chờ xử lý'
    };

    filtered.forEach(task => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>#${task.id}</td>
            <td>${task.title}</td>
            <td><strong>${task.category}</strong></td>
            <td><span class="status-badge status-${task.status}">${statusLabels[task.status] || task.status}</span></td>
            <td class="action-buttons">
                <button class="btn btn-outline" onclick="toggleStatus(${task.id}, '${task.status}')">Đổi trạng thái</button>
                <button class="btn btn-danger" onclick="deleteTask(${task.id})">Xóa</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// 4. Thêm hạng mục triển khai mới (POST request)
async function handleAddTask(event) {
    event.preventDefault();
    const titleInput = document.getElementById('task-title');
    const categorySelect = document.getElementById('task-category');

    const payload = {
        title: titleInput.value.trim(),
        category: categorySelect.value
    };

    if (!payload.title) return;

    await fetch('api/tasks.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    titleInput.value = '';
    loadTasks();
}

// 5. Chuyển đổi trạng thái công việc (PUT request)
async function toggleStatus(id, currentStatus) {
    const nextStatus = currentStatus === 'pending'
        ? 'in_progress'
        : (currentStatus === 'in_progress' ? 'completed' : 'pending');

    await fetch('api/tasks.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status: nextStatus })
    });

    loadTasks();
}

// 6. Xóa hạng mục triển khai (DELETE request)
async function deleteTask(id) {
    if (!confirm(`Bạn có chắc chắn muốn xóa hạng mục #${id} không?`)) {
        return;
    }

    await fetch('api/tasks.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    });

    loadTasks();
}

// 7. Khôi phục 6 bước triển khai mẫu ban đầu (Gợi ý 5)
async function handleResetTasks() {
    if (!confirm('Khôi phục lại 6 hạng mục triển khai mẫu ban đầu?')) {
        return;
    }

    await fetch('api/tasks.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'reset' })
    });

    currentCategoryFilter = 'ALL';
    currentSearchKeyword = '';
    document.getElementById('search-input').value = '';
    document.querySelectorAll('#filter-tabs .filter-btn').forEach((b, idx) => {
        b.classList.toggle('active', idx === 0);
    });

    loadTasks();
}

// 8. Tạo nhanh nội dung file .env và .htaccess (Gợi ý 4)
function generateDeploymentConfigs() {
    const domain = document.getElementById('gen-domain').value.trim() || 'https://deploytrack-demo.id.vn';
    const env = document.getElementById('gen-env').value;
    const dbHost = document.getElementById('gen-dbhost').value.trim() || '127.0.0.1';
    const dbName = document.getElementById('gen-dbname').value.trim() || 'deploytrack_db';
    const dbUser = document.getElementById('gen-dbuser').value.trim() || 'deploy_user';
    const dbPass = document.getElementById('gen-dbpass').value.trim();
    const forceHttps = document.getElementById('gen-https').checked;
    const isDebug = env === 'development' ? 'true' : 'false';

    const envContent =
`# File .env tạo tự động cho môi trường ${env.toUpperCase()}
APP_NAME="DeployTrack - Hệ thống Quản lý Triển khai Web"
APP_ENV=${env}
APP_DEBUG=${isDebug}
APP_URL=${domain}
FORCE_HTTPS=${forceHttps}

# Thông số kết nối MySQL (PDO)
DB_HOST=${dbHost}
DB_PORT=3306
DB_NAME=${dbName}
DB_USER=${dbUser}
DB_PASS=${dbPass}`;

    let htaccessContent =
`# File .htaccess cấu hình bảo mật Apache / Shared Hosting
Options -Indexes\n`;

    if (forceHttps) {
        htaccessContent += `
# Tự động chuyển hướng HTTP sang HTTPS (301 Redirect)
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} !=on
    RewriteCond %{HTTP_HOST} !^localhost [NC]
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>\n`;
    }

    htaccessContent += `
# Chặn người lạ truy cập trực tiếp vào file .env, .sql, .log
<FilesMatch "^\\.env|schema\\.sql|\\.log$">
    Order allow,deny
    Deny from all
</FilesMatch>`;

    document.getElementById('env-output').textContent = envContent;
    document.getElementById('htaccess-output').textContent = htaccessContent;
}

// Sao chép nhanh nội dung cấu hình vào Clipboard
function copyCode(elementId) {
    const text = document.getElementById(elementId).textContent;
    navigator.clipboard.writeText(text).then(() => {
        alert('Đã sao chép nội dung cấu hình vào bộ nhớ tạm!');
    });
}
