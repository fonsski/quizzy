<?php
session_start();

function checkLogin() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function getQuizzes() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM quizzes ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserResults($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM user_results WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>