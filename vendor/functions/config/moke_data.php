<?php

$host     = 'localhost';
$dbname   = 'quiz_system';
$dbUser   = 'root';
$dbPass   = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmtUser = $pdo->prepare("
        INSERT INTO users (username, email, password, role) 
        VALUES (:username, :email, :password, :role)
    ");

    $stmtUser->execute([
        ':username' => 'user',
        ':email'    => 'user@example.com',
        ':password' => password_hash('user', PASSWORD_DEFAULT),
        ':role'     => 'user'
    ]);
    $testUserId = $pdo->lastInsertId();


    $stmtUser->execute([
        ':username' => 'admin',
        ':email'    => 'admin@example.com',
        ':password' => password_hash('admin', PASSWORD_DEFAULT),
        ':role'     => 'admin'
    ]);
    $adminUserId = $pdo->lastInsertId();

    
    $stmtQuiz = $pdo->prepare("
        INSERT INTO quizzes (title, description, created_by, time_limit) 
        VALUES (:title, :description, :created_by, :time_limit)
    ");
    $quizzes = [
        [
            'title'       => 'Test Quiz 1',
            'description' => 'Описание теста 1',
            'created_by'  => $adminUserId,  
            'time_limit'  => 10
        ],
        [
            'title'       => 'Test Quiz 2',
            'description' => 'Описание теста 2',
            'created_by'  => $adminUserId,
            'time_limit'  => 20
        ],
        [
            'title'       => 'Test Quiz 3',
            'description' => 'Описание теста 3',
            'created_by'  => $adminUserId,
            'time_limit'  => 30
        ]
    ];
    $quizIds = [];
    foreach ($quizzes as $quiz) {
        $stmtQuiz->execute($quiz);
        $quizIds[] = $pdo->lastInsertId();
    }

    $stmtQuestion = $pdo->prepare("
        INSERT INTO questions (quiz_id, question_text, type)
        VALUES (:quiz_id, :question_text, :type)
    ");
    $stmtAnswer = $pdo->prepare("
        INSERT INTO answers (question_id, answer_text, is_correct)
        VALUES (:question_id, :answer_text, :is_correct)
    ");

    $stmtQuestion->execute([
        ':quiz_id'       => $quizIds[0],
        ':question_text' => 'What is 2+2?',
        ':type'          => 'single'
    ]);
    $q1Id = $pdo->lastInsertId();
    $stmtAnswer->execute([
        ':question_id' => $q1Id,
        ':answer_text' => '4',
        ':is_correct'  => 1
    ]);
    $stmtAnswer->execute([
        ':question_id' => $q1Id,
        ':answer_text' => '5',
        ':is_correct'  => 0
    ]);

    $stmtQuestion->execute([
        ':quiz_id'       => $quizIds[0],
        ':question_text' => 'Select prime numbers',
        ':type'          => 'multiple'
    ]);
    $q2Id = $pdo->lastInsertId();
    $stmtAnswer->execute([
        ':question_id' => $q2Id,
        ':answer_text' => '2',
        ':is_correct'  => 1
    ]);
    $stmtAnswer->execute([
        ':question_id' => $q2Id,
        ':answer_text' => '4',
        ':is_correct'  => 0
    ]);
    $stmtAnswer->execute([
        ':question_id' => $q2Id,
        ':answer_text' => '3',
        ':is_correct'  => 1
    ]);

    $stmtQuestion->execute([
        ':quiz_id'       => $quizIds[1],
        ':question_text' => 'Capital of France?',
        ':type'          => 'single'
    ]);
    $q3Id = $pdo->lastInsertId();
    $stmtAnswer->execute([
        ':question_id' => $q3Id,
        ':answer_text' => 'Paris',
        ':is_correct'  => 1
    ]);
    $stmtAnswer->execute([
        ':question_id' => $q3Id,
        ':answer_text' => 'Lyon',
        ':is_correct'  => 0
    ]);

    $stmtQuestion->execute([
        ':quiz_id'       => $quizIds[1],
        ':question_text' => 'Which of these are programming languages?',
        ':type'          => 'multiple'
    ]);
    $q4Id = $pdo->lastInsertId();
    $stmtAnswer->execute([
        ':question_id' => $q4Id,
        ':answer_text' => 'PHP',
        ':is_correct'  => 1
    ]);
    $stmtAnswer->execute([
        ':question_id' => $q4Id,
        ':answer_text' => 'HTML',
        ':is_correct'  => 0
    ]);
    $stmtAnswer->execute([
        ':question_id' => $q4Id,
        ':answer_text' => 'JavaScript',
        ':is_correct'  => 1
    ]);

    $stmtQuestion->execute([
        ':quiz_id'       => $quizIds[2],
        ':question_text' => 'What is the color of the sky?',
        ':type'          => 'single'
    ]);
    $q5Id = $pdo->lastInsertId();
    $stmtAnswer->execute([
        ':question_id' => $q5Id,
        ':answer_text' => 'Blue',
        ':is_correct'  => 1
    ]);
    $stmtAnswer->execute([
        ':question_id' => $q5Id,
        ':answer_text' => 'Green',
        ':is_correct'  => 0
    ]);

    $stmtQuestion->execute([
        ':quiz_id'       => $quizIds[2],
        ':question_text' => 'Select even numbers',
        ':type'          => 'multiple'
    ]);
    $q6Id = $pdo->lastInsertId();
    $stmtAnswer->execute([
        ':question_id' => $q6Id,
        ':answer_text' => '2',
        ':is_correct'  => 1
    ]);
    $stmtAnswer->execute([
        ':question_id' => $q6Id,
        ':answer_text' => '3',
        ':is_correct'  => 0
    ]);
    $stmtAnswer->execute([
        ':question_id' => $q6Id,
        ':answer_text' => '4',
        ':is_correct'  => 1
    ]);

    echo "Данные успешно добавлены: тестовый пользователь, админ и 3 теста с вопросами и ответами.";
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
