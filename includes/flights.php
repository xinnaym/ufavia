<?php
/**
 * UFAvia — Модуль работы с рейсами (товары)
 * Реализация CRUD для администратора и поиск/фильтрация для пассажиров
 */

require_once __DIR__ . '/db.php';

/**
 * Получение списка рейсов с возможностью фильтрации и сортировки
 */
function getFlights(array $filters = []): array {
    $db = getDb();
    
    $where = [];
    $params = [];

    if (!empty($filters['origin'])) {
        $where[] = "LOWER(origin) LIKE :origin";
        $params[':origin'] = '%' . mb_strtolower(trim($filters['origin'])) . '%';
    }

    if (!empty($filters['destination'])) {
        $where[] = "LOWER(destination) LIKE :destination";
        $params[':destination'] = '%' . mb_strtolower(trim($filters['destination'])) . '%';
    }

    if (!empty($filters['departure_date'])) {
        $where[] = "departure_date = :departure_date";
        $params[':departure_date'] = trim($filters['departure_date']);
    }

    if (!empty($filters['cabin']) && $filters['cabin'] !== 'any') {
        $where[] = "cabin = :cabin";
        $params[':cabin'] = $filters['cabin'];
    }

    if (isset($filters['stops']) && $filters['stops'] !== 'any') {
        $where[] = "stops = :stops";
        $params[':stops'] = (int)$filters['stops'];
    }

    if (!empty($filters['price_range'])) {
        if ($filters['price_range'] === 'low') {
            $where[] = "price < 5000";
        } elseif ($filters['price_range'] === 'medium') {
            $where[] = "price >= 5000 AND price <= 10000";
        } elseif ($filters['price_range'] === 'high') {
            $where[] = "price > 10000";
        }
    }

    $sql = "SELECT * FROM flights";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    // Сортировка
    $sort = $filters['sort'] ?? 'price';
    if ($sort === 'departure') {
        $sql .= " ORDER BY departure_time ASC";
    } elseif ($sort === 'duration') {
        $sql .= " ORDER BY duration ASC";
    } else {
        $sql .= " ORDER BY price ASC";
    }

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }

    $res = $stmt->execute();
    $flights = [];
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $flights[] = $row;
    }
    return $flights;
}

/**
 * Получение одного рейса по ID
 */
function getFlightById(int $id): ?array {
    $db = getDb();
    $stmt = $db->prepare("SELECT * FROM flights WHERE id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $res = $stmt->execute();
    $flight = $res->fetchArray(SQLITE3_ASSOC);
    return $flight ?: null;
}

/**
 * Получение популярных направлений с минимальными ценами
 */
function getPopularDestinations(): array {
    $db = getDb();
    $sql = "SELECT destination, destination_code, MIN(price) as min_price 
            FROM flights 
            WHERE origin = 'Уфа' 
            GROUP BY destination 
            ORDER BY min_price ASC 
            LIMIT 4";
    $res = $db->query($sql);
    $destinations = [];
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $destinations[] = $row;
    }
    return $destinations;
}

/**
 * Добавление рейса (для администратора)
 */
function addFlight(array $data): bool {
    $db = getDb();
    $stmt = $db->prepare("INSERT INTO flights (
        flight_number, airline, airline_code, origin, origin_code,
        destination, destination_code, departure_date, departure_time,
        arrival_time, duration, price, seats_total, seats_available,
        cabin, stops
    ) VALUES (
        :fn, :al, :alc, :orig, :origc,
        :dest, :destc, :ddate, :dtime,
        :atime, :dur, :price, :stot, :savail,
        :cabin, :stops
    )");

    $stmt->bindValue(':fn', trim($data['flight_number']), SQLITE3_TEXT);
    $stmt->bindValue(':al', trim($data['airline']), SQLITE3_TEXT);
    $stmt->bindValue(':alc', strtoupper(trim($data['airline_code'] ?? 'UF')), SQLITE3_TEXT);
    $stmt->bindValue(':orig', trim($data['origin']), SQLITE3_TEXT);
    $stmt->bindValue(':origc', strtoupper(trim($data['origin_code'] ?? 'UFA')), SQLITE3_TEXT);
    $stmt->bindValue(':dest', trim($data['destination']), SQLITE3_TEXT);
    $stmt->bindValue(':destc', strtoupper(trim($data['destination_code'] ?? 'XXX')), SQLITE3_TEXT);
    $stmt->bindValue(':ddate', trim($data['departure_date']), SQLITE3_TEXT);
    $stmt->bindValue(':dtime', trim($data['departure_time']), SQLITE3_TEXT);
    $stmt->bindValue(':atime', trim($data['arrival_time']), SQLITE3_TEXT);
    $stmt->bindValue(':dur', (int)$data['duration'], SQLITE3_INTEGER);
    $stmt->bindValue(':price', (int)$data['price'], SQLITE3_INTEGER);
    $stmt->bindValue(':stot', (int)($data['seats_total'] ?? 180), SQLITE3_INTEGER);
    $stmt->bindValue(':savail', (int)($data['seats_available'] ?? 42), SQLITE3_INTEGER);
    $stmt->bindValue(':cabin', trim($data['cabin'] ?? 'economy'), SQLITE3_TEXT);
    $stmt->bindValue(':stops', (int)($data['stops'] ?? 0), SQLITE3_INTEGER);

    return (bool)$stmt->execute();
}

/**
 * Редактирование рейса (для администратора)
 */
function updateFlight(int $id, array $data): bool {
    $db = getDb();
    $stmt = $db->prepare("UPDATE flights SET 
        flight_number = :fn,
        airline = :al,
        airline_code = :alc,
        origin = :orig,
        origin_code = :origc,
        destination = :dest,
        destination_code = :destc,
        departure_date = :ddate,
        departure_time = :dtime,
        arrival_time = :atime,
        duration = :dur,
        price = :price,
        seats_total = :stot,
        seats_available = :savail,
        cabin = :cabin,
        stops = :stops
        WHERE id = :id");

    $stmt->bindValue(':fn', trim($data['flight_number']), SQLITE3_TEXT);
    $stmt->bindValue(':al', trim($data['airline']), SQLITE3_TEXT);
    $stmt->bindValue(':alc', strtoupper(trim($data['airline_code'])), SQLITE3_TEXT);
    $stmt->bindValue(':orig', trim($data['origin']), SQLITE3_TEXT);
    $stmt->bindValue(':origc', strtoupper(trim($data['origin_code'])), SQLITE3_TEXT);
    $stmt->bindValue(':dest', trim($data['destination']), SQLITE3_TEXT);
    $stmt->bindValue(':destc', strtoupper(trim($data['destination_code'])), SQLITE3_TEXT);
    $stmt->bindValue(':ddate', trim($data['departure_date']), SQLITE3_TEXT);
    $stmt->bindValue(':dtime', trim($data['departure_time']), SQLITE3_TEXT);
    $stmt->bindValue(':atime', trim($data['arrival_time']), SQLITE3_TEXT);
    $stmt->bindValue(':dur', (int)$data['duration'], SQLITE3_INTEGER);
    $stmt->bindValue(':price', (int)$data['price'], SQLITE3_INTEGER);
    $stmt->bindValue(':stot', (int)$data['seats_total'], SQLITE3_INTEGER);
    $stmt->bindValue(':savail', (int)$data['seats_available'], SQLITE3_INTEGER);
    $stmt->bindValue(':cabin', trim($data['cabin']), SQLITE3_TEXT);
    $stmt->bindValue(':stops', (int)$data['stops'], SQLITE3_INTEGER);
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);

    return (bool)$stmt->execute();
}

/**
 * Удаление рейса (для администратора)
 */
function deleteFlight(int $id): bool {
    $db = getDb();
    $stmt = $db->prepare("DELETE FROM flights WHERE id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    return (bool)$stmt->execute();
}

/**
 * Уменьшение свободных мест при заказе
 */
function decrementFlightSeats(int $flightId, int $count = 1) {
    $db = getDb();
    $stmt = $db->prepare("UPDATE flights SET seats_available = MAX(0, seats_available - :cnt) WHERE id = :id");
    $stmt->bindValue(':cnt', $count, SQLITE3_INTEGER);
    $stmt->bindValue(':id', $flightId, SQLITE3_INTEGER);
    $stmt->execute();
}
