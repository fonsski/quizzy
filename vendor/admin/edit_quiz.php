<?php
require_once "../functions/config/database.php";
require_once "../functions/includes/functions.php";

checkLogin();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

$quiz_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

// Получаем информацию о тесте
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header("Location: manage_quizzes.php");
    exit();
}

// Получаем все вопросы и ответы
$stmt = $pdo->prepare("
    SELECT q.*, GROUP_CONCAT(a.id, ':::', a.answer_text, ':::', a.is_correct SEPARATOR '|||') as answers
    FROM questions q
    LEFT JOIN answers a ON q.id = a.question_id
    WHERE q.quiz_id = ?
    GROUP BY q.id
");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $pdo->beginTransaction();

        // Обновляем основную информацию о тесте
        $stmt = $pdo->prepare("
            UPDATE quizzes
            SET title = ?, description = ?, time_limit = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST["title"],
            $_POST["description"],
            (int) $_POST["time_limit"],
            $quiz_id,
        ]);

        // Сначала удаляем записи из user_answers
        $pdo->prepare(
            "
            DELETE FROM user_answers
            WHERE answer_id IN (
                SELECT a.id
                FROM answers a
                JOIN questions q ON a.question_id = q.id
                WHERE q.quiz_id = ?
            )
        "
        )->execute([$quiz_id]);

        // Затем удаляем старые ответы
        $pdo->prepare(
            "
            DELETE FROM answers
            WHERE question_id IN (
                SELECT id FROM questions WHERE quiz_id = ?
            )
        "
        )->execute([$quiz_id]);

        // И наконец удаляем вопросы
        $pdo->prepare(
            "
            DELETE FROM questions
            WHERE quiz_id = ?
        "
        )->execute([$quiz_id]);

        // Добавляем обновленные вопросы и ответы
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
        $success = "Тест успешно обновлен";

        // Обновляем данные для отображения
        $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
        $stmt->execute([$quiz_id]);
        $quiz = $stmt->fetch();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Ошибка при обновлении теста: " . $e->getMessage();
    }
}

include "../templates/header.php";
?>

<div class="container">
    <div class="quiz-editor">
        <!-- Заголовок -->
        <div class="page-header">
            <div class="header-content">
                <h1>Редактирование теста</h1>
                <p class="text-secondary">Измените параметры теста и его содержание</p>
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

        <form id="editQuizForm" method="POST" class="quiz-form">
            <!-- Основная информация -->
            <div class="form-section">
                <h2>Основная информация</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title">Название теста</label>
                        <input type="text" id="title" name="title" class="form-control" required
                               value="<?= htmlspecialchars($quiz["title"]) ?>"
                               placeholder="Введите название теста">
                    </div>

                    <div class="form-group">
                        <label for="time_limit">Ограничение по времени (в минутах)</label>
                        <input type="number" id="time_limit" name="time_limit" class="form-control"
                               value="<?= $quiz[
                                   "time_limit"
                               ] ?>" min="1" max="180">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Описание теста</label>
                    <textarea id="description" name="description" class="form-control" rows="3"
                              placeholder="Введите описание теста"><?= htmlspecialchars(
                                  $quiz["description"]
                              ) ?></textarea>
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
                    <!-- Существующие вопросы будут загружены через JavaScript -->
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
                    Сохранить изменения
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='manage_quizzes.php'">
                    Отмена
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.quiz-editor {
    padding: 20px 0;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
}

.header-content h1 {
    font-size: 2.5em;
    margin-bottom: 8px;
    background: linear-gradient(45deg, var(--primary), var(--accent));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.quiz-form {
    max-width: 900px;
    margin: 0 auto;
}

.form-section {
    background: var(--card);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: var(--shadow);
    transition: transform 0.3s ease;
}

.form-section:hover {
    transform: translateY(-2px);
    box-shadow: var(--hover-shadow);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.question-card {
    background: var(--background);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid var(--border);
    transition: all 0.3s ease;
}

.question-card:hover {
    border-color: var(--primary);
    box-shadow: 0 2px 8px rgba(33, 150, 243, 0.1);
}

.question-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.answers-grid {
    display: grid;
    gap: 12px;
    margin-top: 16px;
}

.answer-option {
    display: flex;
    align-items: center;
    gap: 12px;
    background: var(--card);
    padding: 12px;
    border-radius: 8px;
    border: 1px solid var(--border);
    transition: all 0.3s ease;
}

.answer-option:hover {
    border-color: var(--primary);
    background: var(--background);
}

.answer-option input[type="text"] {
    flex: 1;
}

.answer-option input[type="checkbox"],
.answer-option input[type="radio"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.form-actions {
    display: flex;
    gap: 16px;
    justify-content: flex-end;
    margin-top: 32px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-success {
    background: var(--success);
    color: white;
}

.btn-success:hover {
    background: #43a047;
    transform: translateY(-2px);
}

.btn-danger {
    background: var(--error);
    color: white;
    padding: 8px 16px;
}

.btn-danger:hover {
    background: #d32f2f;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }

    .section-header {
        flex-direction: column;
        gap: 16px;
    }

    .form-actions {
        flex-direction: column;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
let questionCount = 0;
const existingQuestions = <?= json_encode($questions) ?>;

function initializeExistingQuestions() {
    existingQuestions.forEach(question => {
        const answers = question.answers.split('|||').map(answer => {
            const [id, text, is_correct] = answer.split(':::');
            return { id, text, is_correct: is_correct === '1' };
        });
        addQuestion(question.question_text, question.type, answers);
    });
}

function addQuestion(questionText = '', type = 'single', existingAnswers = []) {
    const container = document.getElementById('questions-container');
    const questionDiv = document.createElement('div');
    questionDiv.className = 'question-card';
    questionDiv.dataset.questionId = questionCount;

    questionDiv.innerHTML = `
        <div class="question-header">
            <h3>Вопрос ${questionCount + 1}</h3>
            <button type="button" class="btn btn-danger" onclick="removeQuestion(${questionCount})">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
                Удалить
            </button>
        </div>

        <div class="form-group">
            <label>Текст вопроса</label>
            <input type="text" name="questions[${questionCount}][text]"
                   class="form-control" required value="${questionText}"
                   placeholder="Введите текст вопроса">
        </div>

        <div class="form-group">
            <label>Тип вопроса</label>
            <select name="questions[${questionCount}][type]" class="form-control"
                    onchange="updateAnswersType(${questionCount}, this.value)">
                <option value="single" ${type === 'single' ? 'selected' : ''}>Один правильный ответ</option>
                <option value="multiple" ${type === 'multiple' ? 'selected' : ''}>Несколько правильных ответов</option>
            </select>
        </div>

        <div class="answers-container">
            <label>Варианты ответов</label>
            <div class="answers-grid"></div>
            <button type="button" class="btn btn-secondary" onclick="addAnswer(${questionCount})" style="margin-top: 12px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Добавить ответ
            </button>
        </div>
    `;

    container.appendChild(questionDiv);

    if (existingAnswers.length > 0) {
        existingAnswers.forEach(answer => {
            addAnswer(questionCount, answer.text, answer.is_correct === '1');
        });
    } else {
        addAnswer(questionCount);
    }

    questionCount++;
}

function addAnswer(questionId, answerText = '', isCorrect = false) {
    const container = document.querySelector(`[data-question-id="${questionId}"] .answers-grid`);
    const answerCount = container.children.length;

    const answerDiv = document.createElement('div');
    answerDiv.className = 'answer-option';

    const questionType = document.querySelector(`[name="questions[${questionId}][type]"]`).value;
    const inputType = questionType === 'multiple' ? 'checkbox' : 'radio';

    answerDiv.innerHTML = `
        <input type="${inputType}" name="questions[${questionId}][answers][${answerCount}][is_correct]"
               value="1" ${isCorrect ? 'checked' : ''}>
        <input type="text" name="questions[${questionId}][answers][${answerCount}][text]"
               class="form-control" placeholder="Введите вариант ответа"
               required value="${answerText}">
        <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    `;

    container.appendChild(answerDiv);
}

function updateAnswersType(questionId, type) {
    const container = document.querySelector(`[data-question-id="${questionId}"] .answers-grid`);
    const answers = container.querySelectorAll('.answer-option');

    answers.forEach(answer => {
        const inputType = type === 'multiple' ? 'checkbox' : 'radio';
        const oldInput = answer.querySelector('input[type="checkbox"], input[type="radio"]');
        const newInput = document.createElement('input');
        newInput.type = inputType;
        newInput.name = oldInput.name;
        newInput.value = oldInput.value;
        newInput.checked = oldInput.checked;
        oldInput.parentNode.replaceChild(newInput, oldInput);
    });
}

function removeQuestion(questionId) {
    if (confirm('Вы уверены, что хотите удалить этот вопрос?')) {
        document.querySelector(`[data-question-id="${questionId}"]`).remove();
    }
}

// Инициализация существующих вопросов при загрузке страницы
document.addEventListener('DOMContentLoaded', initializeExistingQuestions);
</script>

<?php include "../templates/footer.php"; ?>
