<?php
/**
 * UFAvia — Встроенный маршрутизатор для PHP 7.2+ dev server
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$filePath = __DIR__ . $uri;

// Отдача статических файлов (css, js, svg, png, jpg, woff, woff2, ttf)
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    if (in_array(strtolower($ext), ['css', 'js', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'woff', 'woff2', 'ttf', 'ico'])) {
        return false; // встроенный сервер отдаст файл с правильным mime-type
    }
}

// Маршрутизация старых .html на новые .php
$htmlToPhpMap = [
    '/' => '/index.php',
    '/index.html' => '/index.php',
    '/pages/cart.html' => '/pages/cart.php',
    '/pages/search.html' => '/pages/search.php',
    '/pages/tickets.html' => '/pages/tickets.php',
    '/pages/order.html' => '/pages/order.php',
    '/pages/checkout.html' => '/pages/checkout.php',
    '/pages/profile.html' => '/pages/profile.php',
    '/cart.html' => '/pages/cart.php',
    '/search.html' => '/pages/search.php',
    '/tickets.html' => '/pages/tickets.php',
    '/order.html' => '/pages/order.php',
    '/checkout.html' => '/pages/checkout.php',
    '/profile.html' => '/pages/profile.php',
    '/cart' => '/pages/cart.php',
    '/search' => '/pages/search.php',
    '/tickets' => '/pages/tickets.php',
    '/order' => '/pages/order.php',
    '/checkout' => '/pages/checkout.php',
    '/admin' => '/pages/admin.php',
    '/admin.html' => '/pages/admin.php',
    '/order.php' => '/pages/order.php',
    '/cart.php' => '/pages/cart.php',
    '/search.php' => '/pages/search.php',
    '/tickets.php' => '/pages/tickets.php',
    '/checkout.php' => '/pages/checkout.php',
    '/profile.php' => '/pages/profile.php',
    '/admin.php' => '/pages/admin.php',
    '/login.php' => '/pages/login.php',
    '/logout.php' => '/pages/logout.php',
];

if (isset($htmlToPhpMap[$uri])) {
    require __DIR__ . $htmlToPhpMap[$uri];
    exit;
}

if (file_exists($filePath) && substr($filePath, -4) === '.php') {
    require $filePath;
    exit;
}

// По умолчанию отдаем index.php
require __DIR__ . '/index.php';
