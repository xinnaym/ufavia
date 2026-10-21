<?php
/**
 * UFAvia — Модуль работы с заказами
 * Совместимость: PHP 7.2+, MySQL 5.7+ (PDO)
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

    // Генерация уникального номера заказа
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

    $fullName = trim(($data['surname'] ?? '') . ' ' . ($data['name'] ?? ''));
    if (empty(trim($fullName))) {
        $fullName = trim($data['passenger_name'] ?? 'Пассажир');
    }

    $stmt = $db->prepare("INSERT INTO orders (
        order_number, user_id, passenger_name, passenger_email, passenger_phone,
        passenger_passport, passenger_dob, insurance, transfer, meal, sms,
        service_fee, total_amount, payment_method, delivery_method, status
    ) VALUES (
        :num, :uid, :pname, :pemail, :pphone,
        :ppass, :pdob, :ins, :tr, :meal, :sms,
        :fee, :total, :paym, :delm, :stat
    )");

    $ok = $stmt->execute([
        ':num' => $orderNumber,
        ':uid' => $userId,
        ':pname' => $fullName,
        ':pemail' => trim($data['email'] ?? ''),
        ':pphone' => trim($data['phone'] ?? ''),
        ':ppass' => trim($data['passport'] ?? ''),
        ':pdob' => trim($data['dob'] ?? ''),
        ':ins' => $insurance,
        ':tr' => $transfer,
        ':meal' => $meal,
        ':sms' => $sms,
        ':fee' => $serviceFee,
        ':total' => $totalAmount,
        ':paym' => trim($data['payment_method'] ?? 'Банковская карта'),
        ':delm' => trim($data['delivery_method'] ?? 'Электронный билет'),
        ':stat' => 'Оплачен'
    ]);

    if (!$ok) {
        return ['success' => false, 'error' => 'Не удалось сохранить заказ в базе данных'];
    }

    $orderId = (int)$db->lastInsertId();

    // Сохраняем элементы заказа
    $itemStmt = $db->prepare("INSERT INTO order_items (
        order_id, flight_id, seat_number, passenger_name, price
    ) VALUES (
        :oid, :fid, :seat, :pname, :price
    )");

    foreach ($items as $it) {
        $itemStmt->execute([
            ':oid' => $orderId,
            ':fid' => (int)$it['id'],
            ':seat' => $it['seat_number'],
            ':pname' => $fullName,
            ':price' => (int)$it['item_price']
        ]);

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
    $stmt->execute([':id' => $id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
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
    $itemsStmt->execute([':oid' => $id]);
    $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    return $order;
}

/**
 * Получение заказа по номеру (#UF-...)
 */
function getOrderByNumber(string $number): ?array {
    $db = getDb();
    $stmt = $db->prepare("SELECT id FROM orders WHERE order_number = :num");
    $stmt->execute([':num' => $number]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        return getOrderById((int)$row['id']);
    }
    return null;
}

/**
 * Получение заказов пользователя (совместимо с ONLY_FULL_GROUP_BY в MySQL 5.7)
 */
function getUserOrders(int $userId): array {
    $db = getDb();
    $sql = "SELECT o.*, 
                   (SELECT f.origin FROM order_items oi JOIN flights f ON oi.flight_id = f.id WHERE oi.order_id = o.id LIMIT 1) AS origin,
                   (SELECT f.destination FROM order_items oi JOIN flights f ON oi.flight_id = f.id WHERE oi.order_id = o.id LIMIT 1) AS destination,
                   (SELECT f.airline FROM order_items oi JOIN flights f ON oi.flight_id = f.id WHERE oi.order_id = o.id LIMIT 1) AS airline,
                   (SELECT f.flight_number FROM order_items oi JOIN flights f ON oi.flight_id = f.id WHERE oi.order_id = o.id LIMIT 1) AS flight_number,
                   (SELECT f.departure_date FROM order_items oi JOIN flights f ON oi.flight_id = f.id WHERE oi.order_id = o.id LIMIT 1) AS departure_date
            FROM orders o
            WHERE o.user_id = :uid
            ORDER BY o.id DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([':uid' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Получение всех заказов (для администратора)
 */
function getAllOrders(): array {
    $db = getDb();
    $sql = "SELECT o.*, 
                   u.name as registered_user_name, u.email as registered_user_email,
                   (SELECT f.origin FROM order_items oi JOIN flights f ON oi.flight_id = f.id WHERE oi.order_id = o.id LIMIT 1) AS origin,
                   (SELECT f.destination FROM order_items oi JOIN flights f ON oi.flight_id = f.id WHERE oi.order_id = o.id LIMIT 1) AS destination,
                   (SELECT f.airline FROM order_items oi JOIN flights f ON oi.flight_id = f.id WHERE oi.order_id = o.id LIMIT 1) AS airline,
                   (SELECT f.flight_number FROM order_items oi JOIN flights f ON oi.flight_id = f.id WHERE oi.order_id = o.id LIMIT 1) AS flight_number,
                   (SELECT f.departure_date FROM order_items oi JOIN flights f ON oi.flight_id = f.id WHERE oi.order_id = o.id LIMIT 1) AS departure_date
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            ORDER BY o.id DESC";
    $stmt = $db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Обновление статуса заказа (для администратора)
 */
function updateOrderStatus(int $orderId, string $status): bool {
    $db = getDb();
    $stmt = $db->prepare("UPDATE orders SET status = :stat WHERE id = :id");
    return $stmt->execute([
        ':stat' => $status,
        ':id' => $orderId
    ]);
}
