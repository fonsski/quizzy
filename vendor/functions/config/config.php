<?php
// Основные настройки
define('DEBUG_MODE', false);
define('SESSION_LIFETIME', 3600);
define('MIN_PASSWORD_LENGTH', 8);
define('COOLDOWN_MINUTES', 5);

// Настройки безопасности
define('HASH_ALGO', PASSWORD_DEFAULT);
define('HASH_COST', 12);
define('SECURE_COOKIES', true);
define('SESSION_COOKIE_HTTPONLY', true);
define('SESSION_COOKIE_SAMESITE', 'Lax');
define('SESSION_REGENERATE_TIME', 300); // 5 минут

// Лимиты
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 минут
define('MAX_QUESTIONS_PER_QUIZ', 100);
define('MIN_QUESTIONS_PER_QUIZ', 1);

// Настройки загрузки файлов
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Настройки базы данных
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');
