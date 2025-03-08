<?php
function seedUsers(PDO $pdo)
{
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)");
    $users = [
        [
            'username' => 'fon',
            'email'    => 'fon@gmail.com',
            'password' => password_hash('password1', PASSWORD_DEFAULT),
            'role'     => 'user'
        ],
        [
            'username' => 'tester',
            'email'    => 'test@test.com',
            'password' => password_hash('password2', PASSWORD_DEFAULT),
            'role'     => 'user'
        ],
        [
            'username' => 'fon2',
            'email'    => 'fon2@gmail.com',
            'password' => password_hash('password3', PASSWORD_DEFAULT),
            'role'     => 'admin'
        ],
    ];
    foreach ($users as $user) {
        $stmt->execute($user);
    }
    echo "Пользователи добавлены.<br>";
}

function seedQuizzes(PDO $pdo)
{
    $stmt = $pdo->prepare("INSERT INTO quizzes (title, description, created_by, time_limit) VALUES (:title, :description, :created_by, :time_limit)");
    $quizzes = [
        ['title' => 'Тест3',  'description' => 'Тест3',  'created_by' => 1, 'time_limit' => 30],
        ['title' => 'Тест2',  'description' => 'Тест2',  'created_by' => 1, 'time_limit' => 1],
        ['title' => 'Тест 1', 'description' => 'Тест 1', 'created_by' => 3, 'time_limit' => 10],
    ];
    foreach ($quizzes as $quiz) {
        $stmt->execute($quiz);
    }
    echo "Викторины добавлены.<br>";
}

function seedQuestions(PDO $pdo)
{
    $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, type) VALUES (:quiz_id, :question_text, :type)");
    $questions = [
        ['quiz_id' => 3, 'question_text' => '2+2',   'type' => 'single'],
        ['quiz_id' => 2, 'question_text' => 'Тест2',  'type' => 'single'],
        ['quiz_id' => 2, 'question_text' => 'Тест2',  'type' => 'single'],
        ['quiz_id' => 1, 'question_text' => 'Тест3',  'type' => 'single'],
    ];
    foreach ($questions as $question) {
        $stmt->execute($question);
    }
    echo "Вопросы добавлены.<br>";
}

function seedAnswers(PDO $pdo)
{
    $stmt = $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (:question_id, :answer_text, :is_correct)");
    $answers = [
        ['question_id' => 1, 'answer_text' => '4',  'is_correct' => 1],
        ['question_id' => 1, 'answer_text' => '5',  'is_correct' => 0],
        ['question_id' => 1, 'answer_text' => '22', 'is_correct' => 0],
        ['question_id' => 2, 'answer_text' => 'Правильный ответ 1', 'is_correct' => 1],
        ['question_id' => 2, 'answer_text' => 'Тест2',              'is_correct' => 0],
        ['question_id' => 3, 'answer_text' => 'Тест2',              'is_correct' => 0],
        ['question_id' => 3, 'answer_text' => 'Правильный ответ',    'is_correct' => 1],
        ['question_id' => 4, 'answer_text' => 'Правильный ответ',    'is_correct' => 1],
        ['question_id' => 4, 'answer_text' => 'Тест3',              'is_correct' => 0],
    ];
    foreach ($answers as $answer) {
        $stmt->execute($answer);
    }
    echo "Ответы добавлены.<br>";
}

function seedUserResults(PDO $pdo)
{
    $stmt = $pdo->prepare("INSERT INTO user_results (user_id, quiz_id, score) VALUES (:user_id, :quiz_id, :score)");
    $results = [
        ['user_id' => 3, 'quiz_id' => 3, 'score' => 1],
        ['user_id' => 3, 'quiz_id' => 3, 'score' => 1],
        ['user_id' => 3, 'quiz_id' => 3, 'score' => 1],
    ];
    foreach ($results as $result) {
        $stmt->execute($result);
    }
    echo "Результаты пользователей добавлены.<br>";
}

function seedUserAnswers(PDO $pdo)
{
    $stmt = $pdo->prepare("INSERT INTO user_answers (attempt_id, user_id, question_id, answer_id, is_correct) VALUES (:attempt_id, :user_id, :question_id, :answer_id, :is_correct)");
    $userAnswers = [
        ['attempt_id' => 1, 'user_id' => 3, 'question_id' => 2, 'answer_id' => 4, 'is_correct' => 1],
        ['attempt_id' => 1, 'user_id' => 3, 'question_id' => 3, 'answer_id' => 7, 'is_correct' => 1],
        ['attempt_id' => 2, 'user_id' => 3, 'question_id' => 1, 'answer_id' => 1, 'is_correct' => 0],
    ];
    foreach ($userAnswers as $ua) {
        $stmt->execute($ua);
    }
    echo "Ответы пользователей добавлены.<br>";
}


function runSeeding(PDO $pdo)
{
    seedUsers($pdo);
    seedQuizzes($pdo);
    seedQuestions($pdo);
    seedAnswers($pdo);
    seedUserResults($pdo);
    seedUserAnswers($pdo);
}

$host = 'localhost';
$dbname = 'quiz_system';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    runSeeding($pdo);
    echo "<br>Тестовые данные успешно добавлены.";
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
