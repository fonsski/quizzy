<!DOCTYPE html>
<html lang="ru" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="/assets/js/theme.js"></script>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <a href="/" class="navbar-brand">Quizzy</a>
            <div style="display: flex; align-items: center; gap: 24px;">
                <ul class="nav-links">
                    <?php if (isset($_SESSION["user_id"])): ?>
                        <li><a href="/vendor/components/quizzes.php">Тесты</a></li>
                        <li><a href="/vendor/components/results.php">Мои результаты</a></li>
                        <?php if (
                            isset($_SESSION["role"]) &&
                            $_SESSION["role"] === "admin"
                        ): ?>
                            <li><a href="/vendor/admin/index.php" class="admin-link">Админ-панель</a></li>
                        <?php endif; ?>
                        <li><a href="/vendor/components/logout.php">Выйти</a></li>
                    <?php else: ?>
                        <li><a href="/vendor/components/login.php">Войти</a></li>
                        <li><a href="/vendor/components/register.php">Зарегистрироваться</a></li>
                    <?php endif; ?>
                </ul>
                <button id="theme-toggle" class="theme-toggle" aria-label="Toggle theme">
                    <span id="theme-icon"></span>
                </button>
            </div>
        </div>
    </nav>