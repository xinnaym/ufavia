<?php
/**
 * UFAvia — Выход из системы
 */

require_once __DIR__ . '/../includes/auth.php';

logoutUser();
setFlash('success', 'Вы успешно вышли из системы');
header('Location: ../index.php');
exit;
