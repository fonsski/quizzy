<?php
require_once __DIR__ . '/config.php';

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'quiz_system');

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        PDO::ATTR_PERSISTENT => false // Отключаем постоянное соединение для безопасности
    ];

    $dsn = sprintf("mysql:host=%s;dbname=%s;charset=utf8mb4", DB_HOST, DB_NAME);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Ошибка подключения к базе данных. Пожалуйста, попробуйте позже.");
}

function closeConnection() {
    global $pdo;
    if ($pdo) {
        $pdo = null;
    }
}

register_shutdown_function('closeConnection');
?>