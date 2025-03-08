<?php
require_once "vendor/functions/config/database.php";
require_once "vendor/functions/includes/functions.php";

include "vendor/templates/header.php";
?>

<div class="container">
    <div class="hero-section">
        <h1>Добро пожаловать в Quizzy</h1>
        <p class="hero-subtitle">Проверьте свои знания с помощью интерактивных тестов</p>
        <?php if (!isset($_SESSION["user_id"])): ?>
            <div class="hero-actions">
                <a href="login.php" class="btn btn-primary">
                    <span>Начать</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
                <a href="register.php" class="btn btn-secondary">Создать аккаунт</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION["user_id"])): ?>
        <div class="quizzes-section">
            <div class="section-header">
                <h2>Доступные тесты</h2>
                <p class="section-subtitle">Выберите тест для прохождения</p>
            </div>

            <div class="quiz-grid">
                <?php
                $quizzes = getQuizzes();
                foreach ($quizzes as $quiz): ?>
                    <div class="quiz-card">
                        <div class="quiz-card-content">
                            <h3><?= htmlspecialchars($quiz["title"]) ?></h3>
                            <p><?= htmlspecialchars($quiz["description"]) ?></p>

                            <div class="quiz-meta">
                                <span class="quiz-stat">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    <?= $quiz["time_limit"] ?> мин
                                </span>
                                <span class="quiz-stat">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                    <?= $quiz["attempts_count"] ?? 0 ?> попыток
                                </span>
                            </div>
                        </div>

                        <div class="quiz-card-actions">
                            <a href="take_quiz.php?id=<?= $quiz["id"] ?>" class="btn btn-primary">
                                Начать тест
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </a>
                        </div>
                    </div>
                <?php endforeach;
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include "templates/footer.php"; ?>