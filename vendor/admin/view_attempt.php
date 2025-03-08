<?php
require_once "../functions/config/database.php";
require_once "../functions/includes/functions.php";

checkLogin();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

$attempt_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

// Получаем информацию о попытке
$stmt = $pdo->prepare("
    SELECT ur.*,
           u.username,
           u.email,
           q.title AS quiz_title,
           q.description AS quiz_description,
           q.time_limit,
           (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as total_questions
    FROM user_results ur
    JOIN users u ON ur.user_id = u.id
    JOIN quizzes q ON ur.quiz_id = q.id
    WHERE ur.id = ?
");
$stmt->execute([$attempt_id]);
$attempt = $stmt->fetch();

if (!$attempt) {
    header("Location: manage_quizzes.php");
    exit();
}

// Получаем вопросы и ответы пользователя
$stmt = $pdo->prepare("
    SELECT q.id as question_id,
           q.question_text,
           q.type,
           GROUP_CONCAT(
               CONCAT_WS(':::',
                   a.id,
                   a.answer_text,
                   a.is_correct,
                   CASE WHEN ua.id IS NOT NULL THEN 1 ELSE 0 END
               )
               SEPARATOR '|||'
           ) as answers_data
    FROM questions q
    JOIN answers a ON q.id = a.question_id
    LEFT JOIN user_answers ua ON (
        ua.question_id = q.id
        AND ua.answer_id = a.id
        AND ua.attempt_id = ?
    )
    WHERE q.quiz_id = ?
    GROUP BY q.id, q.question_text, q.type
    ORDER BY q.id
");
$stmt->execute([$attempt_id, $attempt["quiz_id"]]);
$questions = $stmt->fetchAll();

include "../templates/header.php";
?>

<div class="container">
    <div class="attempt-review">
        <!-- Заголовок -->
        <div class="page-header">
            <div class="header-content">
                <h1>Просмотр попытки</h1>
                <p class="text-secondary">Детальный разбор ответов пользователя</p>
            </div>
            <div class="header-actions">
                <a href="view_results.php?quiz_id=<?= $attempt[
                    "quiz_id"
                ] ?>" class="btn btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    К результатам теста
                </a>
            </div>
        </div>

        <!-- Информация о попытке -->
        <div class="attempt-info-card">
            <div class="quiz-info">
                <h2><?= htmlspecialchars($attempt["quiz_title"]) ?></h2>
                <p class="quiz-description"><?= htmlspecialchars(
                    $attempt["quiz_description"]
                ) ?></p>
            </div>

            <div class="attempt-meta">
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($attempt["username"], 0, 1)) ?>
                    </div>
                    <div class="user-details">
                        <span class="username"><?= htmlspecialchars(
                            $attempt["username"]
                        ) ?></span>
                        <span class="email"><?= htmlspecialchars(
                            $attempt["email"]
                        ) ?></span>
                    </div>
                </div>

                <div class="attempt-stats">
                    <div class="stat-item">
                        <div class="stat-label">Результат</div>
                        <div class="stat-value score-badge <?= getScoreClass(
                            $attempt["score"]
                        ) ?>">
                            <?= number_format($attempt["score"], 1) ?>%
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Дата прохождения</div>
                        <div class="stat-value"><?= date(
                            "d.m.Y H:i",
                            strtotime($attempt["completed_at"])
                        ) ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Время на тест</div>
                        <div class="stat-value"><?= $attempt[
                            "time_limit"
                        ] ?> мин</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Разбор ответов -->
        <div class="questions-review">
            <?php foreach ($questions as $index => $question):

                $answers = array_map(function ($answer_data) {
                    $parts = explode(":::", $answer_data);
                    return [
                        "id" => $parts[0] ?? "",
                        "text" => $parts[1] ?? "",
                        "is_correct" => $parts[2] ?? "0",
                        "user_selected" => $parts[3] ?? "0",
                    ];
                }, explode("|||", $question["answers_data"]));

                $is_correct = true;
                foreach ($answers as $answer) {
                    if ($answer["is_correct"] != $answer["user_selected"]) {
                        $is_correct = false;
                        break;
                    }
                }
                ?>
                <div class="question-card <?= $is_correct
                    ? "correct"
                    : "incorrect" ?>">
                    <div class="question-header">
                        <div class="question-number">
                            Вопрос <?= $index + 1 ?> из <?= count($questions) ?>
                        </div>
                        <div class="question-type">
                            <?= $question["type"] === "multiple"
                                ? "Несколько ответов"
                                : "Один ответ" ?>
                        </div>
                        <div class="question-status">
                            <?php if ($is_correct): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                <span>Правильно</span>
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="15" y1="9" x2="9" y2="15"></line>
                                    <line x1="9" y1="9" x2="15" y2="15"></line>
                                </svg>
                                <span>Неправильно</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="question-content">
                        <h3><?= htmlspecialchars(
                            $question["question_text"]
                        ) ?></h3>

                        <div class="answers-list">
                            <?php foreach ($answers as $answer): ?>
                                <div class="answer-item <?= getAnswerClass(
                                    $answer
                                ) ?>">
                                    <div class="answer-status">
                                        <?= getAnswerStatusIcon($answer) ?>
                                    </div>
                                    <div class="answer-text">
                                        <?= htmlspecialchars($answer["text"]) ?>
                                    </div>
                                    <?php if ($answer["is_correct"]): ?>
                                        <div class="answer-badge correct">Правильный ответ</div>
                                    <?php endif; ?>
                                    <?php if ($answer["user_selected"]): ?>
                                        <div class="answer-badge selected">Выбран пользователем</div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php
            endforeach; ?>
        </div>
    </div>
</div>

<?php
function getScoreClass($score)
{
    if ($score >= 80) {
        return "score-high";
    }
    if ($score >= 60) {
        return "score-medium";
    }
    return "score-low";
}

function getAnswerClass($answer)
{
    if ($answer["user_selected"] && $answer["is_correct"]) {
        return "correct";
    }
    if ($answer["user_selected"] && !$answer["is_correct"]) {
        return "incorrect";
    }
    if (!$answer["user_selected"] && $answer["is_correct"]) {
        return "correct";
    }
    return "";
}

function getAnswerStatusIcon($answer)
{
    if ($answer["user_selected"] && $answer["is_correct"]) {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4caf50" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>';
    }
    if ($answer["user_selected"] && !$answer["is_correct"]) {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f44336" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>';
    }
    if (!$answer["user_selected"] && $answer["is_correct"]) {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4caf50" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>';
    }
    return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
            </svg>';
}
?>

<?php include "../templates/footer.php"; ?>
