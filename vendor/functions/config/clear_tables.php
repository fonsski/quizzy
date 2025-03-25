<?php
require_once "database.php";
require_once "migrates.php";

try {
    // Выбираем базу данных
    $pdo->exec("USE " . DB_NAME);
    clearAllTables($pdo);
    echo "Очистка завершена успешно";
} catch (PDOException $e) {
    echo "Ошибка очистки: " . $e->getMessage();
}
