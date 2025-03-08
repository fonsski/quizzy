<?php
require_once "../functions/config/database.php";
require_once "../functions/includes/functions.php";

checkLogin();
if (!isAdmin()) {
    header('Location: /vendor/components/index.php');
    exit();
}

$attempt_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем информацию о попытке
$stmt = $pdo->prepare("
    SELECT ur.*, 
           u.username,
           q.title AS quiz_title,
           q.description AS quiz_description
    FROM user_results ur
    JOIN users u ON ur.user_id = u.id
    JOIN quizzes q ON ur.quiz_id = q.id
    WHERE ur.id = ?
");
$stmt->execute([$attempt_id]);
$attempt = $stmt->fetch();

if (!$attempt) {
    header('Location: manage_quizzes.php');
    exit();
}

// Получаем ответы на вопросы
$stmtAnswers = $pdo->prepare("
    SELECT q.question_text, 
           a.answer_text, 
           ua.is_correct 
    FROM user_answers ua
    JOIN questions q ON ua.question_id = q.id
    JOIN answers a ON ua.answer_id = a.id
    WHERE ua.attempt_id = ?
");
$stmtAnswers->execute([$attempt_id]);
$answers = $stmtAnswers->fetchAll();

include "../templates/header.php";
?>

<div class="container">
    <div class="card">
        <div class="header-actions" style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Детали попытки</h2>
            <a href="view_results.php?quiz_id=<?= $attempt['quiz_id'] ?>" class="btn">Назад к результатам</a>
        </div>

        <div class="attempt-info card" style="margin: 20px 0;">
            <h3><?= htmlspecialchars($attempt['quiz_title']) ?></h3>
            <p>Пользователь: <?= htmlspecialchars($attempt['username']) ?></p>
            <p>Баллы: <?= $attempt['score'] ?></p>
            <p>Завершено: <?= date('F j, Y, g:i a', strtotime($attempt['completed_at'])) ?></p>
        </div>

        <div class="answers-info card" style="margin: 20px 0;">
            <h3>Ответы</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Вопрос</th>
                        <th>Ответ</th>
                        <th>Правильность</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($answers as $answer): ?>
                    <tr>
                        <td><?= htmlspecialchars($answer['question_text']) ?></td>
                        <td><?= htmlspecialchars($answer['answer_text']) ?></td>
                        <td><?= $answer['is_correct'] ? 'Да' : 'Нет' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
