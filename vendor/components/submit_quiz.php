<?php
require_once "../functions/config/database.php";
require_once "../functions/includes/functions.php";

checkLogin();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit(json_encode(["success" => false, "error" => "Invalid request method"]));
}

$quiz_id = (int) $_POST["quiz_id"];
$user_id = $_SESSION["user_id"];

try {
    $pdo->beginTransaction();
    
    // Проверяем существование пользователя
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    if (!$stmt->fetch()) {
        throw new Exception("Сессия истекла. Пожалуйста, войдите снова.");
    }

    // Проверяем существование теста
    $stmt = $pdo->prepare("SELECT id FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    if (!$stmt->fetch()) {
        throw new Exception("Тест не найден");
    }

    // Проверяем таймаут между попытками
    $stmt = $pdo->prepare("
        SELECT completed_at 
        FROM user_results 
        WHERE user_id = ? AND quiz_id = ?
        ORDER BY completed_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$user_id, $quiz_id]);
    $last_attempt = $stmt->fetch();

    if ($last_attempt) {
        $cooldown_ends = strtotime($last_attempt['completed_at']) + (5 * 60);
        if (time() < $cooldown_ends) {
            throw new Exception("Пожалуйста, подождите перед следующей попыткой");
        }
    }

    // Проверяем, нет ли уже существующей попытки для этого теста
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM user_results 
        WHERE user_id = ? AND quiz_id = ? AND completed_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
    ");
    $stmt->execute([$user_id, $quiz_id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Попытка уже была отправлена");
    }

    // Создаем запись о попытке
    $stmt = $pdo->prepare("
        INSERT INTO user_results (user_id, quiz_id, score, completed_at)
        VALUES (?, ?, 0, NOW())
    ");
    $stmt->execute([$user_id, $quiz_id]);
    $attempt_id = $pdo->lastInsertId();

    // Получаем все вопросы теста
    $stmt = $pdo->prepare("SELECT id, type FROM questions WHERE quiz_id = ?");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_questions = count($questions);

    $score = 0;
    $processed_questions = [];

    // Обрабатываем ответы пользователя
    foreach ($_POST as $key => $value) {
        if (strpos($key, "question_") === 0) {
            $question_id = (int) substr($key, 9);

            // Проверяем, не обработан ли уже этот вопрос
            if (in_array($question_id, $processed_questions)) {
                continue;
            }
            $processed_questions[] = $question_id;

            // Получаем тип вопроса из массива вопросов
            $question_type = null;
            foreach ($questions as $q) {
                if ($q['id'] === $question_id) {
                    $question_type = $q['type'];
                    break;
                }
            }

            if ($question_type === "multiple") {
                $answer_ids = is_array($value) ? array_map('intval', $value) : [intval($value)];
                
                // Получаем правильные ответы
                $stmt = $pdo->prepare("SELECT id FROM answers WHERE question_id = ? AND is_correct = 1");
                $stmt->execute([$question_id]);
                $correct_answers = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Проверяем точное совпадение выбранных и правильных ответов
                $is_correct = count(array_diff($answer_ids, $correct_answers)) === 0 && 
                             count(array_diff($correct_answers, $answer_ids)) === 0;
                
                if ($is_correct) {
                    $score++;
                }
                
                // Сохраняем ответы
                foreach ($answer_ids as $answer_id) {
                    $stmt = $pdo->prepare("INSERT INTO user_answers (attempt_id, user_id, question_id, answer_id, is_correct) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$attempt_id, $user_id, $question_id, $answer_id, $is_correct ? 1 : 0]);
                }
            } else {
                $answer_id = (int) $value;
                
                // Проверяем правильность ответа
                $stmt = $pdo->prepare("SELECT is_correct FROM answers WHERE id = ? AND question_id = ?");
                $stmt->execute([$answer_id, $question_id]);
                $is_correct = (bool) $stmt->fetchColumn();
                
                if ($is_correct) {
                    $score++;
                }
                
                // Сохраняем ответ
                $stmt = $pdo->prepare("INSERT INTO user_answers (attempt_id, user_id, question_id, answer_id, is_correct) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$attempt_id, $user_id, $question_id, $answer_id, $is_correct ? 1 : 0]);
            }
        }
    }

    // Вычисляем процент правильных ответов
    $percentage = ($score / $total_questions) * 100;
    
    // Обновляем результат
    $stmt = $pdo->prepare("UPDATE user_results SET score = ? WHERE id = ?");
    $stmt->execute([$percentage, $attempt_id]);

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "attempt_id" => $attempt_id,
        "score" => $percentage,
        "total_questions" => $total_questions
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Quiz submission error: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
    exit;
}
