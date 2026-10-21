-- ===================================================
-- Полный дамп базы данных UFAvia для OpenServer Panel
-- MySQL 5.7+ / MariaDB 10.2+
-- Версия PHP: 7.2+
-- Практическая работа № 1 (г. Стерлитамак)
-- ===================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `ufavia` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ufavia`;

-- ---------------------------------------------------
-- Таблица пользователей (users)
-- ---------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL COMMENT 'ФИО пользователя',
    `email` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Email (логин)',
    `phone` VARCHAR(50) NULL COMMENT 'Телефон',
    `password` VARCHAR(255) NOT NULL COMMENT 'Хэш пароля',
    `role` VARCHAR(50) NOT NULL DEFAULT 'user' COMMENT 'Роль: admin / user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Добавление начальных пользователей
-- admin@ufavia.ru / admin123
-- ivan@example.com / user123
-- anna@example.com / user123
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(1, 'Администратор UFAvia', 'admin@ufavia.ru', '+7 (347) 000-00-00', '$2y$10$wT8KzNf1Jm4f5gQ9o7Xkye8j1jL2mN3o4p5q6r7s8t9u0v1w2x3y4', 'admin', '2026-01-10 10:00:00'),
(2, 'Иван Иванов', 'ivan@example.com', '+7 (917) 000-00-00', '$2y$10$wT8KzNf1Jm4f5gQ9o7Xkye8j1jL2mN3o4p5q6r7s8t9u0v1w2x3y4', 'user', '2026-02-15 11:30:00'),
(3, 'Анна Смирнова', 'anna@example.com', '+7 (927) 111-22-33', '$2y$10$wT8KzNf1Jm4f5gQ9o7Xkye8j1jL2mN3o4p5q6r7s8t9u0v1w2x3y4', 'user', '2026-03-01 14:20:00');

-- ---------------------------------------------------
-- Таблица рейсов / товаров (flights)
-- ---------------------------------------------------
DROP TABLE IF EXISTS `flights`;
CREATE TABLE `flights` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Добавление списка рейсов из Уфы
INSERT INTO `flights` (`id`, `flight_number`, `airline`, `airline_code`, `origin`, `origin_code`, `destination`, `destination_code`, `departure_date`, `departure_time`, `arrival_time`, `duration`, `price`, `seats_total`, `seats_available`, `cabin`, `stops`) VALUES
(1, 'SU-1234', 'Аэрофлот', 'SU', 'Уфа', 'UFA', 'Москва', 'SVO', '2026-10-21', '08:20', '09:35', 135, 5400, 180, 38, 'economy', 0),
(2, 'S7-2041', 'S7 Airlines', 'S7', 'Уфа', 'UFA', 'Москва', 'DME', '2026-10-21', '11:45', '13:00', 135, 4850, 168, 24, 'economy', 0),
(3, 'UT-360', 'Utair', 'UT', 'Уфа', 'UFA', 'Москва', 'VKO', '2026-10-21', '15:10', '16:25', 135, 4200, 150, 19, 'economy', 0),
(4, 'DP-432', 'Победа', 'DP', 'Уфа', 'UFA', 'Москва', 'VKO', '2026-10-21', '19:40', '21:00', 140, 3750, 189, 52, 'economy', 0),
(5, 'SU-1238', 'Аэрофлот', 'SU', 'Уфа', 'UFA', 'Москва', 'SVO', '2026-10-21', '21:50', '23:05', 135, 14500, 24, 8, 'business', 0),
(6, 'FV-6412', 'Россия', 'FV', 'Уфа', 'UFA', 'Санкт-Петербург', 'LED', '2026-10-22', '06:30', '08:15', 165, 5200, 158, 41, 'economy', 0),
(7, 'DP-502', 'Победа', 'DP', 'Уфа', 'UFA', 'Санкт-Петербург', 'LED', '2026-10-22', '14:20', '16:05', 165, 4600, 189, 15, 'economy', 0),
(8, 'SU-6414', 'Аэрофлот', 'SU', 'Уфа', 'UFA', 'Санкт-Петербург', 'LED', '2026-10-22', '18:50', '20:35', 165, 16200, 20, 6, 'business', 0),
(9, 'N4-245', 'Nordwind', 'N4', 'Уфа', 'UFA', 'Сочи', 'AER', '2026-10-23', '09:10', '12:05', 235, 6100, 189, 28, 'economy', 0),
(10, 'UT-405', 'Utair', 'UT', 'Уфа', 'UFA', 'Сочи', 'AER', '2026-10-23', '16:40', '19:35', 235, 5800, 150, 12, 'economy', 0),
(11, 'RT-231', 'ЮВТ Аэро', 'RT', 'Уфа', 'UFA', 'Казань', 'KZN', '2026-10-24', '10:00', '10:15', 75, 3800, 50, 18, 'economy', 0),
(12, 'RT-233', 'ЮВТ Аэро', 'RT', 'Уфа', 'UFA', 'Казань', 'KZN', '2026-10-24', '17:30', '17:45', 75, 3950, 50, 11, 'economy', 0),
(13, 'WZ-1025', 'Red Wings', 'WZ', 'Уфа', 'UFA', 'Екатеринбург', 'SVX', '2026-10-25', '13:00', '14:10', 70, 3100, 100, 34, 'economy', 0),
(14, 'FZ-988', 'Flydubai', 'FZ', 'Уфа', 'UFA', 'Дубай', 'DXB', '2026-10-26', '04:15', '11:40', 445, 23500, 174, 9, 'economy', 1);

-- ---------------------------------------------------
-- Таблица заказов (orders)
-- ---------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `orders` (`id`, `order_number`, `user_id`, `passenger_name`, `passenger_email`, `passenger_phone`, `passenger_passport`, `passenger_dob`, `insurance`, `transfer`, `meal`, `sms`, `service_fee`, `total_amount`, `payment_method`, `delivery_method`, `status`, `created_at`) VALUES
(1, 'UF-2026-00815', 2, 'Иван Иванов', 'ivan@example.com', '+7 (917) 000-00-00', '8014 123456', '1995-04-12', 450, 0, 0, 0, 150, 6000, 'Банковская карта', 'Электронный билет', 'Оплачен', '2026-06-15 08:30:00'),
(2, 'UF-2026-00816', 2, 'Иван Иванов', 'ivan@example.com', '+7 (917) 000-00-00', '8014 123456', '1995-04-12', 0, 0, 0, 99, 150, 5099, 'СБП', 'Электронный билет', 'В обработке', '2026-06-22 10:15:00'),
(3, 'UF-2025-00412', 2, 'Иван Иванов', 'ivan@example.com', '+7 (917) 000-00-00', '8014 123456', '1995-04-12', 450, 1200, 350, 99, 150, 8349, 'Банковская карта', 'Электронный билет', 'Отменён', '2025-08-10 14:00:00');

-- ---------------------------------------------------
-- Таблица состава заказа (order_items)
-- ---------------------------------------------------
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `order_items` (`id`, `order_id`, `flight_id`, `seat_number`, `passenger_name`, `price`) VALUES
(1, 1, 1, '2B', 'Иван Иванов', 5400),
(2, 2, 2, '4A', 'Иван Иванов', 4850),
(3, 3, 9, '12F', 'Иван Иванов', 6100);

-- ---------------------------------------------------
-- Таблица корзины (cart)
-- ---------------------------------------------------
DROP TABLE IF EXISTS `cart`;
CREATE TABLE `cart` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL,
    `flight_id` INT NOT NULL,
    `seat_number` VARCHAR(20) NOT NULL,
    `price` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_cart_flight` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
