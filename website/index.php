<?php
require __DIR__ . '/config.php';

try {
    $pdo = db();
    $profile = $pdo->query(
        "SELECT * FROM profile WHERE id = 1"
    )->fetch();

    if (!$profile) {
        throw new RuntimeException('Chưa có dữ liệu profile.');
    }

    $skills = $pdo->query(
        "SELECT * FROM items WHERE kind = 'skill' ORDER BY id DESC"
    )->fetchAll();

    $projects = $pdo->query(
        "SELECT * FROM items WHERE kind = 'project' ORDER BY id DESC"
    )->fetchAll();
} catch (Throwable $exception) {
    error_log('portfolio_error: ' . $exception->getMessage());
    http_response_code(503);
    exit('Website đang được chuẩn bị. Vui lòng thử lại sau.');
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($profile['full_name']) ?> | Portfolio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link href="/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand bg-white border-bottom sticky-top">
    <div class="container flex-wrap gap-2">
        <a class="navbar-brand fw-bold" href="/">Portfolio</a>
        <div class="d-flex flex-wrap gap-3">
            <a href="#about">Giới thiệu</a>
            <a href="#skills">Kỹ năng</a>
            <a href="#projects">Dự án</a>
            <a href="#contact">Liên hệ</a>
        </div>
    </div>
</nav>

<header class="hero">
    <div class="container">
        <p class="text-uppercase">Xin chào, mình là</p>
        <h1><?= e($profile['full_name']) ?></h1>
        <p class="lead"><?= e($profile['headline']) ?></p>
        <a class="btn btn-purple" href="#projects">Xem dự án</a>
    </div>
</header>

<main class="container py-5">
    <section id="about" class="mb-5">
        <h2>Giới thiệu</h2>
        <div class="content-card">
            <?= nl2br(e($profile['about'])) ?>
        </div>
    </section>

    <section id="skills" class="mb-5">
        <h2>Kỹ năng</h2>
        <div class="row g-3">
            <?php foreach ($skills as $item): ?>
                <div class="col-md-6">
                    <article class="content-card h-100">
                        <h3 class="h5"><?= e($item['title']) ?></h3>
                        <p class="mb-0">
                            <?= nl2br(e($item['description'])) ?>
                        </p>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="projects" class="mb-5">
        <h2>Dự án</h2>
        <div class="row g-3">
            <?php foreach ($projects as $item): ?>
                <div class="col-md-6">
                    <article class="content-card h-100">
                        <h3 class="h5"><?= e($item['title']) ?></h3>
                        <p><?= nl2br(e($item['description'])) ?></p>

                        <?php if (
                            $item['link'] !== '' &&
                            filter_var($item['link'], FILTER_VALIDATE_URL) &&
                            in_array(
                                strtolower(
                                    parse_url($item['link'], PHP_URL_SCHEME) ?: ''
                                ),
                                ['http', 'https'],
                                true
                            )
                        ): ?>
                            <a href="<?= e($item['link']) ?>"
                               target="_blank"
                               rel="noopener noreferrer">
                                Xem dự án →
                            </a>
                        <?php endif; ?>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="contact">
        <h2>Liên hệ</h2>
        <div class="content-card">
            <p>Email: <?= e($profile['email']) ?></p>
            <p class="mb-0">Điện thoại: <?= e($profile['phone']) ?></p>
        </div>
    </section>
</main>

<footer class="text-center py-4">
    © <?= date('Y') ?> <?= e($profile['full_name']) ?>
</footer>
</body>
</html>