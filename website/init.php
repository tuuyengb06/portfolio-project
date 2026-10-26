<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/config.php';

$pdo = db();

$pdo->exec("
    CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS profile (
        id INT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        headline VARCHAR(200) NOT NULL,
        about TEXT NOT NULL,
        email VARCHAR(150) NOT NULL,
        phone VARCHAR(30) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kind ENUM('skill', 'project') NOT NULL,
        title VARCHAR(150) NOT NULL,
        description TEXT NOT NULL,
        link VARCHAR(500) NOT NULL DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
    INSERT IGNORE INTO profile
        (id, full_name, headline, about, email, phone)
    VALUES (
        1,
        'Tên của bạn',
        'Sinh viên Công nghệ thông tin',
        'Tôi yêu thích lập trình web và triển khai hệ thống bằng Docker.',
        'yourname@example.com',
        '0900000000'
    )
");

if ((int) $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn() === 0) {
    $stmt = $pdo->prepare("
        INSERT INTO items (kind, title, description, link)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        'skill',
        'PHP và MySQL',
        'Xây dựng website động và quản lý dữ liệu.',
        ''
    ]);

    $stmt->execute([
        'project',
        'Website Portfolio',
        'Website giới thiệu cá nhân có trang quản trị nội dung.',
        ''
    ]);
}

$password = stream_get_contents(STDIN);
$password = rtrim($password, "\r\n");

if (strlen($password) < 12) {
    exit("Mật khẩu admin phải có ít nhất 12 ký tự.\n");
}

$exists = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
$exists->execute(['admin']);

if (!$exists->fetch()) {
    $stmt = $pdo->prepare("
        INSERT INTO admins (username, password_hash)
        VALUES (?, ?)
    ");

    $stmt->execute([
        'admin',
        password_hash($password, PASSWORD_DEFAULT)
    ]);

    echo "Đã tạo tài khoản admin.\n";
} else {
    echo "Tài khoản admin đã tồn tại; giữ nguyên mật khẩu.\n";
}

echo "Đã chuẩn bị database.\n";