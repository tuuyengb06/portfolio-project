<?php
require __DIR__ . '/config.php';

header('Cache-Control: no-store');

$error = '';
$message = '';

try {
    $pdo = db();
} catch (Throwable $exception) {
    error_log('admin_database_error: ' . $exception->getMessage());
    http_response_code(503);
    exit('Không kết nối được cơ sở dữ liệu.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'login') {
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            $stmt = $pdo->prepare(
                "SELECT * FROM admins WHERE username = ?"
            );
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if (
                !$admin ||
                !password_verify($password, $admin['password_hash'])
            ) {
                error_log('admin_login_failed');
                throw new RuntimeException(
                    'Tên đăng nhập hoặc mật khẩu không đúng.'
                );
            }

            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['csrf'] = bin2hex(random_bytes(32));

            error_log('admin_login_success');

            header('Location: /admin.php');
            exit;
        }

        if (empty($_SESSION['admin_id'])) {
            throw new RuntimeException('Bạn cần đăng nhập.');
        }

        if ($action === 'logout') {
            $_SESSION = [];
            session_destroy();

            header('Location: /admin.php');
            exit;
        }

        if ($action === 'profile') {
            $name = trim((string) ($_POST['full_name'] ?? ''));
            $headline = trim((string) ($_POST['headline'] ?? ''));
            $about = trim((string) ($_POST['about'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $phone = trim((string) ($_POST['phone'] ?? ''));

            if ($name === '' || strlen($name) > 100) {
                throw new RuntimeException('Kiểm tra lại họ tên.');
            }

            if ($headline === '' || strlen($headline) > 200) {
                throw new RuntimeException('Kiểm tra lại tiêu đề.');
            }

            if ($about === '' || strlen($about) > 20000) {
                throw new RuntimeException('Kiểm tra lại giới thiệu.');
            }

            if (
                !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                strlen($email) > 150
            ) {
                throw new RuntimeException('Email không hợp lệ.');
            }

            if (!preg_match('/^(03|05|07|08|09)[0-9]{8}$/', $phone)) {
                throw new RuntimeException(
                    'Số điện thoại cần 10 số, đầu 03/05/07/08/09.'
                );
            }

            $stmt = $pdo->prepare("
                UPDATE profile
                SET full_name = ?, headline = ?, about = ?,
                    email = ?, phone = ?
                WHERE id = 1
            ");

            $stmt->execute([
                $name, $headline, $about, $email, $phone
            ]);

            error_log('profile_updated');
        } elseif ($action === 'save_item') {
            $id = (int) ($_POST['id'] ?? 0);
            $kind = (string) ($_POST['kind'] ?? '');
            $title = trim((string) ($_POST['title'] ?? ''));
            $description = trim(
                (string) ($_POST['description'] ?? '')
            );
            $link = trim((string) ($_POST['link'] ?? ''));

            if (!in_array($kind, ['skill', 'project'], true)) {
                throw new RuntimeException('Loại nội dung không hợp lệ.');
            }

            if ($title === '' || strlen($title) > 150) {
                throw new RuntimeException(
                    'Tiêu đề không được trống hoặc quá dài.'
                );
            }

            if ($description === '' || strlen($description) > 20000) {
                throw new RuntimeException('Kiểm tra lại mô tả.');
            }

            if ($link !== '') {
                $scheme = strtolower(
                    parse_url($link, PHP_URL_SCHEME) ?: ''
                );

                if (
                    strlen($link) > 500 ||
                    !filter_var($link, FILTER_VALIDATE_URL) ||
                    !in_array($scheme, ['http', 'https'], true)
                ) {
                    throw new RuntimeException(
                        'Liên kết phải bắt đầu bằng http:// hoặc https://.'
                    );
                }
            }

            if ($id > 0) {
                $stmt = $pdo->prepare("
                    UPDATE items
                    SET kind = ?, title = ?, description = ?, link = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $kind, $title, $description, $link, $id
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO items (kind, title, description, link)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $kind, $title, $description, $link
                ]);
            }

            error_log('content_saved');
        } elseif ($action === 'delete_item') {
            $id = (int) ($_POST['id'] ?? 0);

            $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
            $stmt->execute([$id]);

            error_log('content_deleted');
        } else {
            throw new RuntimeException('Thao tác không hợp lệ.');
        }

        header('Location: /admin.php?saved=1');
        exit;
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        error_log('admin_error: ' . $exception->getMessage());
        $error = 'Không lưu được dữ liệu. Hãy kiểm tra lại.';
    }
}

$isAdmin = !empty($_SESSION['admin_id']);
$profile = [];
$items = [];
$editing = null;

if ($isAdmin) {
    $profile = $pdo->query(
        "SELECT * FROM profile WHERE id = 1"
    )->fetch();

    $items = $pdo->query(
        "SELECT * FROM items ORDER BY kind, id DESC"
    )->fetchAll();

    $editId = (int) ($_GET['edit'] ?? 0);

    if ($editId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->execute([$editId]);
        $editing = $stmt->fetch() ?: null;
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản trị Portfolio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link href="/style.css" rel="stylesheet">
</head>
<body>
<main class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Quản trị Portfolio</h1>
        <a href="/">Xem website</a>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if (!$isAdmin): ?>
        <div class="content-card mx-auto" style="max-width: 460px">
            <h2 class="h4">Đăng nhập admin</h2>

            <form method="post">
                <input type="hidden" name="csrf"
                       value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="login">

                <label class="form-label" for="username">
                    Tên đăng nhập
                </label>
                <input class="form-control mb-3" id="username"
                       name="username" required
                       autocomplete="username">

                <label class="form-label" for="password">
                    Mật khẩu
                </label>
                <input class="form-control mb-3" id="password"
                       type="password" name="password" required
                       autocomplete="current-password">

                <button class="btn btn-purple" type="submit">
                    Đăng nhập
                </button>
            </form>
        </div>
    <?php else: ?>

        <?php if (isset($_GET['saved'])): ?>
            <div class="alert alert-success">
                Đã cập nhật dữ liệu.
            </div>
        <?php endif; ?>

        <form method="post" class="mb-4">
            <input type="hidden" name="csrf"
                   value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="logout">
            <button class="btn btn-outline-secondary" type="submit">
                Đăng xuất
            </button>
        </form>

        <section class="content-card mb-4">
            <h2 class="h4">Thông tin cá nhân</h2>

            <form method="post">
                <input type="hidden" name="csrf"
                       value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="profile">

                <label class="form-label">Họ tên</label>
                <input class="form-control mb-3" name="full_name"
                       value="<?= e($profile['full_name']) ?>"
                       required maxlength="100">

                <label class="form-label">Tiêu đề giới thiệu</label>
                <input class="form-control mb-3" name="headline"
                       value="<?= e($profile['headline']) ?>"
                       required maxlength="200">

                <label class="form-label">Giới thiệu</label>
                <textarea class="form-control mb-3"
                          name="about" required><?= e($profile['about']) ?></textarea>

                <label class="form-label">Email</label>
                <input class="form-control mb-3" name="email"
                       type="email" required
                       value="<?= e($profile['email']) ?>">

                <label class="form-label">Số điện thoại</label>
                <input class="form-control mb-3" name="phone"
                       required pattern="(03|05|07|08|09)[0-9]{8}"
                       value="<?= e($profile['phone']) ?>">

                <button class="btn btn-purple" type="submit">
                    Lưu thông tin
                </button>
            </form>
        </section>

        <section class="content-card mb-4">
            <h2 class="h4">
                <?= $editing ? 'Sửa nội dung' : 'Thêm kỹ năng / dự án' ?>
            </h2>

            <form method="post">
                <input type="hidden" name="csrf"
                       value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="save_item">
                <input type="hidden" name="id"
                       value="<?= (int) ($editing['id'] ?? 0) ?>">

                <label class="form-label">Loại nội dung</label>
                <select class="form-select mb-3" name="kind">
                    <option value="skill"
                        <?= ($editing['kind'] ?? '') === 'skill'
                            ? 'selected' : '' ?>>
                        Kỹ năng
                    </option>
                    <option value="project"
                        <?= ($editing['kind'] ?? '') === 'project'
                            ? 'selected' : '' ?>>
                        Dự án
                    </option>
                </select>

                <label class="form-label">Tiêu đề</label>
                <input class="form-control mb-3" name="title"
                       required maxlength="150"
                       value="<?= e($editing['title'] ?? '') ?>">

                <label class="form-label">Mô tả</label>
                <textarea class="form-control mb-3"
                          name="description"
                          required><?= e($editing['description'] ?? '') ?></textarea>

                <label class="form-label">
                    Liên kết dự án — có thể để trống
                </label>
                <input class="form-control mb-3" name="link"
                       type="url" maxlength="500"
                       value="<?= e($editing['link'] ?? '') ?>">

                <button class="btn btn-purple" type="submit">
                    Lưu nội dung
                </button>
                <a class="btn btn-outline-secondary" href="/admin.php">
                    Hủy / Thêm mới
                </a>
            </form>
        </section>

        <section class="content-card">
            <h2 class="h4">Danh sách nội dung</h2>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Loại</th>
                        <th>Tiêu đề</th>
                        <th>Thao tác</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <?= $item['kind'] === 'skill'
                                    ? 'Kỹ năng' : 'Dự án' ?>
                            </td>
                            <td><?= e($item['title']) ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a class="btn btn-sm btn-outline-primary"
                                       href="/admin.php?edit=<?= (int) $item['id'] ?>">
                                        Sửa
                                    </a>

                                    <form method="post"
                                          onsubmit="return confirm('Bạn muốn xóa nội dung này?')">
                                        <input type="hidden" name="csrf"
                                               value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action"
                                               value="delete_item">
                                        <input type="hidden" name="id"
                                               value="<?= (int) $item['id'] ?>">

                                        <button class="btn btn-sm btn-outline-danger"
                                                type="submit">
                                            Xóa
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</main>
</body>
</html>