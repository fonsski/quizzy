<?php
require_once "../functions/config/database.php";
require_once "../functions/includes/functions.php";

checkLogin();
if (!isAdmin()) {
    header("Location: ../index.php");
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
        (SELECT AVG(score) FROM user_results) as average_score,
        (SELECT COUNT(DISTINCT user_id) FROM user_results) as active_users
"
    )
    ->fetch();

// Статистика по дням за последний месяц
$daily_stats = $pdo
    ->query(
        "
    SELECT
        DATE(completed_at) as date,
        COUNT(*) as attempts,
        AVG(score) as avg_score
    FROM user_results
    WHERE completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(completed_at)
    ORDER BY date
"
    )
    ->fetchAll();

// Топ тестов
$top_quizzes = $pdo
    ->query(
        "
    SELECT
        q.title,
        COUNT(ur.id) as attempts_count,
        AVG(ur.score) as avg_score,
        MIN(ur.score) as min_score,
        MAX(ur.score) as max_score
    FROM quizzes q
    LEFT JOIN user_results ur ON q.id = ur.quiz_id
    GROUP BY q.id
    ORDER BY attempts_count DESC
    LIMIT 5
"
    )
    ->fetchAll();

// Статистика по пользователям
$user_stats = $pdo
    ->query(
        "
    SELECT
        u.username,
        COUNT(ur.id) as attempts_count,
        AVG(ur.score) as avg_score,
        COUNT(DISTINCT ur.quiz_id) as unique_quizzes
    FROM users u
    LEFT JOIN user_results ur ON u.id = ur.user_id
    WHERE u.role = 'user'
    GROUP BY u.id
    ORDER BY attempts_count DESC
    LIMIT 5
"
    )
    ->fetchAll();

include "../templates/header.php";
?>

<div class="container">
    <div class="statistics-dashboard">
        <!-- Заголовок -->
        <div class="page-header">
            <div class="header-content">
                <h1>Статистика системы</h1>
                <p class="text-secondary">Аналитика использования системы тестирования</p>
            </div>
        </div>

        <!-- Основные показатели -->
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

        <!-- Графики -->
        <div class="charts-grid">
            <!-- График активности -->
            <div class="chart-card">
                <h3>Активность за последние 30 дней</h3>
                <canvas id="activityChart"></canvas>
            </div>

            <!-- График распределения баллов -->
            <div class="chart-card">
                <h3>Распределение результатов</h3>
                <canvas id="scoresChart"></canvas>
            </div>
        </div>

        <!-- Рейтинги -->
        <div class="rankings-grid">
            <!-- Топ тестов -->
            <div class="ranking-card">
                <h3>Популярные тесты</h3>
                <div class="ranking-list">
                    <?php foreach ($top_quizzes as $index => $quiz): ?>
                        <div class="ranking-item">
                            <div class="ranking-position"><?= $index +
                                1 ?></div>
                            <div class="ranking-content">
                                <div class="ranking-title"><?= htmlspecialchars(
                                    $quiz["title"]
                                ) ?></div>
                                <div class="ranking-stats">
                                    <span class="stat"><?= number_format(
                                        $quiz["attempts_count"]
                                    ) ?> попыток</span>
                                    <span class="stat"><?= number_format(
                                        $quiz["avg_score"],
                                        1
                                    ) ?> ср. балл</span>
                                </div>
                            </div>
                            <div class="ranking-score">
                                <div class="score-range">
                                    <span class="min"><?= number_format(
                                        $quiz["min_score"]
                                    ) ?></span>
                                    <span class="max"><?= number_format(
                                        $quiz["max_score"]
                                    ) ?></span>
                                </div>
                                <div class="score-bar">
                                    <div class="score-fill" style="width: <?= ($quiz[
                                        "avg_score"
                                    ] /
                                        100) *
                                        100 ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Топ пользователей -->
            <div class="ranking-card">
                <h3>Активные пользователи</h3>
                <div class="ranking-list">
                    <?php foreach ($user_stats as $index => $user): ?>
                        <div class="ranking-item">
                            <div class="ranking-position"><?= $index +
                                1 ?></div>
                            <div class="ranking-content">
                                <div class="ranking-title"><?= htmlspecialchars(
                                    $user["username"]
                                ) ?></div>
                                <div class="ranking-stats">
                                    <span class="stat"><?= number_format(
                                        $user["attempts_count"]
                                    ) ?> попыток</span>
                                    <span class="stat"><?= number_format(
                                        $user["unique_quizzes"]
                                    ) ?> тестов</span>
                                </div>
                            </div>
                            <div class="ranking-score">
                                <div class="score-value"><?= number_format(
                                    $user["avg_score"],
                                    1
                                ) ?></div>
                                <div class="score-label">ср. балл</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Данные для графиков
    const dailyStats = <?= json_encode($daily_stats) ?>;

    // График активности
    const activityChart = new Chart(document.getElementById('activityChart'), {
        type: 'line',
        data: {
            labels: dailyStats.map(stat => stat.date),
            datasets: [{
                label: 'Количество попыток',
                data: dailyStats.map(stat => stat.attempts),
                borderColor: 'rgba(33, 150, 243, 1)',
                backgroundColor: 'rgba(33, 150, 243, 0.1)',
                borderWidth: 2,
                fill: true
            }, {
                label: 'Средний балл',
                data: dailyStats.map(stat => stat.avg_score),
                borderColor: 'rgba(76, 175, 80, 1)',
                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                borderWidth: 2,
                fill: true,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
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
                        text: 'Количество попыток'
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Средний балл'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });

    // График распределения баллов
    const scoresData = Array(10).fill(0);
    dailyStats.forEach(stat => {
        const scoreIndex = Math.floor(stat.avg_score / 10);
        if (scoreIndex >= 0 && scoreIndex < 10) {
            scoresData[scoreIndex]++;
        }
    });

    new Chart(document.getElementById('scoresChart'), {
        type: 'bar',
        data: {
            labels: ['0-10', '11-20', '21-30', '31-40', '41-50', '51-60', '61-70', '71-80', '81-90', '91-100'],
            datasets: [{
                label: 'Количество результатов',
                data: scoresData,
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
                },
                x: {
                    title: {
                        display: true,
                        text: 'Диапазон баллов'
                    }
                }
            }
        }
    });
});

</script>
<?php include "../templates/footer.php"; ?>
