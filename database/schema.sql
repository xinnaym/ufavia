-- ===================================================
-- База данных для сайта продажи авиабилетов UFAvia
-- Практическая работа № 1 (г. Стерлитамак)
-- СУБД: MySQL 5.7+ / MariaDB 10.2+
-- Версия PHP: 7.2+
-- Кодировка: utf8mb4
-- ===================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Создание базы данных (если не существует)
CREATE DATABASE IF NOT EXISTS `ufavia` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ufavia`;

-- ---------------------------------------------------
-- 1. Таблица пользователей (users)
-- Роли: 'admin' (администратор), 'user' (пассажир)
-- ---------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL COMMENT 'ФИО пользователя',
    `email` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Email (логин)',
    `phone` VARCHAR(50) NULL COMMENT 'Телефон',
    `password` VARCHAR(255) NOT NULL COMMENT 'Хэш пароля (password_hash)',
    `role` VARCHAR(50) NOT NULL DEFAULT 'user' COMMENT 'Роль: admin / user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата регистрации',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 2. Таблица рейсов / товаров (flights)
-- ---------------------------------------------------
DROP TABLE IF EXISTS `flights`;
CREATE TABLE `flights` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `flight_number` VARCHAR(50) NOT NULL COMMENT 'Номер рейса (SU-1234)',
    `airline` VARCHAR(100) NOT NULL COMMENT 'Авиакомпания',
    `airline_code` VARCHAR(10) NOT NULL COMMENT 'Код авиакомпании (SU, S7)',
    `origin` VARCHAR(100) NOT NULL COMMENT 'Город вылета (Уфа)',
    `origin_code` VARCHAR(10) NOT NULL COMMENT 'Код аэропорта вылета (UFA)',
    `destination` VARCHAR(100) NOT NULL COMMENT 'Город прилета (Москва)',
    `destination_code` VARCHAR(10) NOT NULL COMMENT 'Код аэропорта прилета (SVO)',
    `departure_date` DATE NOT NULL COMMENT 'Дата вылета',
    `departure_time` VARCHAR(20) NOT NULL COMMENT 'Время вылета',
    `arrival_time` VARCHAR(20) NOT NULL COMMENT 'Время прилета',
    `duration` INT NOT NULL COMMENT 'Длительность полета в минутах',
    `price` INT NOT NULL COMMENT 'Базовая цена билета в рублях',
    `seats_total` INT NOT NULL DEFAULT 180 COMMENT 'Всего мест в самолете',
    `seats_available` INT NOT NULL DEFAULT 42 COMMENT 'Свободных мест',
    `cabin` VARCHAR(50) NOT NULL DEFAULT 'economy' COMMENT 'Класс: economy / business',
    `stops` INT NOT NULL DEFAULT 0 COMMENT 'Пересадок: 0 - прямой рейс',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата добавления',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 3. Таблица заказов (orders)
-- ---------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `order_number` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Уникальный номер (#UF-2026-XXXXX)',
    `user_id` INT NULL COMMENT 'ID пользователя (NULL для гостя)',
    `passenger_name` VARCHAR(255) NOT NULL COMMENT 'ФИО пассажира',
    `passenger_email` VARCHAR(255) NOT NULL COMMENT 'Email для отправки билета',
    `passenger_phone` VARCHAR(50) NOT NULL COMMENT 'Контактный телефон',
    `passenger_passport` VARCHAR(100) NULL COMMENT 'Паспортные данные',
    `passenger_dob` VARCHAR(50) NULL COMMENT 'Дата рождения',
    `insurance` INT NOT NULL DEFAULT 0 COMMENT 'Страховка (руб)',
    `transfer` INT NOT NULL DEFAULT 0 COMMENT 'Трансфер (руб)',
    `meal` INT NOT NULL DEFAULT 0 COMMENT 'Горячее питание (руб)',
    `sms` INT NOT NULL DEFAULT 0 COMMENT 'SMS информирование (руб)',
    `service_fee` INT NOT NULL DEFAULT 150 COMMENT 'Сервисный сбор',
    `total_amount` INT NOT NULL COMMENT 'Итоговая стоимость заказа',
    `payment_method` VARCHAR(100) NOT NULL COMMENT 'Способ оплаты',
    `delivery_method` VARCHAR(100) NOT NULL COMMENT 'Способ получения билета',
    `status` VARCHAR(50) NOT NULL DEFAULT 'Оплачен' COMMENT 'Статус: Оплачен, В обработке, Отменён',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата оформления',
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 4. Таблица состава заказа / билетов (order_items)
-- ---------------------------------------------------
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `order_id` INT NOT NULL COMMENT 'ID заказа',
    `flight_id` INT NOT NULL COMMENT 'ID рейса',
    `seat_number` VARCHAR(20) NOT NULL COMMENT 'Выбранное место (например, 2B)',
    `passenger_name` VARCHAR(255) NULL COMMENT 'Имя пассажира',
    `price` INT NOT NULL COMMENT 'Стоимость билета',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_items_flight` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 5. Таблица корзины (cart)
-- ---------------------------------------------------
DROP TABLE IF EXISTS `cart`;
CREATE TABLE `cart` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL COMMENT 'Идентификатор сессии PHP',
    `flight_id` INT NOT NULL COMMENT 'ID рейса',
    `seat_number` VARCHAR(20) NOT NULL COMMENT 'Выбранное место',
    `price` INT NOT NULL COMMENT 'Стоимость билета',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_cart_flight` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
