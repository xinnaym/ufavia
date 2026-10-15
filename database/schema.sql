-- ===================================================
-- База данных для сайта продажи авиабилетов UFAvia
-- Разработано для Практической работы № 1 (г. Стерлитамак)
-- Поддерживаемые СУБД: SQLite / MySQL (MariaDB)
-- ===================================================

-- 1. Таблица пользователей (users)
-- Роли: 'admin' (администратор), 'user' (зарегистрированный пользователь)
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(50),
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Таблица товаров / рейсов (flights)
CREATE TABLE IF NOT EXISTS flights (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    flight_number VARCHAR(50) NOT NULL,
    airline VARCHAR(100) NOT NULL,
    airline_code VARCHAR(10) NOT NULL,
    origin VARCHAR(100) NOT NULL,
    origin_code VARCHAR(10) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    destination_code VARCHAR(10) NOT NULL,
    departure_date DATE NOT NULL,
    departure_time VARCHAR(20) NOT NULL,
    arrival_time VARCHAR(20) NOT NULL,
    duration INT NOT NULL, -- в минутах
    price INT NOT NULL,
    seats_total INT NOT NULL DEFAULT 180,
    seats_available INT NOT NULL DEFAULT 42,
    cabin VARCHAR(50) NOT NULL DEFAULT 'economy',
    stops INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Таблица заказов (orders)
CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    user_id INT NULL,
    passenger_name VARCHAR(255) NOT NULL,
    passenger_email VARCHAR(255) NOT NULL,
    passenger_phone VARCHAR(50) NOT NULL,
    passenger_passport VARCHAR(100),
    passenger_dob VARCHAR(50),
    insurance INT DEFAULT 0,
    transfer INT DEFAULT 0,
    meal INT DEFAULT 0,
    sms INT DEFAULT 0,
    service_fee INT DEFAULT 150,
    total_amount INT NOT NULL,
    payment_method VARCHAR(100) NOT NULL,
    delivery_method VARCHAR(100) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Оплачен',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 4. Таблица состава заказа (order_items)
CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INT NOT NULL,
    flight_id INT NOT NULL,
    seat_number VARCHAR(20) NOT NULL,
    passenger_name VARCHAR(255),
    price INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE
);

-- 5. Таблица корзины (cart)
CREATE TABLE IF NOT EXISTS cart (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id VARCHAR(100) NOT NULL,
    flight_id INT NOT NULL,
    seat_number VARCHAR(20) NOT NULL,
    price INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE
);
