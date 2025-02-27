<?php
require_once "../functions/config/database.php";
require_once "../functions/includes/functions.php";
$pageType = 'results';
checkLogin();

$attempt_id = isset($_GET["attempt_id"]) ? (int) $_GET["attempt_id"] : 0;

// Получаем детали попытки
$stmt = $pdo->prepare("
    SELECT ur.*,
           q.title AS quiz_title,
           q.description AS quiz_description,
           q.time_limit,
           u.username,
           (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as total_questions
    FROM user_results ur
    JOIN quizzes q ON ur.quiz_id = q.id
    JOIN users u ON ur.user_id = u.id
    WHERE ur.id = ?
");
$stmt->execute([$attempt_id]);
$attempt = $stmt->fetch();

if (!$attempt) {
    echo "No attempt found for ID: " . $attempt_id;
    header("Location: quizzes.php");
    exit();
}

// Получаем вопросы и ответы пользователя
$stmt = $pdo->prepare("
    SELECT
        q.id as question_id,
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

try {
    $stmt->execute([$attempt_id, $attempt["quiz_id"]]);
    $questions = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "Error executing query: " . $e->getMessage();
    exit();
}
// Подсчет статистики
$correctQuestions = 0;
$totalQuestions = count($questions);

foreach ($questions as $question) {
    $answers = array_map(function ($answer_data) {
        $parts = explode(":::", $answer_data);
        return [
            "id" => $parts[0] ?? "",
            "text" => $parts[1] ?? "",
            "is_correct" => $parts[2] ?? "0",
            "user_selected" => $parts[3] ?? "0",
        ];
    }, explode("|||", $question["answers_data"]));

    $questionCorrect = true;
    foreach ($answers as $answer) {
        if ($answer["is_correct"] != $answer["user_selected"]) {
            $questionCorrect = false;
            break;
        }
    }
    if ($questionCorrect) {
        $correctQuestions++;
    }
}

$percentage = ($correctQuestions / $totalQuestions) * 100;

include "../templates/header.php";
?>

<div class="container">
    <div class="results-container">
        <!-- Карточка с общими результатами -->
        <div class="results-header">
            <div class="results-summary">
                <h1>Результаты теста</h1>
                <h2><?= htmlspecialchars($attempt["quiz_title"]) ?></h2>
                <p class="quiz-description"><?= htmlspecialchars(
                    $attempt["quiz_description"]
                ) ?></p>
            </div>

            <div class="results-meta">
                <div class="meta-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                    </svg>
                    <span><?= htmlspecialchars($attempt["username"]) ?></span>
                </div>
                <div class="meta-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span><?= date(
                        "d.m.Y H:i",
                        strtotime($attempt["completed_at"])
                    ) ?></span>
                </div>
            </div>
        </div>

        <!-- Карточка со счетом -->
        <div class="score-card">
            <div class="score-circle" style="--percentage: <?= $percentage ?>">
                <div class="score-number">
                    <span class="percentage"><?= round($percentage) ?>%</span>
                    <span class="score-label">Результат</span>
                </div>
            </div>

            <div class="score-details">
                <div class="score-item">
                    <span class="score-value"><?= $correctQuestions ?>/<?= $totalQuestions ?></span>
                    <span class="score-label">Правильных ответов</span>
                </div>
                <div class="score-item">
                    <span class="score-value"><?= $attempt[
                        "time_limit"
                    ] ?> мин</span>
                    <span class="score-label">Время на тест</span>
                </div>
                <div class="score-item">
                    <span class="score-value"><?= getScoreMessage(
                        $percentage
                    ) ?></span>
                    <span class="score-label">Оценка</span>
                </div>
            </div>
        </div>

        <!-- Подробный разбор ответов -->
        <div class="answers-review">
            <h3>Подробный разбор</h3>

            <?php foreach ($questions as $index => $question):
                $answers = array_map(function ($answer_data) {
                    $parts = explode(":::", $answer_data);
                    return [
                        "id" => $parts[0] ?? "",
                        "text" => $parts[1] ?? "",
                        "is_correct" => $parts[2] ?? "0",
                        "user_selected" => $parts[3] ?? "0",
                    ];
                }, explode("|||", $question["answers_data"])); ?>
                <div class="question-review">
                    <div class="question-header">
                        <h4>Вопрос <?= $index + 1 ?></h4>
                        <span class="question-type">
                            <?= $question["type"] === "multiple"
                                ? "Несколько ответов"
                                : "Один ответ" ?>
                        </span>
                    </div>

                    <p class="question-text"><?= htmlspecialchars(
                        $question["question_text"]
                    ) ?></p>

                    <div class="answers-list">
                        <?php foreach ($answers as $answer): ?>
                            <div class="answer-item <?= getAnswerClass(
                                $answer
                            ) ?>">
                                <span class="answer-icon">
                                    <?= getAnswerIcon($answer) ?>
                                </span>
                                <span class="answer-text">
                                    <?= htmlspecialchars($answer["text"]) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php
            endforeach; ?>
        </div>

        <!-- Действия -->
        <div class="results-actions">
            <a href="quizzes.php" class="btn btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                К списку тестов
            </a>
            <a href="take_quiz.php?id=<?= $attempt[
                "quiz_id"
            ] ?>" class="btn btn-primary">
                Пройти тест снова
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
            </a>
        </div>
    </div>
</div>

<?php
function getScoreMessage($percentage)
{
    if ($percentage >= 90) {
        return "Отлично";
    }
    if ($percentage >= 70) {
        return "Хорошо";
    }
    if ($percentage >= 50) {
        return "Удовлетворительно";
    }
    return "Нужно подготовиться";
}

function getAnswerClass($answer)
{
    $classes = [];

    // Преобразуем возможный NULL в "0"
    $isCorrect = $answer["is_correct"] ?? "0";
    $userSelected = $answer["user_selected"] ?? "0";

    // Преобразуем в булевы значения
    $isCorrect = $isCorrect === "1";
    $userSelected = $userSelected === "1";

    if ($userSelected) {
        $classes[] = "user-selected";
        if ($isCorrect) {
            $classes[] = "answer-correct";
        } else {
            $classes[] = "answer-incorrect";
        }
    } elseif ($isCorrect) {
        $classes[] = "answer-correct";
    }

    return implode(" ", $classes);
}

function getAnswerIcon($answer)
{
    // Преобразуем возможный NULL в "0"
    $isCorrect = $answer["is_correct"] ?? "0";
    $userSelected = $answer["user_selected"] ?? "0";

    // Преобразуем в булевы значения
    $isCorrect = $isCorrect === "1";
    $userSelected = $userSelected === "1";

    if ($userSelected) {
        if ($isCorrect) {
            // Правильный выбранный ответ - зеленая галочка
            return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4caf50" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>';
        } else {
            // Неправильный выбранный ответ - красный крестик
            return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f44336" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>';
        }
    } elseif ($isCorrect) {
        // Правильный невыбранный ответ - зеленый кружок
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4caf50" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>';
    }

    return ""; // Для неправильных невыбранных ответов
}
?>

<?php include "../templates/footer.php";; ?>
