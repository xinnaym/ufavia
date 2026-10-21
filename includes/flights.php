<?php
/**
 * UFAvia — Модуль работы с рейсами (товары)
 * Реализация CRUD для администратора и поиск/фильтрация для пассажиров
 * Совместимость: PHP 7.2+, MySQL 5.7+ (PDO)
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

    if (isset($filters['stops']) && $filters['stops'] !== 'any' && $filters['stops'] !== '') {
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
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Получение одного рейса по ID
 */
function getFlightById(int $id): ?array {
    $db = getDb();
    $stmt = $db->prepare("SELECT * FROM flights WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $flight = $stmt->fetch(PDO::FETCH_ASSOC);
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
            GROUP BY destination, destination_code 
            ORDER BY min_price ASC 
            LIMIT 4";
    $stmt = $db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    return $stmt->execute([
        ':fn' => trim($data['flight_number']),
        ':al' => trim($data['airline']),
        ':alc' => strtoupper(trim($data['airline_code'] ?? 'UF')),
        ':orig' => trim($data['origin']),
        ':origc' => strtoupper(trim($data['origin_code'] ?? 'UFA')),
        ':dest' => trim($data['destination']),
        ':destc' => strtoupper(trim($data['destination_code'] ?? 'XXX')),
        ':ddate' => trim($data['departure_date']),
        ':dtime' => trim($data['departure_time']),
        ':atime' => trim($data['arrival_time']),
        ':dur' => (int)$data['duration'],
        ':price' => (int)$data['price'],
        ':stot' => (int)($data['seats_total'] ?? 180),
        ':savail' => (int)($data['seats_available'] ?? 42),
        ':cabin' => trim($data['cabin'] ?? 'economy'),
        ':stops' => (int)($data['stops'] ?? 0)
    ]);
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

    return $stmt->execute([
        ':fn' => trim($data['flight_number']),
        ':al' => trim($data['airline']),
        ':alc' => strtoupper(trim($data['airline_code'])),
        ':orig' => trim($data['origin']),
        ':origc' => strtoupper(trim($data['origin_code'])),
        ':dest' => trim($data['destination']),
        ':destc' => strtoupper(trim($data['destination_code'])),
        ':ddate' => trim($data['departure_date']),
        ':dtime' => trim($data['departure_time']),
        ':atime' => trim($data['arrival_time']),
        ':dur' => (int)$data['duration'],
        ':price' => (int)$data['price'],
        ':stot' => (int)$data['seats_total'],
        ':savail' => (int)$data['seats_available'],
        ':cabin' => trim($data['cabin']),
        ':stops' => (int)$data['stops'],
        ':id' => $id
    ]);
}

/**
 * Удаление рейса (для администратора)
 */
function deleteFlight(int $id): bool {
    $db = getDb();
    $stmt = $db->prepare("DELETE FROM flights WHERE id = :id");
    return $stmt->execute([':id' => $id]);
}

/**
 * Уменьшение свободных мест при заказе
 */
function decrementFlightSeats(int $flightId, int $count = 1) {
    $db = getDb();
    $stmt = $db->prepare("UPDATE flights SET seats_available = CASE WHEN seats_available >= :cnt THEN seats_available - :cnt ELSE 0 END WHERE id = :id");
    $stmt->execute([
        ':cnt' => $count,
        ':id' => $flightId
    ]);
}
