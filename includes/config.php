<?php
/**
 * UFAvia — Конфигурация базы данных для OpenServer Panel
 * Поддерживаемые СУБД: MySQL 5.7+ / MariaDB
 * Версия PHP: 7.2+
 */

// Хост MySQL (в OpenServer Panel обычно 127.0.0.1 или localhost)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');

// Стандартный порт MySQL
define('DB_PORT', getenv('DB_PORT') ?: '3306');

// Имя базы данных
define('DB_NAME', getenv('DB_NAME') ?: 'ufavia');

// Имя пользователя (по умолчанию в OpenServer: root)
define('DB_USER', getenv('DB_USER') ?: 'root');

// Пароль пользователя (по умолчанию в OpenServer пустой)
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

// Кодировка подключения (полная поддержка русского языка и эмодзи)
define('DB_CHARSET', 'utf8mb4');

// Путь к файлу резервной локальной базы SQLite (на случай, если MySQL не запущен)
define('SQLITE_FILE', __DIR__ . '/../database/ufavia.db');
