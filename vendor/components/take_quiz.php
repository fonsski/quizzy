<?php
require_once "../functions/config/database.php";
require_once "../functions/includes/functions.php";
checkLogin();

$quiz_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

// Получаем информацию о викторине
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header("Location: index.php");
    exit();
}

// Получаем вопросы
$stmt = $pdo->prepare("
    SELECT q.*, GROUP_CONCAT(a.id, ':::', a.answer_text SEPARATOR '|||') as answers
    FROM questions q
    LEFT JOIN answers a ON q.id = a.question_id
    WHERE q.quiz_id = ?
    GROUP BY q.id
");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();

include "../templates/header.php";
?>

<div class="container">
    <div class="quiz-container">
        <div class="quiz-header">
            <div class="quiz-info">
                <h1><?= htmlspecialchars($quiz["title"]) ?></h1>
                <p class="quiz-description"><?= htmlspecialchars(
                                                $quiz["description"]
                                            ) ?></p>
            </div>

            <div class="quiz-timer">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span id="timer">00:00</span>
            </div>
        </div>

        <div class="quiz-progress">
            <div class="progress-bar">
                <div class="progress-fill" style="width: 0%"></div>
            </div>
            <span class="progress-text">Вопрос <span id="currentQuestion">1</span> из <?= count(
                                                                                            $questions
                                                                                        ) ?></span>
        </div>

        <form id="quizForm" data-quiz-id="<?= $quiz_id ?>" class="quiz-form">
            <div class="questions-container">
                <?php foreach ($questions as $index => $question): ?>
                    <div class="question-card" data-question="<?= $index +
                                                                    1 ?>" style="display: <?= $index === 0
                                                                                                ? "block"
                                                                                                : "none" ?>">
                        <div class="question-header">
                            <span class="question-number">Вопрос <?= $index +
                                                                        1 ?></span>
                            <span class="question-type"><?= $question["type"] === "multiple"
                                                            ? "Несколько ответов"
                                                            : "Один ответ" ?></span>
                        </div>

                        <h3 class="question-text"><?= htmlspecialchars(
                                                        $question["question_text"]
                                                    ) ?></h3>

                        <div class="answers-grid">
                            <?php
                            $answers = explode("|||", $question["answers"]);
                            foreach ($answers as $answer):
                                list($answer_id, $answer_text) = explode(
                                    ":::",
                                    $answer
                                ); ?>
                                <label class="answer-option">
                                    <input
                                        type="<?= $question["type"] ===
                                                    "multiple"
                                                    ? "checkbox"
                                                    : "radio" ?>"
                                        name="question_<?= $question["id"] .
                                                            ($question["type"] ===
                                                                "multiple"
                                                                ? "[]"
                                                                : "") ?>"
                                        value="<?= $answer_id ?>"
                                        class="answer-input">
                                    <span class="answer-text"><?= htmlspecialchars(
                                                                    $answer_text
                                                                ) ?></span>
                                    <span class="answer-check"></span>
                                </label>
                            <?php
                            endforeach;
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="quiz-navigation">
                <button type="button" id="prevQuestion" class="btn btn-secondary" disabled>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Предыдущий
                </button>

                <button type="button" id="nextQuestion" class="btn btn-primary">
                    Следующий
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </button>

                <button type="submit" id="submitQuiz" class="btn btn-success" style="display: none;">
                    Завершить тест
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const quizForm = document.getElementById('quizForm');
        const prevButton = document.getElementById('prevQuestion');
        const nextButton = document.getElementById('nextQuestion');
        const submitButton = document.getElementById('submitQuiz');
        const progressFill = document.querySelector('.progress-fill');
        const currentQuestionSpan = document.getElementById('currentQuestion');
        const timerElement = document.getElementById('timer');

        let currentQuestion = 1;
        const totalQuestions = <?= count($questions) ?>;
        let timeLeft = <?= $quiz["time_limit"] ?> * 60;

        // Функция обновления прогресса
        function updateProgress() {
            const progress = (currentQuestion / totalQuestions) * 100;
            progressFill.style.width = `${progress}%`;
            currentQuestionSpan.textContent = currentQuestion;

            // Управление кнопками
            prevButton.disabled = currentQuestion === 1;
            nextButton.style.display = currentQuestion === totalQuestions ? 'none' : 'flex';
            submitButton.style.display = currentQuestion === totalQuestions ? 'flex' : 'none';
        }

        // Функция показа вопроса
        function showQuestion(questionNumber) {
            document.querySelectorAll('.question-card').forEach(card => {
                card.style.display = 'none';
            });
            document.querySelector(`[data-question="${questionNumber}"]`).style.display = 'block';
            updateProgress();
        }

        // Обработчики навигации
        prevButton.addEventListener('click', () => {
            if (currentQuestion > 1) {
                currentQuestion--;
                showQuestion(currentQuestion);
            }
        });

        nextButton.addEventListener('click', () => {
            if (currentQuestion < totalQuestions) {
                currentQuestion++;
                showQuestion(currentQuestion);
            }
        });
        // В take_quiz.php
        quizForm.addEventListener('submit', function(e) {
            e.preventDefault();

            if (!confirm('Вы уверены, что хотите завершить тест?')) {
                return;
            }

            clearInterval(timer);

            const formData = new FormData(this);
            formData.append('quiz_id', this.dataset.quizId);

            fetch('/vendor/components/submit_quiz.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = `view_results.php?attempt_id=${data.attempt_id}`;
                    } else {
                        throw new Error(data.error || 'Произошла ошибка при сохранении результатов');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Произошла ошибка при отправке теста. Пожалуйста, попробуйте снова.');
                });
        });
        // Таймер
        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerElement.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

            if (timeLeft <= 0) {
                quizForm.submit();
            } else {
                timeLeft--;
            }
        }

        const timer = setInterval(updateTimer, 1000);

        // Анимация ответов
        document.querySelectorAll('.answer-option').forEach(option => {
            option.addEventListener('click', function() {
                const input = this.querySelector('input');
                if (input.type === 'radio') {
                    this.closest('.answers-grid').querySelectorAll('.answer-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                }
                this.classList.toggle('selected');
            });
        });

        // Отправка формы
        quizForm.addEventListener('submit', function(e) {
            isQuizSubmitted = true;
            e.preventDefault();

            if (!confirm('Вы уверены, что хотите завершить тест?')) {
                return;
            }

            clearInterval(timer);

            const formData = new FormData(this);
            formData.append('quiz_id', this.dataset.quizId);

            fetch('/vendor/components/submit_quiz.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = `results.php?quiz_id=${data.quiz_id}&attempt_id=${data.attempt_id}`;
                    } else {
                        alert('Ошибка при отправке теста. Пожалуйста, попробуйте снова.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Произошла ошибка. Пожалуйста, попробуйте снова.');
                });
        });

        // Инициализация
        updateProgress();
    });

    // Предупреждение при попытке покинуть страницу
    let isQuizSubmitted = false;

    window.addEventListener('beforeunload', function(e) {
        if (!isQuizSubmitted) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
</script>
<?php include "../templates/footer.php";; ?>