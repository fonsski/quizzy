<?php
require_once "../functions/config/database.php";
require_once "../functions/includes/functions.php";

checkLogin();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

// Удаление теста
if (isset($_POST["delete_quiz"])) {
    $quiz_id = (int) $_POST["quiz_id"];
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM user_results WHERE quiz_id = ?")->execute([
            $quiz_id,
        ]);
        $pdo->prepare(
            "DELETE FROM answers WHERE question_id IN (SELECT id FROM questions WHERE quiz_id = ?)"
        )->execute([$quiz_id]);
        $pdo->prepare("DELETE FROM questions WHERE quiz_id = ?")->execute([
            $quiz_id,
        ]);
        $pdo->prepare("DELETE FROM quizzes WHERE id = ?")->execute([$quiz_id]);
        $pdo->commit();
        $success = "Тест успешно удален";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Ошибка при удалении теста: " . $e->getMessage();
    }
}

// Получаем список всех тестов с дополнительной информацией
$stmt = $pdo->query("
    SELECT q.*,
           COUNT(DISTINCT ur.id) as attempts_count,
           AVG(ur.score) as average_score,
           u.username as creator_name,
           (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as questions_count
    FROM quizzes q
    LEFT JOIN user_results ur ON q.id = ur.quiz_id
    LEFT JOIN users u ON q.created_by = u.id
    GROUP BY q.id
    ORDER BY q.created_at DESC
");
$quizzes = $stmt->fetchAll();

include "../templates/header.php";
?>

<div class="container">
    <div class="admin-quizzes">
        <!-- Заголовок и действия -->
        <div class="page-header">
            <div class="header-content">
                <h1>Управление тестами</h1>
                <p class="text-secondary">Создавайте, редактируйте и управляйте тестами</p>
            </div>
            <div class="header-actions">
                <a href="create_quiz.php" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Создать тест
                </a>
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

        <!-- Фильтры и поиск -->
        <div class="filters-bar">
            <div class="search-box">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="searchQuiz" placeholder="Поиск тестов..." class="search-input">
            </div>

            <div class="filters">
                <button class="filter-btn active" data-filter="all">Все тесты</button>
                <button class="filter-btn" data-filter="active">Активные</button>
                <button class="filter-btn" data-filter="draft">Черновики</button>
            </div>
        </div>

        <!-- Сетка тестов -->
        <div class="quizzes-grid">
            <?php foreach ($quizzes as $quiz): ?>
                <div class="quiz-card">
                    <div class="quiz-card-header">
                        <div class="quiz-status <?= $quiz["questions_count"] > 0
                            ? "status-active"
                            : "status-draft" ?>">
                            <?= $quiz["questions_count"] > 0
                                ? "Активный"
                                : "Черновик" ?>
                        </div>
                        <div class="quiz-meta">
                            <span class="meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <?= $quiz["time_limit"] ?> мин
                            </span>
                            <span class="meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                    <polyline points="13 2 13 9 20 9"></polyline>
                                </svg>
                                <?= $quiz["questions_count"] ?> вопросов
                            </span>
                        </div>
                    </div>

                    <div class="quiz-card-content">
                        <h3><?= htmlspecialchars($quiz["title"]) ?></h3>
                        <p class="quiz-description"><?= htmlspecialchars(
                            $quiz["description"]
                        ) ?></p>

                        <div class="quiz-stats">
                            <div class="stat-group">
                                <div class="stat-value"><?= number_format(
                                    $quiz["attempts_count"]
                                ) ?></div>
                                <div class="stat-label">Попыток</div>
                            </div>
                            <div class="stat-group">
                                <div class="stat-value"><?= number_format(
                                    $quiz["average_score"],
                                    1
                                ) ?></div>
                                <div class="stat-label">Средний балл</div>
                            </div>
                        </div>

                        <div class="quiz-info">
                            <div class="creator">
                                <div class="creator-avatar">
                                    <?= strtoupper(
                                        substr($quiz["creator_name"], 0, 1)
                                    ) ?>
                                </div>
                                <span>Автор: <?= htmlspecialchars(
                                    $quiz["creator_name"]
                                ) ?></span>
                            </div>
                            <div class="created-date">
                                <?= date(
                                    "d.m.Y",
                                    strtotime($quiz["created_at"])
                                ) ?>
                            </div>
                        </div>
                    </div>

                    <div class="quiz-card-actions">
                        <a href="edit_quiz.php?id=<?= $quiz[
                            "id"
                        ] ?>" class="btn btn-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            Редактировать
                        </a>
                        <a href="view_results.php?quiz_id=<?= $quiz[
                            "id"
                        ] ?>" class="btn btn-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            Результаты
                        </a>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Вы уверены, что хотите удалить этот тест?');">
                            <input type="hidden" name="quiz_id" value="<?= $quiz[
                                "id"
                            ] ?>">
                            <button type="submit" name="delete_quiz" class="btn btn-danger">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                                Удалить
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
  // Поиск тестов
  const searchInput = document.getElementById("searchQuiz");
  const quizCards = document.querySelectorAll(".quiz-card");

  searchInput.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase();

    quizCards.forEach((card) => {
      const title = card.querySelector("h3").textContent.toLowerCase();
      const description = card
        .querySelector(".quiz-description")
        .textContent.toLowerCase();

      if (title.includes(searchTerm) || description.includes(searchTerm)) {
        card.style.display = "block";
      } else {
        card.style.display = "none";
      }
    });
  });

  // Фильтрация тестов
  const filterButtons = document.querySelectorAll(".filter-btn");

  filterButtons.forEach((btn) => {
    btn.addEventListener("click", function () {
      filterButtons.forEach((b) => b.classList.remove("active"));
      this.classList.add("active");

      const filter = this.dataset.filter;

      quizCards.forEach((card) => {
        if (filter === "all") {
          card.style.display = "block";
        } else if (filter === "attempted") {
          card.style.display =
            card.dataset.attempted === "true" ? "block" : "none";
        } else if (filter === "not-attempted") {
          card.style.display =
            card.dataset.attempted === "false" ? "block" : "none";
        }
      });
    });
  });
});

document.addEventListener("DOMContentLoaded", function () {
  // Поиск тестов
  const searchInput = document.getElementById("searchQuiz");
  const quizCards = document.querySelectorAll(".quiz-card");

  searchInput.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase();

    quizCards.forEach((card) => {
      const title = card.querySelector("h3").textContent.toLowerCase();
      const description = card
        .querySelector(".quiz-description")
        .textContent.toLowerCase();

      if (title.includes(searchTerm) || description.includes(searchTerm)) {
        card.style.display = "block";
      } else {
        card.style.display = "none";
      }
    });
  });

  // Фильтрация тестов
  const filterButtons = document.querySelectorAll(".filter-btn");

  filterButtons.forEach((btn) => {
    btn.addEventListener("click", function () {
      filterButtons.forEach((b) => b.classList.remove("active"));
      this.classList.add("active");

      const filter = this.dataset.filter;

      quizCards.forEach((card) => {
        const status = card
          .querySelector(".quiz-status")
          .classList.contains("status-active")
          ? "active"
          : "draft";

        if (filter === "all" || filter === status) {
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
