<?php
require_once "../functions/config/database.php";
require_once "../functions/includes/functions.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = sanitizeInput($_POST["email"]);
    $password = $_POST["password"];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["role"] = $user["role"];

        header("Location: /index.php");
        exit();
    } else {
        $error = "Неверный email или пароль";
    }
}

include "../templates/header.php";
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Добро пожаловать</h2>
                <p class="auth-subtitle">Войдите в свой аккаунт</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/vendor/components/login.php" class="auth-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-with-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        <input type="email" id="email" name="email" class="form-control" required placeholder="Введите email">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Пароль</label>
                    <div class="input-with-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <input type="password" id="password" name="password" class="form-control" required placeholder="Введите пароль">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <span>Войти</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </button>
            </form>

            <div class="auth-footer">
                <p>Нет аккаунта? <a href="register.php" class="link-primary">Зарегистрируйтесь</a></p>
            </div>
        </div>
    </div>
</div>

<?php include "/vendor/templates/footer.php"; ?>
