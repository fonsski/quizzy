<?php
require_once "../functions/config/database.php";
require_once "../functions/includes/functions.php";

checkLogin();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

// Обработка действий с пользователями
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["change_role"])) {
        $user_id = (int) $_POST["user_id"];
        $new_role = $_POST["role"] === "admin" ? "admin" : "user";

        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $user_id]);

        $success = "Роль пользователя успешно обновлена";
    }

    if (isset($_POST["delete_user"])) {
        $user_id = (int) $_POST["user_id"];

        try {
            $pdo->beginTransaction();
            $pdo->prepare(
                "DELETE FROM user_results WHERE user_id = ?"
            )->execute([$user_id]);
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([
                $user_id,
            ]);
            $pdo->commit();
            $success = "Пользователь успешно удален";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Ошибка при удалении пользователя: " . $e->getMessage();
        }
    }
}

// Получаем статистику пользователей
$stats = $pdo
    ->query(
        "
    SELECT
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
        SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as user_count,
        COUNT(DISTINCT ur.user_id) as active_users
    FROM users u
    LEFT JOIN user_results ur ON u.id = ur.user_id
"
    )
    ->fetch();

// Получаем список пользователей с их статистикой
$users = $pdo
    ->query(
        "
    SELECT u.*,
           COUNT(DISTINCT ur.id) as attempts_count,
           AVG(ur.score) as average_score,
           MAX(ur.completed_at) as last_activity,
           COUNT(DISTINCT q.id) as created_quizzes
    FROM users u
    LEFT JOIN user_results ur ON u.id = ur.user_id
    LEFT JOIN quizzes q ON u.id = q.created_by
    GROUP BY u.id
    ORDER BY u.created_at DESC
"
    )
    ->fetchAll();

include "../templates/header.php";
?>

<div class="container">
    <div class="admin-users">
        <!-- Заголовок -->
        <div class="page-header">
            <div class="header-content">
                <h1>Управление пользователями</h1>
                <p class="text-secondary">Управляйте пользователями и их правами доступа</p>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <?= $error ?>
            </div>
        <?php endif; ?>

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
                    <span class="stat-label">Всего пользователей</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?= number_format(
                        $stats["active_users"]
                    ) ?></span>
                    <span class="stat-label">Активных пользователей</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <line x1="19" y1="8" x2="19" y2="14"></line>
                        <line x1="22" y1="11" x2="16" y2="11"></line>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?= number_format(
                        $stats["admin_count"]
                    ) ?></span>
                    <span class="stat-label">Администраторов</span>
                </div>
            </div>
        </div>

        <!-- Фильтры и поиск -->
        <div class="filters-bar">
            <div class="search-box">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="searchUser" placeholder="Поиск пользователей..." class="search-input">
            </div>

            <div class="filters">
                <button class="filter-btn active" data-filter="all">Все</button>
                <button class="filter-btn" data-filter="admin">Администраторы</button>
                <button class="filter-btn" data-filter="user">Пользователи</button>
            </div>
        </div>

        <!-- Список пользователей -->
        <div class="users-grid">
            <?php foreach ($users as $user): ?>
                <div class="user-card" data-role="<?= $user["role"] ?>">
                    <div class="user-card-header">
                        <div class="user-avatar">
                            <?= strtoupper(substr($user["username"], 0, 1)) ?>
                        </div>
                        <div class="user-status <?= $user["role"] === "admin"
                            ? "status-admin"
                            : "status-user" ?>">
                            <?= $user["role"] === "admin"
                                ? "Администратор"
                                : "Пользователь" ?>
                        </div>
                    </div>

                    <div class="user-card-content">
                        <h3><?= htmlspecialchars($user["username"]) ?></h3>
                        <p class="user-email"><?= htmlspecialchars(
                            $user["email"]
                        ) ?></p>

                        <div class="user-stats">
                            <div class="stat-group">
                                <div class="stat-value"><?= number_format(
                                    $user["attempts_count"]
                                ) ?></div>
                                <div class="stat-label">Попыток</div>
                            </div>
                            <div class="stat-group">
                                <div class="stat-value"><?= $user[
                                    "average_score"
                                ]
                                    ? number_format($user["average_score"], 1)
                                    : "0" ?></div>
                                <div class="stat-label">Средний балл</div>
                            </div>
                            <div class="stat-group">
                                <div class="stat-value"><?= number_format(
                                    $user["created_quizzes"]
                                ) ?></div>
                                <div class="stat-label">Создано тестов</div>
                            </div>
                        </div>

                        <div class="user-activity">
                            <span class="activity-label">Последняя активность:</span>
                            <span class="activity-date">
                                <?= $user["last_activity"]
                                    ? date(
                                        "d.m.Y H:i",
                                        strtotime($user["last_activity"])
                                    )
                                    : "Нет активности" ?>
                            </span>
                        </div>
                    </div>

                    <div class="user-card-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="user_id" value="<?= $user[
                                "id"
                            ] ?>">
                            <select name="role" onchange="this.form.submit()"
                                    class="role-select" <?= $user["id"] ===
                                    $_SESSION["user_id"]
                                        ? "disabled"
                                        : "" ?>>
                                <option value="user" <?= $user["role"] ===
                                "user"
                                    ? "selected"
                                    : "" ?>>Пользователь</option>
                                <option value="admin" <?= $user["role"] ===
                                "admin"
                                    ? "selected"
                                    : "" ?>>Администратор</option>
                            </select>
                            <input type="hidden" name="change_role" value="1">
                        </form>

                        <?php if ($user["id"] !== $_SESSION["user_id"]): ?>
                            <form method="POST" style="display: inline;"
                                  onsubmit="return confirm('Вы уверены, что хотите удалить этого пользователя?');">
                                <input type="hidden" name="user_id" value="<?= $user[
                                    "id"
                                ] ?>">
                                <button type="submit" name="delete_user" class="btn btn-danger">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                    Удалить
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
  // Поиск пользователей
  const searchInput = document.getElementById("searchUser");
  const userCards = document.querySelectorAll(".user-card");

  searchInput.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase();

    userCards.forEach((card) => {
      const username = card.querySelector("h3").textContent.toLowerCase();
      const email = card.querySelector(".user-email").textContent.toLowerCase();

      if (username.includes(searchTerm) || email.includes(searchTerm)) {
        card.style.display = "block";
      } else {
        card.style.display = "none";
      }
    });
  });

  // Фильтрация пользователей
  const filterButtons = document.querySelectorAll(".filter-btn");

  filterButtons.forEach((btn) => {
    btn.addEventListener("click", function () {
      filterButtons.forEach((b) => b.classList.remove("active"));
      this.classList.add("active");

      const filter = this.dataset.filter;

      userCards.forEach((card) => {
        if (filter === "all" || card.dataset.role === filter) {
          card.style.display = "block";
        } else {
          card.style.display = "none";
        }
      });
    });
  });
});

</script>
<?php include "../templates/footer.php"; ?>
