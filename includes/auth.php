<?php
/**
 * UFAvia — Модуль аутентификации и управления пользователями
 * Поддерживаемые роли: 'admin', 'user', 'guest'
 */

require_once __DIR__ . '/db.php';

function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

initSession();

/**
 * Получение текущего пользователя
 * @return array|null Возвращает данные пользователя или null для гостя ('guest')
 */
function getCurrentUser() {
    if (!empty($_SESSION['user_id'])) {
        $db = getDb();
        $stmt = $db->prepare("SELECT id, name, email, phone, role, created_at FROM users WHERE id = :id");
        $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
        $res = $stmt->execute();
        $user = $res->fetchArray(SQLITE3_ASSOC);
        if ($user) {
            return $user;
        }
    }
    return null;
}

/**
 * Проверка авторизации
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Проверка прав администратора
 */
function isAdmin(): bool {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

/**
 * Вход в систему
 */
function loginUser(string $email, string $password): array {
    $email = trim(mb_strtolower($email));
    $db = getDb();
    
    $stmt = $db->prepare("SELECT * FROM users WHERE LOWER(email) = :email");
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $res = $stmt->execute();
    $user = $res->fetchArray(SQLITE3_ASSOC);

    if (!$user) {
        return ['success' => false, 'error' => 'Пользователь с таким email не найден'];
    }

    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => 'Неверный пароль'];
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];

    return ['success' => true, 'user' => $user];
}

/**
 * Регистрация нового пользователя
 */
function registerUser(string $name, string $email, string $phone, string $password): array {
    $name = trim($name);
    $email = trim(mb_strtolower($email));
    $phone = trim($phone);

    if (empty($name) || empty($email) || empty($password)) {
        return ['success' => false, 'error' => 'Заполните все обязательные поля'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Некорректный формат email'];
    }

    if (mb_strlen($password) < 4) {
        return ['success' => false, 'error' => 'Пароль должен содержать не менее 4 символов'];
    }

    $db = getDb();

    // Проверка уникальности email
    $stmt = $db->prepare("SELECT id FROM users WHERE LOWER(email) = :email");
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $res = $stmt->execute();
    if ($res->fetchArray()) {
        return ['success' => false, 'error' => 'Пользователь с таким email уже зарегистрирован'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $insert = $db->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (:name, :email, :phone, :password, 'user')");
    $insert->bindValue(':name', $name, SQLITE3_TEXT);
    $insert->bindValue(':email', $email, SQLITE3_TEXT);
    $insert->bindValue(':phone', $phone, SQLITE3_TEXT);
    $insert->bindValue(':password', $hash, SQLITE3_TEXT);
    
    if ($insert->execute()) {
        $newId = $db->lastInsertRowID();
        $_SESSION['user_id'] = $newId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = 'user';
        return ['success' => true, 'user_id' => $newId];
    }

    return ['success' => false, 'error' => 'Ошибка при регистрации. Попробуйте позже.'];
}

/**
 * Выход из системы
 */
function logoutUser() {
    initSession();
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_role']);
}

/**
 * Обновление профиля пользователя
 */
function updateUserProfile(int $userId, string $name, string $email, string $phone, ?string $newPassword = null): array {
    $db = getDb();
    
    if (!empty($newPassword)) {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET name = :name, email = :email, phone = :phone, password = :password WHERE id = :id");
        $stmt->bindValue(':password', $hash, SQLITE3_TEXT);
    } else {
        $stmt = $db->prepare("UPDATE users SET name = :name, email = :email, phone = :phone WHERE id = :id");
    }
    
    $stmt->bindValue(':name', trim($name), SQLITE3_TEXT);
    $stmt->bindValue(':email', trim(mb_strtolower($email)), SQLITE3_TEXT);
    $stmt->bindValue(':phone', trim($phone), SQLITE3_TEXT);
    $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
    
    if ($stmt->execute()) {
        $_SESSION['user_name'] = trim($name);
        return ['success' => true];
    }
    return ['success' => false, 'error' => 'Не удалось сохранить изменения'];
}

/**
 * Флеш-сообщения
 */
function setFlash(string $type, string $message) {
    initSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    initSession();
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
