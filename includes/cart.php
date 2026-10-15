<?php
/**
 * UFAvia — Модуль управления корзиной билетов
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function getSessionId(): string {
    initSession();
    return session_id();
}

/**
 * Получение всех билетов в корзине текущего пользователя
 */
function getCartItems(): array {
    $db = getDb();
    $sessionId = getSessionId();

    $stmt = $db->prepare("SELECT c.id as cart_id, c.seat_number, c.price as item_price,
                                 f.* 
                          FROM cart c
                          JOIN flights f ON c.flight_id = f.id
                          WHERE c.session_id = :sid
                          ORDER BY c.id ASC");
    $stmt->bindValue(':sid', $sessionId, SQLITE3_TEXT);
    $res = $stmt->execute();

    $items = [];
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $items[] = $row;
    }
    return $items;
}

/**
 * Добавление билета в корзину
 */
function addToCart(int $flightId, string $seatNumber, int $price): bool {
    $db = getDb();
    $sessionId = getSessionId();

    // Проверяем, не добавлен ли уже этот рейс с этим местом
    $check = $db->prepare("SELECT id FROM cart WHERE session_id = :sid AND flight_id = :fid AND seat_number = :seat");
    $check->bindValue(':sid', $sessionId, SQLITE3_TEXT);
    $check->bindValue(':fid', $flightId, SQLITE3_INTEGER);
    $check->bindValue(':seat', $seatNumber, SQLITE3_TEXT);
    $res = $check->execute();
    if ($res->fetchArray()) {
        return true; // уже в корзине
    }

    $stmt = $db->prepare("INSERT INTO cart (session_id, flight_id, seat_number, price) VALUES (:sid, :fid, :seat, :price)");
    $stmt->bindValue(':sid', $sessionId, SQLITE3_TEXT);
    $stmt->bindValue(':fid', $flightId, SQLITE3_INTEGER);
    $stmt->bindValue(':seat', $seatNumber, SQLITE3_TEXT);
    $stmt->bindValue(':price', $price, SQLITE3_INTEGER);

    return (bool)$stmt->execute();
}

/**
 * Удаление билета из корзины
 */
function removeFromCart(int $cartId): bool {
    $db = getDb();
    $sessionId = getSessionId();

    $stmt = $db->prepare("DELETE FROM cart WHERE id = :id AND session_id = :sid");
    $stmt->bindValue(':id', $cartId, SQLITE3_INTEGER);
    $stmt->bindValue(':sid', $sessionId, SQLITE3_TEXT);

    return (bool)$stmt->execute();
}

/**
 * Очистка корзины
 */
function clearCart(): bool {
    $db = getDb();
    $sessionId = getSessionId();

    $stmt = $db->prepare("DELETE FROM cart WHERE session_id = :sid");
    $stmt->bindValue(':sid', $sessionId, SQLITE3_TEXT);

    return (bool)$stmt->execute();
}

/**
 * Итоговые суммы корзины
 */
function getCartSummary(): array {
    $items = getCartItems();
    $count = count($items);
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += (int)$item['item_price'];
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
