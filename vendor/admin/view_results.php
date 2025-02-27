<?php
require_once "../functions/config/database.php";
require_once "../functions/includes/functions.php";

checkLogin();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

$quiz_id = isset($_GET["quiz_id"]) ? (int) $_GET["quiz_id"] : 0;

// Получаем информацию о тесте
$stmt = $pdo->prepare("
    SELECT q.*,
           u.username as creator_name,
           COUNT(DISTINCT ur.id) as total_attempts,
           AVG(ur.score) as average_score,
           MIN(ur.score) as min_score,
           MAX(ur.score) as max_score,
           (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as questions_count
    FROM quizzes q
    LEFT JOIN user_results ur ON q.id = ur.quiz_id
    LEFT JOIN users u ON q.created_by = u.id
    WHERE q.id = ?
    GROUP BY q.id
");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header("Location: manage_quizzes.php");
    exit();
}

// Получаем распределение баллов
$stmt = $pdo->prepare("
    SELECT
        FLOOR(score/(100/10)) * 10 as score_range,
        COUNT(*) as count
    FROM user_results
    WHERE quiz_id = ?
    GROUP BY FLOOR(score/(100/10))
    ORDER BY score_range
");
$stmt->execute([$quiz_id]);
$score_distribution = $stmt->fetchAll();

// Получаем последние результаты
$stmt = $pdo->prepare("
    SELECT ur.*,
           u.username,
           u.email
    FROM user_results ur
    JOIN users u ON ur.user_id = u.id
    WHERE ur.quiz_id = ?
    ORDER BY ur.completed_at DESC
    LIMIT 50
");
$stmt->execute([$quiz_id]);
$results = $stmt->fetchAll();

include "../templates/header.php";
?>

<div class="container">
    <div class="quiz-results">
        <!-- Заголовок -->
        <div class="page-header">
            <div class="header-content">
                <h1><?= htmlspecialchars($quiz["title"]) ?></h1>
                <p class="text-secondary"><?= htmlspecialchars(
                    $quiz["description"]
                ) ?></p>
            </div>
            <div class="header-actions">
                <a href="manage_quizzes.php" class="btn btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Вернуться к тестам
                </a>
            </div>
        </div>

        <!-- Статистика теста -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?= number_format(
                        $quiz["total_attempts"]
                    ) ?></span>
                    <span class="stat-label">Всего попыток</span>
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
                        $quiz["average_score"],
                        1
                    ) ?></span>
                    <span class="stat-label">Средний балл</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon high-score">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?= number_format(
                        $quiz["max_score"],
                        1
                    ) ?></span>
                    <span class="stat-label">Лучший результат</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?= $quiz["time_limit"] ?></span>
                    <span class="stat-label">Минут на тест</span>
                </div>
            </div>
        </div>

        <!-- Графики и распределение -->
        <div class="charts-grid">
            <!-- График распределения баллов -->
            <div class="chart-card">
                <h3>Распределение результатов</h3>
                <canvas id="scoreDistribution"></canvas>
            </div>

            <!-- Статистика по вопросам -->
            <div class="chart-card">
                <h3>Статистика по вопросам</h3>
                <canvas id="questionStats"></canvas>
            </div>
        </div>

        <!-- Таблица результатов -->
        <div class="results-section">
            <div class="section-header">
                <h3>Последние результаты</h3>
                <div class="section-actions">
                    <button class="btn btn-secondary" onclick="exportResults()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Экспорт
                    </button>
                </div>
            </div>

            <div class="results-table-container">
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Пользователь</th>
                            <th>Результат</th>
                            <th>Время</th>
                            <th>Дата</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $result): ?>
                            <tr>
                                <td class="user-cell">
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?= strtoupper(
                                                substr(
                                                    $result["username"],
                                                    0,
                                                    1
                                                )
                                            ) ?>
                                        </div>
                                        <div class="user-details">
                                            <span class="username"><?= htmlspecialchars(
                                                $result["username"]
                                            ) ?></span>
                                            <span class="email"><?= htmlspecialchars(
                                                $result["email"]
                                            ) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="score-badge <?= getScoreClass(
                                        $result["score"]
                                    ) ?>">
                                        <?= number_format(
                                            $result["score"],
                                            1
                                        ) ?>%
                                    </div>
                                </td>
                                <td><?= date(
                                    "H:i:s",
                                    strtotime($result["completed_at"])
                                ) ?></td>
                                <td><?= date(
                                    "d.m.Y",
                                    strtotime($result["completed_at"])
                                ) ?></td>
                                <td>
                                    <a href="view_attempt.php?id=<?= $result[
                                        "id"
                                    ] ?>" class="btn btn-small">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        Детали
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Данные для графика распределения баллов
    const scoreDistribution = <?= json_encode($score_distribution) ?>;

    new Chart(document.getElementById('scoreDistribution'), {
        type: 'bar',
        data: {
            labels: scoreDistribution.map(d => `${d.score_range}-${d.score_range + 10}`),
            datasets: [{
                label: 'Количество результатов',
                data: scoreDistribution.map(d => d.count),
                backgroundColor: 'rgba(33, 150, 243, 0.5)',
                borderColor: 'rgba(33, 150, 243, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Количество результатов'
                    }
                }
            }
        }
    });
});

function exportResults() {
    // Реализация экспорта результатов
    alert('Экспорт результатов будет добавлен позже');
}

function getScoreClass(score) {
    if (score >= 80) return 'score-high';
    if (score >= 60) return 'score-medium';
    return 'score-low';
}
</script>

<?php include "../templates/footer.php"; ?>
