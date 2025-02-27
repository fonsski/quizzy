<?php
require_once "../functions/config/database.php";
require_once "../functions/includes/functions.php";

checkLogin();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = sanitizeInput($_POST["title"]);
    $description = sanitizeInput($_POST["description"]);
    $time_limit = (int) $_POST["time_limit"];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO quizzes (title, description, time_limit, created_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $title,
            $description,
            $time_limit,
            $_SESSION["user_id"],
        ]);
        $quiz_id = $pdo->lastInsertId();

        foreach ($_POST["questions"] as $q) {
            $stmt = $pdo->prepare("
                INSERT INTO questions (quiz_id, question_text, type)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$quiz_id, $q["text"], $q["type"]]);
            $question_id = $pdo->lastInsertId();

            foreach ($q["answers"] as $a) {
                $stmt = $pdo->prepare("
                    INSERT INTO answers (question_id, answer_text, is_correct)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([
                    $question_id,
                    $a["text"],
                    isset($a["is_correct"]) ? 1 : 0,
                ]);
            }
        }

        $pdo->commit();
        header("Location: manage_quizzes.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error creating quiz: " . $e->getMessage();
    }
}

include "../templates/header.php";
?>

<div class="container">
    <div class="quiz-creator">
        <!-- Заголовок -->
        <div class="page-header">
            <div class="header-content">
                <h1>Создание теста</h1>
                <p class="text-secondary">Создайте новый тест с вопросами и ответами</p>
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

        <form id="createQuizForm" method="POST" class="quiz-form">
            <!-- Основная информация -->
            <div class="form-section">
                <h2>Основная информация</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title">Название теста</label>
                        <input type="text" id="title" name="title" class="form-control" required
                               placeholder="Введите название теста">
                    </div>

                    <div class="form-group">
                        <label for="time_limit">Ограничение по времени (в минутах)</label>
                        <input type="number" id="time_limit" name="time_limit" class="form-control"
                               value="30" min="1" max="180">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Описание теста</label>
                    <textarea id="description" name="description" class="form-control" rows="3"
                              placeholder="Введите описание теста"></textarea>
                </div>
            </div>

            <!-- Вопросы -->
            <div class="form-section">
                <div class="section-header">
                    <h2>Вопросы</h2>
                    <button type="button" class="btn btn-primary" onclick="addQuestion()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Добавить вопрос
                    </button>
                </div>

                <div id="questions-container">
                    <!-- Здесь будут появляться вопросы -->
                </div>
            </div>

            <!-- Кнопки действий -->
            <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Сохранить тест
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='manage_quizzes.php'">
                    Отмена
                </button>
            </div>
        </form>
    </div>
</div>


<?php include "../templates/footer.php"; ?>
