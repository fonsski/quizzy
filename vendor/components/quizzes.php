<?php
require_once "../functions/config/database.php";
require_once "../functions/includes/functions.php";

checkLogin();

// Получаем список всех доступных тестов
$stmt = $pdo->prepare("
    SELECT q.*,
           COUNT(DISTINCT ur.id) as attempts_count,
           AVG(ur.score) as average_score,
           u.username as author_name,
           (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as questions_count,
           EXISTS(SELECT 1 FROM user_results WHERE user_id = ? AND quiz_id = q.id) as is_attempted
    FROM quizzes q
    LEFT JOIN user_results ur ON q.id = ur.quiz_id
    LEFT JOIN users u ON q.created_by = u.id
    GROUP BY q.id
    ORDER BY q.created_at DESC
");
$stmt->execute([$_SESSION["user_id"]]);
$quizzes = $stmt->fetchAll();

include "../templates/header.php";
?>

<div class="container">
    <div class="page-header">
        <div class="header-content">
            <h1>Доступные тесты</h1>
            <p class="text-secondary">Выберите тест для прохождения</p>
        </div>

        <div class="header-actions">
            <div class="search-box">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="searchQuiz" placeholder="Поиск тестов..." class="search-input">
            </div>
        </div>
    </div>

    <div class="filters">
        <button class="filter-btn active" data-filter="all">Все тесты</button>
        <button class="filter-btn" data-filter="not-attempted">Не пройденные</button>
        <button class="filter-btn" data-filter="attempted">Пройденные</button>
    </div>

    <div class="quiz-grid" id="quizGrid">
        <?php foreach ($quizzes as $quiz): ?>
            <div class="quiz-card" data-attempted="<?= $quiz["is_attempted"]
                ? "true"
                : "false" ?>">
                <div class="quiz-card-header">
                    <div class="quiz-status <?= $quiz["is_attempted"]
                        ? "status-completed"
                        : "status-new" ?>">
                        <?= $quiz["is_attempted"] ? "Пройден" : "Новый" ?>
                    </div>
                    <div class="quiz-stats">
                        <div class="stat">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <?= $quiz["time_limit"] ?> мин
                        </div>
                        <div class="stat">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 3h18v18H3zM8 12h8"></path>
                            </svg>
                            <?= $quiz["questions_count"] ?> вопросов
                        </div>
                    </div>
                </div>

                <div class="quiz-card-content">
                    <h3><?= htmlspecialchars($quiz["title"]) ?></h3>
                    <p class="quiz-description"><?= htmlspecialchars(
                        $quiz["description"]
                    ) ?></p>

                    <?php if ($quiz["is_attempted"]): ?>
                        <div class="quiz-score">
                            <div class="score-badge">
                                <span class="score-value"><?= round(
                                    $quiz["average_score"]
                                ) ?>%</span>
                                <span class="score-label">Средний балл</span>
                            </div>
                            <span class="attempts-count"><?= $quiz[
                                "attempts_count"
                            ] ?> попыток</span>
                        </div>
                    <?php endif; ?>

                    <div class="quiz-meta">
                        <div class="author">
                            <div class="author-avatar">
                                <?= strtoupper(
                                    substr($quiz["author_name"], 0, 1)
                                ) ?>
                            </div>
                            <span>Автор: <?= htmlspecialchars(
                                $quiz["author_name"]
                            ) ?></span>
                        </div>
                        <div class="created-at">
                            <?= date("d.m.Y", strtotime($quiz["created_at"])) ?>
                        </div>
                    </div>
                </div>

                <div class="quiz-card-footer">
                    <a href="take_quiz.php?id=<?= $quiz[
                        "id"
                    ] ?>" class="btn btn-primary">
                        <?= $quiz["is_attempted"]
                            ? "Пройти снова"
                            : "Начать тест" ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                    <?php if ($quiz["is_attempted"]): ?>
                        <a href="view_results.php?quiz_id=<?= $quiz[
                            "id"
                        ] ?>" class="btn btn-secondary">
                            Результаты
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include "../templates/footer.php"; ?>
