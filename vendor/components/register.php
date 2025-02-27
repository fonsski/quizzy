<?php
require_once "../functions/config/database.php";
require_once "../functions/includes/functions.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $password]);
        
        $_SESSION['success'] = "Registration successful! Please login.";
        header('Location: login.php');
        exit();
    } catch (PDOException $e) {
        $error = "Email or username already exists";
    }
}

include "../templates/header.php";
?>

<div class="container">
    <div class="card" style="max-width: 500px; margin: 40px auto;">
        <h2>Регистрация</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Имя пользователя</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="email">Почта</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn">Зарегестрироваться</button>
        </form>
        
        <p style="margin-top: 20px;">
            Уже есть аккаунт? <a href="login.php">Войти</a>
        </p>
    </div>
</div>

<?php include "../templates/footer.php"; ?>