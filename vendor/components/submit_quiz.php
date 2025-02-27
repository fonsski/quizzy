<?php
require_once "../functions/config/database.php";
require_once "../functions/includes/functions.php";

checkLogin();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit(
        json_encode(["success" => false, "error" => "Invalid request method"])
    );
}

$quiz_id = (int) $_POST["quiz_id"];
$user_id = $_SESSION["user_id"];

try {
    $pdo->beginTransaction();

    // Создаем запись о попытке
    $stmt = $pdo->prepare("
        INSERT INTO user_results (user_id, quiz_id, score, completed_at)
        VALUES (?, ?, 0, NOW())
    ");
    $stmt->execute([$user_id, $quiz_id]);
    $attempt_id = $pdo->lastInsertId();

    // Получаем все вопросы теста для подсчета общего количества
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE quiz_id = ?");
    $stmt->execute([$quiz_id]);
    $total_questions = $stmt->fetchColumn();

    $score = 0;

    // Обрабатываем ответы пользователя
    foreach ($_POST as $key => $value) {
        if (strpos($key, "question_") === 0) {
            $question_id = (int) substr($key, 9);

            // Получаем тип вопроса
            $stmt = $pdo->prepare("SELECT type FROM questions WHERE id = ?");
            $stmt->execute([$question_id]);
            $question_type = $stmt->fetchColumn();

            // Для вопросов с множественным выбором
            if ($question_type === "multiple") {
                $answers = is_array($value) ? $value : [$value];
                $correct_count = 0;
                $total_correct = 0;

                // Считаем общее количество правильных ответов для этого вопроса
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM answers WHERE question_id = ? AND is_correct = 1"
                );
                $stmt->execute([$question_id]);
                $total_correct = $stmt->fetchColumn();

                foreach ($answers as $answer_id) {
                    // Сохраняем ответ пользователя
                    $stmt = $pdo->prepare("
                        INSERT INTO user_answers (attempt_id, user_id, question_id, answer_id, is_correct)
                        SELECT ?, ?, ?, ?, is_correct
                        FROM answers
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $attempt_id,
                        $user_id,
                        $question_id,
                        $answer_id,
                        $answer_id,
                    ]);

                    // Проверяем, правильный ли это ответ
                    $stmt = $pdo->prepare(
                        "SELECT is_correct FROM answers WHERE id = ?"
                    );
                    $stmt->execute([$answer_id]);
                    if ($stmt->fetchColumn()) {
                        $correct_count++;
                    }
                }

                // Если все ответы правильные, добавляем балл
                if ($correct_count === $total_correct) {
                    $score++;
                }
            }
            // Для вопросов с одним ответом
            else {
                $answer_id = (int) $value;

                // Сохраняем ответ пользователя
                $stmt = $pdo->prepare("
                    INSERT INTO user_answers (attempt_id, user_id, question_id, answer_id, is_correct)
                    SELECT ?, ?, ?, ?, is_correct
                    FROM answers
                    WHERE id = ?
                ");
                $stmt->execute([
                    $attempt_id,
                    $user_id,
                    $question_id,
                    $answer_id,
                    $answer_id,
                ]);

                // Проверяем, правильный ли это ответ
                $stmt = $pdo->prepare(
                    "SELECT is_correct FROM answers WHERE id = ?"
                );
                $stmt->execute([$answer_id]);
                if ($stmt->fetchColumn()) {
                    $score++;
                }
            }
        }
    }

    // Обновляем итоговый счет
    $stmt = $pdo->prepare("UPDATE user_results SET score = ? WHERE id = ?");
    $stmt->execute([$score, $attempt_id]);

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "attempt_id" => $attempt_id,
        "score" => $score,
        "total_questions" => $total_questions,
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log($e->getMessage());
    echo json_encode([
        "success" => false,
        "error" => "An error occurred while saving the quiz results",
    ]);
}
