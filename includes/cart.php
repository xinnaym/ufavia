<?php
/**
 * UFAvia — Модуль управления корзиной билетов
 * Совместимость: PHP 7.2+, MySQL 5.7+ (PDO) + Session Fallback
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/flights.php';

function getSessionId(): string {
    initSession();
    return session_id();
}

/**
 * Получение всех билетов в корзине текущего пользователя
 */
function getCartItems(): array {
    initSession();
    $items = [];
    $sessionId = getSessionId();

    // 1. Пытаемся получить из базы данных
    try {
        $db = getDb();
        $stmt = $db->prepare("SELECT c.id as cart_id, c.seat_number, c.price as item_price,
                                     f.* 
                              FROM cart c
                              JOIN flights f ON c.flight_id = f.id
                              WHERE c.session_id = :sid
                              ORDER BY c.id ASC");
        $stmt->execute([':sid' => $sessionId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $items = [];
    }

    // 2. Если в БД пусто, проверяем хранилище в сессии (гарантия надежности)
    if (empty($items) && !empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $cartId => $cartRow) {
            $f = getFlightById((int)$cartRow['flight_id']);
            if ($f) {
                $f['cart_id'] = $cartId;
                $f['seat_number'] = $cartRow['seat_number'];
                $f['item_price'] = $cartRow['price'];
                $items[] = $f;
            }
        }
    }

    return $items;
}

/**
 * Добавление билета в корзину
 */
function addToCart(int $flightId, string $seatNumber, int $price): bool {
    initSession();
    $sessionId = getSessionId();
    $seatNumber = trim($seatNumber ?: '2B');
    $price = (int)$price;

    // 1. Сохраняем в сессии
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    $cartKey = $flightId . '_' . $seatNumber;
    $_SESSION['cart'][$cartKey] = [
        'flight_id' => $flightId,
        'seat_number' => $seatNumber,
        'price' => $price
    ];

    // 2. Сохраняем в базе данных MySQL
    try {
        $db = getDb();
        $check = $db->prepare("SELECT id FROM cart WHERE session_id = :sid AND flight_id = :fid AND seat_number = :seat");
        $check->execute([
            ':sid' => $sessionId,
            ':fid' => $flightId,
            ':seat' => $seatNumber
        ]);
        if (!$check->fetch()) {
            $stmt = $db->prepare("INSERT INTO cart (session_id, flight_id, seat_number, price) VALUES (:sid, :fid, :seat, :price)");
            $stmt->execute([
                ':sid' => $sessionId,
                ':fid' => $flightId,
                ':seat' => $seatNumber,
                ':price' => $price
            ]);
        }
    } catch (Exception $e) {
        // Ошибка БД не блокирует добавление, так как в сессии билет уже сохранен
    }

    return true;
}

/**
 * Удаление билета из корзины
 */
function removeFromCart($cartId): bool {
    initSession();
    $sessionId = getSessionId();

    // Удаляем из сессии
    if (isset($_SESSION['cart'][$cartId])) {
        unset($_SESSION['cart'][$cartId]);
    }
    if (is_numeric($cartId)) {
        foreach ($_SESSION['cart'] ?? [] as $k => $v) {
            if ($v['flight_id'] == $cartId) {
                unset($_SESSION['cart'][$k]);
            }
        }
    }

    // Удаляем из БД
    try {
        $db = getDb();
        $stmt = $db->prepare("DELETE FROM cart WHERE (id = :id OR flight_id = :fid) AND session_id = :sid");
        $stmt->execute([
            ':id' => is_numeric($cartId) ? (int)$cartId : 0,
            ':fid' => is_numeric($cartId) ? (int)$cartId : 0,
            ':sid' => $sessionId
        ]);
    } catch (Exception $e) {}

    return true;
}

/**
 * Очистка корзины
 */
function clearCart(): bool {
    initSession();
    $_SESSION['cart'] = [];
    $sessionId = getSessionId();

    try {
        $db = getDb();
        $stmt = $db->prepare("DELETE FROM cart WHERE session_id = :sid");
        $stmt->execute([':sid' => $sessionId]);
    } catch (Exception $e) {}

    return true;
}

/**
 * Итоговые суммы корзины
 */
function getCartSummary(): array {
    $items = getCartItems();
    $count = count($items);
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += (int)($item['item_price'] ?? 0);
    }
    $serviceFeePerTicket = 150;
    $serviceFee = $count * $serviceFeePerTicket;
    $total = $subtotal + $serviceFee;

    return [
        'count' => $count,
        'subtotal' => $subtotal,
        'service_fee' => $serviceFee,
        'total' => $total,
        'items' => $items
    ];
}
