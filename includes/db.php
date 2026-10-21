<?php
/**
 * UFAvia — Модуль работы с базой данных
 * Совместимость: PHP 7.2+ и MySQL 5.7 / MariaDB
 * Разработано для запуска в OpenServer Panel
 */

require_once __DIR__ . '/config.php';

/**
 * Получение активного подключения к базе данных (PDO)
 * @return PDO
 */
function getDb(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $driver = 'mysql';

    // 1. Попытка подключения к MySQL 5.7 в OpenServer
    try {
        $dsnWithoutDb = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $rootPdo = new PDO($dsnWithoutDb, DB_USER, DB_PASS, $options);
        // Создаем базу данных, если еще не создана
        $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $rootPdo->exec("USE `" . DB_NAME . "`;");
        $rootPdo->exec("SET NAMES " . DB_CHARSET . ";");
        // Отключаем строгий режим ONLY_FULL_GROUP_BY для полной совместимости с MySQL 5.7
        try {
            $rootPdo->exec("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
        } catch (Exception $e) {
            // Игнорируем, если версия не поддерживает
        }

        $pdo = $rootPdo;
        initDatabaseMysql($pdo);
        return $pdo;
    } catch (PDOException $e) {
        // 2. Резервное переключение на SQLite (если сервер MySQL еще не запущен)
        return getFallbackSqliteDb();
    }
}

/**
 * Резервное подключение через SQLite PDO
 */
function getFallbackSqliteDb(): PDO {
    static $sqlitePdo = null;
    if ($sqlitePdo !== null) {
        return $sqlitePdo;
    }

    $dbDir = dirname(SQLITE_FILE);
    if (!is_dir($dbDir)) {
        @mkdir($dbDir, 0777, true);
    }

    $sqlitePdo = new PDO('sqlite:' . SQLITE_FILE);
    $sqlitePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sqlitePdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $sqlitePdo->exec('PRAGMA foreign_keys = ON;');

    initDatabaseSqlite($sqlitePdo);
    return $sqlitePdo;
}

/**
 * Инициализация таблиц в MySQL 5.7
 */
function initDatabaseMysql(PDO $db) {
    // Проверяем существование таблицы flights
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :dbname AND table_name = 'flights'");
    $stmt->execute([':dbname' => DB_NAME]);
    $tableExists = (bool)$stmt->fetchColumn();

    if (!$tableExists) {
        // Создаем таблицы MySQL
        $db->exec("CREATE TABLE IF NOT EXISTS `users` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) NOT NULL UNIQUE,
            `phone` VARCHAR(50) NULL,
            `password` VARCHAR(255) NOT NULL,
            `role` VARCHAR(50) NOT NULL DEFAULT 'user',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $db->exec("CREATE TABLE IF NOT EXISTS `flights` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `flight_number` VARCHAR(50) NOT NULL,
            `airline` VARCHAR(100) NOT NULL,
            `airline_code` VARCHAR(10) NOT NULL,
            `origin` VARCHAR(100) NOT NULL,
            `origin_code` VARCHAR(10) NOT NULL,
            `destination` VARCHAR(100) NOT NULL,
            `destination_code` VARCHAR(10) NOT NULL,
            `departure_date` DATE NOT NULL,
            `departure_time` VARCHAR(20) NOT NULL,
            `arrival_time` VARCHAR(20) NOT NULL,
            `duration` INT NOT NULL,
            `price` INT NOT NULL,
            `seats_total` INT NOT NULL DEFAULT 180,
            `seats_available` INT NOT NULL DEFAULT 42,
            `cabin` VARCHAR(50) NOT NULL DEFAULT 'economy',
            `stops` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $db->exec("CREATE TABLE IF NOT EXISTS `orders` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `order_number` VARCHAR(50) NOT NULL UNIQUE,
            `user_id` INT NULL,
            `passenger_name` VARCHAR(255) NOT NULL,
            `passenger_email` VARCHAR(255) NOT NULL,
            `passenger_phone` VARCHAR(50) NOT NULL,
            `passenger_passport` VARCHAR(100) NULL,
            `passenger_dob` VARCHAR(50) NULL,
            `insurance` INT NOT NULL DEFAULT 0,
            `transfer` INT NOT NULL DEFAULT 0,
            `meal` INT NOT NULL DEFAULT 0,
            `sms` INT NOT NULL DEFAULT 0,
            `service_fee` INT NOT NULL DEFAULT 150,
            `total_amount` INT NOT NULL,
            `payment_method` VARCHAR(100) NOT NULL,
            `delivery_method` VARCHAR(100) NOT NULL,
            `status` VARCHAR(50) NOT NULL DEFAULT 'Оплачен',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $db->exec("CREATE TABLE IF NOT EXISTS `order_items` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `order_id` INT NOT NULL,
            `flight_id` INT NOT NULL,
            `seat_number` VARCHAR(20) NOT NULL,
            `passenger_name` VARCHAR(255) NULL,
            `price` INT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_items_flight` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $db->exec("CREATE TABLE IF NOT EXISTS `cart` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `session_id` VARCHAR(100) NOT NULL,
            `flight_id` INT NOT NULL,
            `seat_number` VARCHAR(20) NOT NULL,
            `price` INT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            CONSTRAINT `fk_cart_flight` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        seedUsersData($db);
        seedFlightsData($db);
        seedOrdersData($db);
    }
}

/**
 * Инициализация SQLite (для резервного режима)
 */
function initDatabaseSqlite(PDO $db) {
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        phone TEXT,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'user',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );");

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

    $db->exec("CREATE TABLE IF NOT EXISTS cart (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id TEXT NOT NULL,
        flight_id INTEGER NOT NULL,
        seat_number TEXT NOT NULL,
        price INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE
    );");

    $checkUser = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($checkUser == 0) {
        seedUsersData($db);
        seedFlightsData($db);
        seedOrdersData($db);
    }
}

/**
 * Наполнение начальными пользователями (Admin + Users)
 */
function seedUsersData(PDO $db) {
    $stmt = $db->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (:name, :email, :phone, :password, :role)");

    // Администратор: admin@ufavia.ru / admin123
    $stmt->execute([
        ':name' => 'Администратор UFAvia',
        ':email' => 'admin@ufavia.ru',
        ':phone' => '+7 (347) 000-00-00',
        ':password' => password_hash('admin123', PASSWORD_DEFAULT),
        ':role' => 'admin'
    ]);

    // Пользователь 1: ivan@example.com / user123
    $stmt->execute([
        ':name' => 'Иван Иванов',
        ':email' => 'ivan@example.com',
        ':phone' => '+7 (917) 000-00-00',
        ':password' => password_hash('user123', PASSWORD_DEFAULT),
        ':role' => 'user'
    ]);

    // Пользователь 2: anna@example.com / user123
    $stmt->execute([
        ':name' => 'Анна Смирнова',
        ':email' => 'anna@example.com',
        ':phone' => '+7 (927) 111-22-33',
        ':password' => password_hash('user123', PASSWORD_DEFAULT),
        ':role' => 'user'
    ]);
}

/**
 * Наполнение рейсами (товары)
 */
function seedFlightsData(PDO $db) {
    $today = date('Y-m-d');
    $flights = [
        ['SU-1234', 'Аэрофлот', 'SU', 'Уфа', 'UFA', 'Москва', 'SVO', $today, '08:20', '09:35', 135, 5400, 180, 38, 'economy', 0],
        ['S7-2041', 'S7 Airlines', 'S7', 'Уфа', 'UFA', 'Москва', 'DME', $today, '11:45', '13:00', 135, 4850, 168, 24, 'economy', 0],
        ['UT-360',  'Utair', 'UT', 'Уфа', 'UFA', 'Москва', 'VKO', $today, '15:10', '16:25', 135, 4200, 150, 19, 'economy', 0],
        ['DP-432',  'Победа', 'DP', 'Уфа', 'UFA', 'Москва', 'VKO', $today, '19:40', '21:00', 140, 3750, 189, 52, 'economy', 0],
        ['SU-1238', 'Аэрофлот', 'SU', 'Уфа', 'UFA', 'Москва', 'SVO', $today, '21:50', '23:05', 135, 14500, 24, 8, 'business', 0],
        ['FV-6412', 'Россия', 'FV', 'Уфа', 'UFA', 'Санкт-Петербург', 'LED', date('Y-m-d', strtotime('+1 day')), '06:30', '08:15', 165, 5200, 158, 41, 'economy', 0],
        ['DP-502',  'Победа', 'DP', 'Уфа', 'UFA', 'Санкт-Петербург', 'LED', date('Y-m-d', strtotime('+1 day')), '14:20', '16:05', 165, 4600, 189, 15, 'economy', 0],
        ['SU-6414', 'Аэрофлот', 'SU', 'Уфа', 'UFA', 'Санкт-Петербург', 'LED', date('Y-m-d', strtotime('+1 day')), '18:50', '20:35', 165, 16200, 20, 6, 'business', 0],
        ['N4-245',  'Nordwind', 'N4', 'Уфа', 'UFA', 'Сочи', 'AER', date('Y-m-d', strtotime('+2 days')), '09:10', '12:05', 235, 6100, 189, 28, 'economy', 0],
        ['UT-405',  'Utair', 'UT', 'Уфа', 'UFA', 'Сочи', 'AER', date('Y-m-d', strtotime('+2 days')), '16:40', '19:35', 235, 5800, 150, 12, 'economy', 0],
        ['RT-231',  'ЮВТ Аэро', 'RT', 'Уфа', 'UFA', 'Казань', 'KZN', date('Y-m-d', strtotime('+3 days')), '10:00', '10:15', 75, 3800, 50, 18, 'economy', 0],
        ['RT-233',  'ЮВТ Аэро', 'RT', 'Уфа', 'UFA', 'Казань', 'KZN', date('Y-m-d', strtotime('+3 days')), '17:30', '17:45', 75, 3950, 50, 11, 'economy', 0],
        ['WZ-1025', 'Red Wings', 'WZ', 'Уфа', 'UFA', 'Екатеринбург', 'SVX', date('Y-m-d', strtotime('+4 days')), '13:00', '14:10', 70, 3100, 100, 34, 'economy', 0],
        ['FZ-988',  'Flydubai', 'FZ', 'Уфа', 'UFA', 'Дубай', 'DXB', date('Y-m-d', strtotime('+5 days')), '04:15', '11:40', 445, 23500, 174, 9, 'economy', 1],
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
        $stmt->execute([
            ':fn' => $f[0],
            ':al' => $f[1],
            ':alc' => $f[2],
            ':orig' => $f[3],
            ':origc' => $f[4],
            ':dest' => $f[5],
            ':destc' => $f[6],
            ':ddate' => $f[7],
            ':dtime' => $f[8],
            ':atime' => $f[9],
            ':dur' => $f[10],
            ':price' => $f[11],
            ':stot' => $f[12],
            ':savail' => $f[13],
            ':cabin' => $f[14],
            ':stops' => $f[15]
        ]);
    }
}

/**
 * Наполнение тестовыми заказами
 */
function seedOrdersData(PDO $db) {
    $stmtUser = $db->query("SELECT id FROM users WHERE email = 'ivan@example.com'");
    $userId = (int)$stmtUser->fetchColumn() ?: 2;

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

    $orders = [
        [
            'UF-2026-00815', $userId, 'Иван Иванов', 'ivan@example.com', '+7 (917) 000-00-00',
            '8014 123456', '1995-04-12', 450, 0, 0, 0, 150, 6000, 'Банковская карта',
            'Электронный билет', 'Оплачен', '2026-06-15 08:30:00'
        ],
        [
            'UF-2026-00816', $userId, 'Иван Иванов', 'ivan@example.com', '+7 (917) 000-00-00',
            '8014 123456', '1995-04-12', 0, 0, 0, 99, 150, 5099, 'СБП',
            'Электронный билет', 'В обработке', '2026-06-22 10:15:00'
        ],
        [
            'UF-2025-00412', $userId, 'Иван Иванов', 'ivan@example.com', '+7 (917) 000-00-00',
            '8014 123456', '1995-04-12', 450, 1200, 350, 99, 150, 8349, 'Банковская карта',
            'Электронный билет', 'Отменён', '2025-08-10 14:00:00'
        ]
    ];

    foreach ($orders as $o) {
        $stmtOrder->execute([
            ':num' => $o[0],
            ':uid' => $o[1],
            ':pname' => $o[2],
            ':pemail' => $o[3],
            ':pphone' => $o[4],
            ':ppass' => $o[5],
            ':pdob' => $o[6],
            ':ins' => $o[7],
            ':tr' => $o[8],
            ':meal' => $o[9],
            ':sms' => $o[10],
            ':fee' => $o[11],
            ':total' => $o[12],
            ':paym' => $o[13],
            ':delm' => $o[14],
            ':stat' => $o[15],
            ':created' => $o[16]
        ]);

        $orderId = (int)$db->lastInsertId();

        $stmtItem->execute([
            ':oid' => $orderId,
            ':fid' => 1,
            ':seat' => '2B',
            ':pname' => $o[2],
            ':price' => 5400
        ]);
    }
}
