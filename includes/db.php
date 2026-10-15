<?php
/**
 * UFAvia — Модуль работы с базой данных
 * Совместимость: PHP 7.2+
 * СУБД: SQLite3 (с экспортом в MySQL)
 */

define('DB_FILE', __DIR__ . '/../database/ufavia.db');

function getDb(): SQLite3 {
    static $db = null;
    if ($db === null) {
        $dbDir = dirname(DB_FILE);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0777, true);
        }
        $db = new SQLite3(DB_FILE);
        $db->busyTimeout(5000);
        $db->exec('PRAGMA foreign_keys = ON;');
        initDatabase($db);
    }
    return $db;
}

/**
 * Инициализация таблиц и начальных данных
 */
function initDatabase(SQLite3 $db) {
    // Таблица пользователей
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        phone TEXT,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'user',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );");

    // Таблица рейсов (товары)
    $db->exec("CREATE TABLE IF NOT EXISTS flights (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        flight_number TEXT NOT NULL,
        airline TEXT NOT NULL,
        airline_code TEXT NOT NULL,
        origin TEXT NOT NULL,
        origin_code TEXT NOT NULL,
        destination TEXT NOT NULL,
        destination_code TEXT NOT NULL,
        departure_date TEXT NOT NULL,
        departure_time TEXT NOT NULL,
        arrival_time TEXT NOT NULL,
        duration INTEGER NOT NULL,
        price INTEGER NOT NULL,
        seats_total INTEGER NOT NULL DEFAULT 180,
        seats_available INTEGER NOT NULL DEFAULT 42,
        cabin TEXT NOT NULL DEFAULT 'economy',
        stops INTEGER NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );");

    // Таблица заказов
    $db->exec("CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_number TEXT UNIQUE NOT NULL,
        user_id INTEGER,
        passenger_name TEXT NOT NULL,
        passenger_email TEXT NOT NULL,
        passenger_phone TEXT NOT NULL,
        passenger_passport TEXT,
        passenger_dob TEXT,
        insurance INTEGER DEFAULT 0,
        transfer INTEGER DEFAULT 0,
        meal INTEGER DEFAULT 0,
        sms INTEGER DEFAULT 0,
        service_fee INTEGER DEFAULT 150,
        total_amount INTEGER NOT NULL,
        payment_method TEXT NOT NULL,
        delivery_method TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'Оплачен',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );");

    // Таблица элементов заказа (билеты с местами)
    $db->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        flight_id INTEGER NOT NULL,
        seat_number TEXT NOT NULL,
        passenger_name TEXT,
        price INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE
    );");

    // Таблица корзины (по сессии)
    $db->exec("CREATE TABLE IF NOT EXISTS cart (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id TEXT NOT NULL,
        flight_id INTEGER NOT NULL,
        seat_number TEXT NOT NULL,
        price INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE
    );");

    // Проверяем, есть ли администратор
    $checkUser = $db->querySingle("SELECT COUNT(*) FROM users");
    if ($checkUser == 0) {
        seedUsers($db);
    }

    // Проверяем, есть ли рейсы
    $checkFlights = $db->querySingle("SELECT COUNT(*) FROM flights");
    if ($checkFlights == 0) {
        seedFlights($db);
    }

    // Проверяем, есть ли начальные заказы
    $checkOrders = $db->querySingle("SELECT COUNT(*) FROM orders");
    if ($checkOrders == 0) {
        seedOrders($db);
    }
}

/**
 * Добавление пользователей по умолчанию (Администратор и пользователи)
 */
function seedUsers(SQLite3 $db) {
    $stmt = $db->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (:name, :email, :phone, :password, :role)");

    // Администратор: admin@ufavia.ru / admin123
    $stmt->bindValue(':name', 'Администратор UFAvia', SQLITE3_TEXT);
    $stmt->bindValue(':email', 'admin@ufavia.ru', SQLITE3_TEXT);
    $stmt->bindValue(':phone', '+7 (347) 000-00-00', SQLITE3_TEXT);
    $stmt->bindValue(':password', password_hash('admin123', PASSWORD_DEFAULT), SQLITE3_TEXT);
    $stmt->bindValue(':role', 'admin', SQLITE3_TEXT);
    $stmt->execute();

    // Пользователь 1: ivan@example.com / user123
    $stmt->bindValue(':name', 'Иван Иванов', SQLITE3_TEXT);
    $stmt->bindValue(':email', 'ivan@example.com', SQLITE3_TEXT);
    $stmt->bindValue(':phone', '+7 (917) 000-00-00', SQLITE3_TEXT);
    $stmt->bindValue(':password', password_hash('user123', PASSWORD_DEFAULT), SQLITE3_TEXT);
    $stmt->bindValue(':role', 'user', SQLITE3_TEXT);
    $stmt->execute();

    // Пользователь 2: anna@example.com / user123
    $stmt->bindValue(':name', 'Анна Смирнова', SQLITE3_TEXT);
    $stmt->bindValue(':email', 'anna@example.com', SQLITE3_TEXT);
    $stmt->bindValue(':phone', '+7 (927) 111-22-33', SQLITE3_TEXT);
    $stmt->bindValue(':password', password_hash('user123', PASSWORD_DEFAULT), SQLITE3_TEXT);
    $stmt->bindValue(':role', 'user', SQLITE3_TEXT);
    $stmt->execute();
}

/**
 * Заполнение рейсов начальными данными
 */
function seedFlights(SQLite3 $db) {
    $flights = [
        // Уфа -> Москва
        ['SU-1234', 'Аэрофлот', 'SU', 'Уфа', 'UFA', 'Москва', 'SVO', '2026-10-20', '08:20', '09:35', 135, 5400, 180, 38, 'economy', 0],
        ['S7-2041', 'S7 Airlines', 'S7', 'Уфа', 'UFA', 'Москва', 'DME', '2026-10-20', '11:45', '13:00', 135, 4850, 168, 24, 'economy', 0],
        ['UT-360',  'Utair', 'UT', 'Уфа', 'UFA', 'Москва', 'VKO', '2026-10-20', '15:10', '16:25', 135, 4200, 150, 19, 'economy', 0],
        ['DP-432',  'Победа', 'DP', 'Уфа', 'UFA', 'Москва', 'VKO', '2026-10-20', '19:40', '21:00', 140, 3750, 189, 52, 'economy', 0],
        ['SU-1238', 'Аэрофлот', 'SU', 'Уфа', 'UFA', 'Москва', 'SVO', '2026-10-20', '21:50', '23:05', 135, 14500, 24, 8, 'business', 0],
        
        // Уфа -> Санкт-Петербург
        ['FV-6412', 'Россия', 'FV', 'Уфа', 'UFA', 'Санкт-Петербург', 'LED', '2026-10-21', '06:30', '08:15', 165, 5200, 158, 41, 'economy', 0],
        ['DP-502',  'Победа', 'DP', 'Уфа', 'UFA', 'Санкт-Петербург', 'LED', '2026-10-21', '14:20', '16:05', 165, 4600, 189, 15, 'economy', 0],
        ['SU-6414', 'Аэрофлот', 'SU', 'Уфа', 'UFA', 'Санкт-Петербург', 'LED', '2026-10-21', '18:50', '20:35', 165, 16200, 20, 6, 'business', 0],

        // Уфа -> Сочи
        ['N4-245',  'Nordwind', 'N4', 'Уфа', 'UFA', 'Сочи', 'AER', '2026-10-22', '09:10', '12:05', 235, 6100, 189, 28, 'economy', 0],
        ['UT-405',  'Utair', 'UT', 'Уфа', 'UFA', 'Сочи', 'AER', '2026-10-22', '16:40', '19:35', 235, 5800, 150, 12, 'economy', 0],

        // Уфа -> Казань
        ['RT-231',  'ЮВТ Аэро', 'RT', 'Уфа', 'UFA', 'Казань', 'KZN', '2026-10-23', '10:00', '10:15', 75, 3800, 50, 18, 'economy', 0],
        ['RT-233',  'ЮВТ Аэро', 'RT', 'Уфа', 'UFA', 'Казань', 'KZN', '2026-10-23', '17:30', '17:45', 75, 3950, 50, 11, 'economy', 0],

        // Уфа -> Екатеринбург
        ['WZ-1025', 'Red Wings', 'WZ', 'Уфа', 'UFA', 'Екатеринбург', 'SVX', '2026-10-24', '13:00', '14:10', 70, 3100, 100, 34, 'economy', 0],

        // Уфа -> Дубай (международный с пересадкой)
        ['FZ-988',  'Flydubai', 'FZ', 'Уфа', 'UFA', 'Дубай', 'DXB', '2026-10-25', '04:15', '11:40', 445, 23500, 174, 9, 'economy', 1],
    ];

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

    foreach ($flights as $f) {
        $stmt->bindValue(':fn', $f[0], SQLITE3_TEXT);
        $stmt->bindValue(':al', $f[1], SQLITE3_TEXT);
        $stmt->bindValue(':alc', $f[2], SQLITE3_TEXT);
        $stmt->bindValue(':orig', $f[3], SQLITE3_TEXT);
        $stmt->bindValue(':origc', $f[4], SQLITE3_TEXT);
        $stmt->bindValue(':dest', $f[5], SQLITE3_TEXT);
        $stmt->bindValue(':destc', $f[6], SQLITE3_TEXT);
        $stmt->bindValue(':ddate', $f[7], SQLITE3_TEXT);
        $stmt->bindValue(':dtime', $f[8], SQLITE3_TEXT);
        $stmt->bindValue(':atime', $f[9], SQLITE3_TEXT);
        $stmt->bindValue(':dur', $f[10], SQLITE3_INTEGER);
        $stmt->bindValue(':price', $f[11], SQLITE3_INTEGER);
        $stmt->bindValue(':stot', $f[12], SQLITE3_INTEGER);
        $stmt->bindValue(':savail', $f[13], SQLITE3_INTEGER);
        $stmt->bindValue(':cabin', $f[14], SQLITE3_TEXT);
        $stmt->bindValue(':stops', $f[15], SQLITE3_INTEGER);
        $stmt->execute();
    }
}

/**
 * Заполнение начальных заказов для демонстрации
 */
function seedOrders(SQLite3 $db) {
    // Получим ID пользователя Ivan
    $user = $db->querySingle("SELECT id FROM users WHERE email = 'ivan@example.com'", true);
    $userId = $user ? $user['id'] : 2;

    $orders = [
        [
            'UF-2024-00815', $userId, 'Иван Иванов', 'ivan@example.com', '+7 (917) 000-00-00',
            '8014 123456', '1995-04-12', 450, 0, 0, 0, 150, 6000, 'Банковская карта',
            'Электронный билет', 'Оплачен', '2026-06-15 08:30:00'
        ],
        [
            'UF-2024-00816', $userId, 'Иван Иванов', 'ivan@example.com', '+7 (917) 000-00-00',
            '8014 123456', '1995-04-12', 0, 0, 0, 99, 150, 6100, 'СБП',
            'Электронный билет', 'В обработке', '2026-06-22 10:15:00'
        ],
        [
            'UF-2023-00412', $userId, 'Иван Иванов', 'ivan@example.com', '+7 (917) 000-00-00',
            '8014 123456', '1995-04-12', 450, 1200, 350, 99, 150, 8299, 'Банковская карта',
            'Электронный билет', 'Отменён', '2025-08-10 14:00:00'
        ]
    ];

    $stmtOrder = $db->prepare("INSERT INTO orders (
        order_number, user_id, passenger_name, passenger_email, passenger_phone,
        passenger_passport, passenger_dob, insurance, transfer, meal, sms,
        service_fee, total_amount, payment_method, delivery_method, status, created_at
    ) VALUES (
        :num, :uid, :pname, :pemail, :pphone,
        :ppass, :pdob, :ins, :tr, :meal, :sms,
        :fee, :total, :paym, :delm, :stat, :created
    )");

    $stmtItem = $db->prepare("INSERT INTO order_items (
        order_id, flight_id, seat_number, passenger_name, price
    ) VALUES (
        :oid, :fid, :seat, :pname, :price
    )");

    foreach ($orders as $o) {
        $stmtOrder->bindValue(':num', $o[0], SQLITE3_TEXT);
        $stmtOrder->bindValue(':uid', $o[1], SQLITE3_INTEGER);
        $stmtOrder->bindValue(':pname', $o[2], SQLITE3_TEXT);
        $stmtOrder->bindValue(':pemail', $o[3], SQLITE3_TEXT);
        $stmtOrder->bindValue(':pphone', $o[4], SQLITE3_TEXT);
        $stmtOrder->bindValue(':ppass', $o[5], SQLITE3_TEXT);
        $stmtOrder->bindValue(':pdob', $o[6], SQLITE3_TEXT);
        $stmtOrder->bindValue(':ins', $o[7], SQLITE3_INTEGER);
        $stmtOrder->bindValue(':tr', $o[8], SQLITE3_INTEGER);
        $stmtOrder->bindValue(':meal', $o[9], SQLITE3_INTEGER);
        $stmtOrder->bindValue(':sms', $o[10], SQLITE3_INTEGER);
        $stmtOrder->bindValue(':fee', $o[11], SQLITE3_INTEGER);
        $stmtOrder->bindValue(':total', $o[12], SQLITE3_INTEGER);
        $stmtOrder->bindValue(':paym', $o[13], SQLITE3_TEXT);
        $stmtOrder->bindValue(':delm', $o[14], SQLITE3_TEXT);
        $stmtOrder->bindValue(':stat', $o[15], SQLITE3_TEXT);
        $stmtOrder->bindValue(':created', $o[16], SQLITE3_TEXT);
        $stmtOrder->execute();

        $orderId = $db->lastInsertRowID();

        // Добавим билет
        $stmtItem->bindValue(':oid', $orderId, SQLITE3_INTEGER);
        $stmtItem->bindValue(':fid', 1, SQLITE3_INTEGER);
        $stmtItem->bindValue(':seat', '2B', SQLITE3_TEXT);
        $stmtItem->bindValue(':pname', $o[2], SQLITE3_TEXT);
        $stmtItem->bindValue(':price', 5400, SQLITE3_INTEGER);
        $stmtItem->execute();
    }
}
