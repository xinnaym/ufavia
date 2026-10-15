<?php
/**
 * UFAvia — Модуль работы с заказами
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/flights.php';
require_once __DIR__ . '/cart.php';

/**
 * Создание нового заказа на основе билетов из корзины
 */
function createOrder(array $data, array $items): array {
    if (empty($items)) {
        return ['success' => false, 'error' => 'Корзина пуста'];
    }

    $db = getDb();

    // Генерация красивого номера заказа
    $orderNumber = 'UF-' . date('Y') . '-' . str_pad((string)mt_rand(100, 99999), 5, '0', STR_PAD_LEFT);

    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += (int)$item['item_price'];
    }

    $insurance = !empty($data['insurance']) ? 450 : 0;
    $transfer  = !empty($data['transfer'])  ? 1200 : 0;
    $meal      = !empty($data['meal'])      ? 350 : 0;
    $sms       = !empty($data['sms'])       ? 99 : 0;
    $serviceFee = count($items) * 150;
    $totalAmount = $subtotal + $insurance + $transfer + $meal + $sms + $serviceFee;

    $userId = !empty($data['user_id']) ? (int)$data['user_id'] : null;

    $stmt = $db->prepare("INSERT INTO orders (
        order_number, user_id, passenger_name, passenger_email, passenger_phone,
        passenger_passport, passenger_dob, insurance, transfer, meal, sms,
        service_fee, total_amount, payment_method, delivery_method, status
    ) VALUES (
        :num, :uid, :pname, :pemail, :pphone,
        :ppass, :pdob, :ins, :tr, :meal, :sms,
        :fee, :total, :paym, :delm, :stat
    )");

    $fullName = trim(($data['surname'] ?? '') . ' ' . ($data['name'] ?? ''));
    if (empty(trim($fullName))) {
        $fullName = trim($data['passenger_name'] ?? 'Пассажир');
    }

    $stmt->bindValue(':num', $orderNumber, SQLITE3_TEXT);
    $stmt->bindValue(':uid', $userId, $userId ? SQLITE3_INTEGER : SQLITE3_NULL);
    $stmt->bindValue(':pname', $fullName, SQLITE3_TEXT);
    $stmt->bindValue(':pemail', trim($data['email'] ?? ''), SQLITE3_TEXT);
    $stmt->bindValue(':pphone', trim($data['phone'] ?? ''), SQLITE3_TEXT);
    $stmt->bindValue(':ppass', trim($data['passport'] ?? ''), SQLITE3_TEXT);
    $stmt->bindValue(':pdob', trim($data['dob'] ?? ''), SQLITE3_TEXT);
    $stmt->bindValue(':ins', $insurance, SQLITE3_INTEGER);
    $stmt->bindValue(':tr', $transfer, SQLITE3_INTEGER);
    $stmt->bindValue(':meal', $meal, SQLITE3_INTEGER);
    $stmt->bindValue(':sms', $sms, SQLITE3_INTEGER);
    $stmt->bindValue(':fee', $serviceFee, SQLITE3_INTEGER);
    $stmt->bindValue(':total', $totalAmount, SQLITE3_INTEGER);
    $stmt->bindValue(':paym', trim($data['payment_method'] ?? 'Банковская карта'), SQLITE3_TEXT);
    $stmt->bindValue(':delm', trim($data['delivery_method'] ?? 'Электронный билет'), SQLITE3_TEXT);
    $stmt->bindValue(':stat', 'Оплачен', SQLITE3_TEXT);

    if (!$stmt->execute()) {
        return ['success' => false, 'error' => 'Не удалось сохранить заказ в базе данных'];
    }

    $orderId = $db->lastInsertRowID();

    // Сохраняем элементы заказа
    $itemStmt = $db->prepare("INSERT INTO order_items (
        order_id, flight_id, seat_number, passenger_name, price
    ) VALUES (
        :oid, :fid, :seat, :pname, :price
    )");

    foreach ($items as $it) {
        $itemStmt->bindValue(':oid', $orderId, SQLITE3_INTEGER);
        $itemStmt->bindValue(':fid', (int)$it['id'], SQLITE3_INTEGER);
        $itemStmt->bindValue(':seat', $it['seat_number'], SQLITE3_TEXT);
        $itemStmt->bindValue(':pname', $fullName, SQLITE3_TEXT);
        $itemStmt->bindValue(':price', (int)$it['item_price'], SQLITE3_INTEGER);
        $itemStmt->execute();

        // Уменьшаем количество свободных мест на рейсе
        decrementFlightSeats((int)$it['id'], 1);
    }

    // Очищаем корзину после успешного оформления
    clearCart();

    return [
        'success' => true,
        'order_id' => $orderId,
        'order_number' => $orderNumber
    ];
}

/**
 * Получение заказа по ID с рейсами и пассажирами
 */
function getOrderById(int $id): ?array {
    $db = getDb();
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $res = $stmt->execute();
    $order = $res->fetchArray(SQLITE3_ASSOC);
    if (!$order) {
        return null;
    }

    // Получаем билеты
    $itemsStmt = $db->prepare("SELECT oi.*, f.flight_number, f.airline, f.airline_code,
                                      f.origin, f.origin_code, f.destination, f.destination_code,
                                      f.departure_date, f.departure_time, f.arrival_time, f.duration, f.cabin
                               FROM order_items oi
                               JOIN flights f ON oi.flight_id = f.id
                               WHERE oi.order_id = :oid");
    $itemsStmt->bindValue(':oid', $id, SQLITE3_INTEGER);
    $itemsRes = $itemsStmt->execute();
    
    $items = [];
    while ($row = $itemsRes->fetchArray(SQLITE3_ASSOC)) {
        $items[] = $row;
    }
    $order['items'] = $items;
    return $order;
}

/**
 * Получение заказа по номеру (#UF-...)
 */
function getOrderByNumber(string $number): ?array {
    $db = getDb();
    $stmt = $db->prepare("SELECT id FROM orders WHERE order_number = :num");
    $stmt->bindValue(':num', $number, SQLITE3_TEXT);
    $res = $stmt->execute();
    $row = $res->fetchArray(SQLITE3_ASSOC);
    if ($row) {
        return getOrderById((int)$row['id']);
    }
    return null;
}

/**
 * Получение заказов пользователя
 */
function getUserOrders(int $userId): array {
    $db = getDb();
    $stmt = $db->prepare("SELECT o.*, 
                                 f.origin, f.destination, f.departure_date, f.airline, f.flight_number
                          FROM orders o
                          LEFT JOIN order_items oi ON oi.order_id = o.id
                          LEFT JOIN flights f ON oi.flight_id = f.id
                          WHERE o.user_id = :uid
                          GROUP BY o.id
                          ORDER BY o.id DESC");
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $res = $stmt->execute();

    $orders = [];
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $orders[] = $row;
    }
    return $orders;
}

/**
 * Получение всех заказов (для администратора)
 */
function getAllOrders(): array {
    $db = getDb();
    $sql = "SELECT o.*, 
                   f.origin, f.destination, f.departure_date, f.airline, f.flight_number,
                   u.name as registered_user_name, u.email as registered_user_email
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN order_items oi ON oi.order_id = o.id
            LEFT JOIN flights f ON oi.flight_id = f.id
            GROUP BY o.id
            ORDER BY o.id DESC";
    $res = $db->query($sql);
    $orders = [];
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $orders[] = $row;
    }
    return $orders;
}

/**
 * Обновление статуса заказа (для администратора)
 */
function updateOrderStatus(int $orderId, string $status): bool {
    $db = getDb();
    $stmt = $db->prepare("UPDATE orders SET status = :stat WHERE id = :id");
    $stmt->bindValue(':stat', $status, SQLITE3_TEXT);
    $stmt->bindValue(':id', $orderId, SQLITE3_INTEGER);
    return (bool)$stmt->execute();
}
