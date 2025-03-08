<?php
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'quiz_system';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "База данных `$dbname` создана или уже существует.<br>";


    $pdo->exec("USE `$dbname`");


    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `username` VARCHAR(50) NOT NULL,
          `email` VARCHAR(100) NOT NULL,
          `password` VARCHAR(255) NOT NULL,
          `role` ENUM('user','admin') DEFAULT 'user',
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `username` (`username`),
          UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Таблица <code>users</code> создана.<br>";


    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `quizzes` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `title` VARCHAR(255) NOT NULL,
          `description` TEXT DEFAULT NULL,
          `created_by` INT(11) DEFAULT NULL,
          `time_limit` INT(11) DEFAULT 0,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `created_by` (`created_by`),
          CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Таблица <code>quizzes</code> создана.<br>";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `questions` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `quiz_id` INT(11) DEFAULT NULL,
          `question_text` TEXT NOT NULL,
          `type` ENUM('single','multiple') DEFAULT 'single',
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `quiz_id` (`quiz_id`),
          CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Таблица <code>questions</code> создана.<br>";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `answers` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `question_id` INT(11) DEFAULT NULL,
          `answer_text` TEXT NOT NULL,
          `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
          PRIMARY KEY (`id`),
          KEY `question_id` (`question_id`),
          CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Таблица <code>answers</code> создана.<br>";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `user_results` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `user_id` INT(11) NOT NULL,
          `quiz_id` INT(11) NOT NULL,
          `score` INT(11) DEFAULT 0,
          `completed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `user_id` (`user_id`),
          KEY `quiz_id` (`quiz_id`),
          CONSTRAINT `user_results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
          CONSTRAINT `user_results_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Таблица <code>user_results</code> создана.<br>";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `user_answers` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `attempt_id` INT(11) NOT NULL,
          `user_id` INT(11) NOT NULL,
          `question_id` INT(11) NOT NULL,
          `answer_id` INT(11) NOT NULL,
          `is_correct` TINYINT(1) DEFAULT 0,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `attempt_id` (`attempt_id`),
          KEY `user_id` (`user_id`),
          KEY `question_id` (`question_id`),
          KEY `answer_id` (`answer_id`),
          CONSTRAINT `user_answers_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `user_results` (`id`) ON DELETE CASCADE,
          CONSTRAINT `user_answers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
          CONSTRAINT `user_answers_ibfk_3` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`),
          CONSTRAINT `user_answers_ibfk_4` FOREIGN KEY (`answer_id`) REFERENCES `answers` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Таблица <code>user_answers</code> создана.<br>";

    echo "<br>Миграция выполнена успешно.";
} catch (PDOException $e) {
    echo "Ошибка миграции: " . $e->getMessage();
}
