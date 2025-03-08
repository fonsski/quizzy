<?php
require_once "../functions/config/database.php";
require_once "../functions/includes/functions.php";
checkLogin();

$user_id = $_SESSION["user_id"]; // Текущий пользователь
// Получаем результаты пользователя
$stmt = $pdo->prepare("
    SELECT ur.id AS attempt_id,
           q.title AS quiz_title,
           ur.score,
           ur.completed_at
    FROM user_results ur
    JOIN quizzes q ON ur.quiz_id = q.id
    WHERE ur.user_id = ?
    ORDER BY ur.completed_at DESC
");
$stmt->execute([$user_id]);
$results = $stmt->fetchAll();

include "../templates/header.php";
?>

<div class="container">
    <h2>Мои результаты</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Тест</th>
                <th>Баллы</th>
                <th>Дата</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $result): ?>
            <tr>
                <td><?= htmlspecialchars($result["quiz_title"]) ?></td>
                <td><?= htmlspecialchars($result["score"]) ?></td>
                <td><?= date(
                    "F j, Y, g:i a",
                    strtotime($result["completed_at"])
                ) ?></td>
                <td><a href="view_results.php?attempt_id=<?= $result[
                    "attempt_id"
                ] ?>" class="btn">Детали</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include "../templates/footer.php"; ?>
