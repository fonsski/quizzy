<?php
session_start();
require_once __DIR__ . "/../functions/config/database.php";
require_once __DIR__ . "/../functions/includes/functions.php";

checkLogin();
if (!isAdmin()) {
    header("Location: /index.php");
    exit();
}

// Получаем общую статистику
$stats = $pdo
    ->query(
        "
    SELECT
        (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users,
        (SELECT COUNT(*) FROM quizzes) as total_quizzes,
        (SELECT COUNT(*) FROM user_results) as total_attempts,
        (SELECT AVG(score) FROM user_results) as average_score
"
    )
    ->fetch();

// Получаем последние действия
$recent_activities = $pdo
    ->query(
        "
    SELECT
        ur.completed_at,
        u.username,
        q.title as quiz_title,
        ur.score
    FROM user_results ur
    JOIN users u ON ur.user_id = u.id
    JOIN quizzes q ON ur.quiz_id = q.id
    ORDER BY ur.completed_at DESC
    LIMIT 5
"
    )
    ->fetchAll();

include __DIR__ . "/../templates/header.php";
?>

<div class="container">
    <div class="admin-dashboard">
        <!-- Приветствие -->
        <div class="welcome-section">
            <div class="welcome-content">
                <h1>Панель администратора</h1>
                <p class="welcome-text">Добро пожаловать в панель управления системы тестирования</p>
            </div>
            <div class="quick-actions">
                <a href="create_quiz.php" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Создать тест
                </a>
                <a href="manage_users.php" class="btn btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    Управление пользователями
                </a>
            </div>
        </div>

        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?= number_format(
                        $stats["total_users"]
                    ) ?></span>
                    <span class="stat-label">Пользователей</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?= number_format(
                        $stats["total_quizzes"]
                    ) ?></span>
                    <span class="stat-label">Тестов</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?= number_format(
                        $stats["total_attempts"]
                    ) ?></span>
                    <span class="stat-label">Попыток</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.2 7.8l-7.7 7.7-4-4-5.7 5.7"></path>
                        <path d="M15 7h6v6"></path>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?= number_format(
                        $stats["average_score"],
                        1
                    ) ?></span>
                    <span class="stat-label">Средний балл</span>
                </div>
            </div>
        </div>

        <!-- Основные действия -->
        <div class="admin-actions-grid">
            <div class="action-card">
                <div class="action-header">
                    <h3>Управление тестами</h3>
                    <p>Создание и редактирование тестов</p>
                </div>
                <div class="action-links">
                    <a href="manage_quizzes.php" class="btn btn-primary">Все тесты</a>
                    <a href="create_quiz.php" class="btn btn-secondary">Создать тест</a>
                </div>
            </div>

            <div class="action-card">
                <div class="action-header">
                    <h3>Управление пользователями</h3>
                    <p>Управление аккаунтами пользователей</p>
                </div>
                <div class="action-links">
                    <a href="manage_users.php" class="btn btn-primary">Все пользователи</a>
                </div>
            </div>

            <div class="action-card">
                <div class="action-header">
                    <h3>Статистика</h3>
                    <p>Анализ результатов и активности</p>
                </div>
                <div class="action-links">
                    <a href="statistics.php" class="btn btn-primary">Посмотреть статистику</a>
                </div>
            </div>
        </div>

        <!-- Последние действия --> 
        <div class="recent-activities">
            <h3>Последние действия</h3>
            <div class="activities-list">
                <?php foreach ($recent_activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </div>
                        <div class="activity-details">
                            <p>
                                <strong><?= htmlspecialchars(
                                    $activity["username"]
                                ) ?></strong>
                                прошел тест "<?= htmlspecialchars(
                                    $activity["quiz_title"]
                                ) ?>"
                            </p>
                            <span class="activity-meta">
                                Результат: <?= $activity["score"] ?> |
                                <?= date(
                                    "d.m.Y H:i",
                                    strtotime($activity["completed_at"])
                                ) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . "/../templates/footer.php"; ?>