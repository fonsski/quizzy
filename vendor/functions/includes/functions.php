<?php
// Удаляем session_start() отсюда, так как он должен быть только в точке входа

function checkLogin() {
    // Убираем дублирующий session_start()
    if (!isset($_SESSION['user_id'])) {
        header('Location: /vendor/components/login.php');
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function sanitizeInput($data) {
    if(is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function checkQuizAccess($pdo, $quiz_id, $user_id) {
    $stmt = $pdo->prepare("SELECT created_by FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();
    
    return $quiz && ($quiz['created_by'] == $user_id || isAdmin());
}

function checkAttemptAccess($pdo, $attempt_id, $user_id) {
    $stmt = $pdo->prepare("SELECT user_id FROM user_results WHERE id = ?");
    $stmt->execute([$attempt_id]);
    $attempt = $stmt->fetch();
    
    return $attempt && ($attempt['user_id'] == $user_id || isAdmin());
}

function getQuizzes() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM quizzes ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting quizzes: " . $e->getMessage());
        return [];
    }
}

function getUserResults($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT ur.*, q.title as quiz_title 
            FROM user_results ur
            JOIN quizzes q ON ur.quiz_id = q.id 
            WHERE ur.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting user results: " . $e->getMessage());
        return [];
    }
}
?>