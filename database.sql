-- ==============================================================
-- Лоиҳа: Системаи автоматикунонидашудаи фурӯши чиптаҳои қатора
-- Мавзӯъ: Коркарди веблоиҳаи фурӯши чиптаҳо барои қатораҳои байнишаҳрӣ
-- Фан: Технология ва забонҳои интернет-барномасозӣ
-- Пойгоҳи додаҳо: train_tickets_db
-- ==============================================================

CREATE DATABASE IF NOT EXISTS `train_tickets_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `train_tickets_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Ҷадвали корбарон (users)
DROP TABLE IF EXISTS `tickets`;
DROP TABLE IF EXISTS `seats`;
DROP TABLE IF EXISTS `cars`;
DROP TABLE IF EXISTS `trips`;
DROP TABLE IF EXISTS `routes`;
DROP TABLE IF EXISTS `trains`;
DROP TABLE IF EXISTS `stations`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(30) NOT NULL,
  `passport_series` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('passenger', 'cashier', 'admin') DEFAULT 'passenger',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Ҷадвали истгоҳҳо (stations)
CREATE TABLE `stations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(10) NOT NULL UNIQUE,
  `city` VARCHAR(100) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Ҷадвали қатораҳо (trains)
CREATE TABLE `trains` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `train_number` VARCHAR(50) NOT NULL UNIQUE,
  `train_name` VARCHAR(100) NOT NULL,
  `train_type` ENUM('express', 'intercity', 'highspeed') DEFAULT 'express',
  `status` ENUM('active', 'maintenance') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Ҷадвали хатсайрҳо (routes)
CREATE TABLE `routes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `route_name` VARCHAR(150) NOT NULL,
  `from_station_id` INT NOT NULL,
  `to_station_id` INT NOT NULL,
  `distance_km` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `duration_hours` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`from_station_id`) REFERENCES `stations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`to_station_id`) REFERENCES `stations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Ҷадвали қатнавҳо (trips)
CREATE TABLE `trips` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `route_id` INT NOT NULL,
  `train_id` INT NOT NULL,
  `departure_time` DATETIME NOT NULL,
  `arrival_time` DATETIME NOT NULL,
  `base_price` DECIMAL(10,2) NOT NULL DEFAULT 80.00,
  `status` ENUM('scheduled', 'boarding', 'departed', 'completed', 'cancelled') DEFAULT 'scheduled',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`route_id`) REFERENCES `routes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`train_id`) REFERENCES `trains`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Ҷадвали вагонҳо (cars)
CREATE TABLE `cars` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `train_id` INT NOT NULL,
  `car_number` INT NOT NULL,
  `car_type` ENUM('platskart', 'coupe', 'sv_lux') NOT NULL DEFAULT 'coupe',
  `total_seats` INT NOT NULL DEFAULT 36,
  `price_multiplier` DECIMAL(4,2) NOT NULL DEFAULT 1.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`train_id`) REFERENCES `trains`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Ҷадвали ҷойҳо (seats)
CREATE TABLE `seats` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `car_id` INT NOT NULL,
  `seat_number` INT NOT NULL,
  `seat_type` ENUM('lower', 'upper', 'side_lower', 'side_upper') NOT NULL DEFAULT 'lower',
  FOREIGN KEY (`car_id`) REFERENCES `cars`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Ҷадвали чиптаҳо (tickets)
-- Талаботи махсус: Банд кардани ҷой ба муддати 15 дақиқа то пардохт
CREATE TABLE `tickets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_number` VARCHAR(50) NOT NULL UNIQUE,
  `trip_id` INT NOT NULL,
  `car_id` INT NOT NULL,
  `seat_id` INT NOT NULL,
  `passenger_id` INT NULL,
  `passenger_name` VARCHAR(150) NOT NULL,
  `passenger_passport` VARCHAR(50) NOT NULL,
  `passenger_phone` VARCHAR(30) NULL,
  `cashier_id` INT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('online_card', 'alif_mobi', 'dushanbe_city', 'cash') DEFAULT 'online_card',
  `payment_status` ENUM('pending', 'paid', 'refunded', 'expired') DEFAULT 'pending',
  `status` ENUM('reserved', 'confirmed', 'cancelled', 'refunded') DEFAULT 'reserved',
  `reserved_at` DATETIME NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `purchased_at` DATETIME NULL,
  `refunded_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`trip_id`) REFERENCES `trips`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`car_id`) REFERENCES `cars`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`seat_id`) REFERENCES `seats`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`passenger_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`cashier_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================
-- МАЪЛУМОТҲОИ ИБТИДОӢ (SEED DATA) БАРОИ САНҶИШИ ПУРРАИ СИСТЕМА
-- ==============================================================

-- Рамз барои ҳамаи корбарони санҷишӣ: 123456
-- Ҳашшуда бо password_hash('123456', PASSWORD_BCRYPT)
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `passport_series`, `password`, `role`) VALUES
(1, 'Администратори Система', 'admin@railway.tj', '+992900000001', 'A10203040', '$2y$10$e8l4p97k1sQ2Uqg23r6bC.rK3kY43Z1Vl3iK2p3hK9q4m5o6p7q8r', 'admin'),
(2, 'Кассири Калон', 'cashier@railway.tj', '+992900000002', 'A20304050', '$2y$10$e8l4p97k1sQ2Uqg23r6bC.rK3kY43Z1Vl3iK2p3hK9q4m5o6p7q8r', 'cashier'),
(3, 'Рустам Саидов', 'rustam@gmail.com', '+992935551122', 'A30405060', '$2y$10$e8l4p97k1sQ2Uqg23r6bC.rK3kY43Z1Vl3iK2p3hK9q4m5o6p7q8r', 'passenger'),
(4, 'Нигина Каримова', 'nigina@mail.ru', '+992988884433', 'A40506070', '$2y$10$e8l4p97k1sQ2Uqg23r6bC.rK3kY43Z1Vl3iK2p3hK9q4m5o6p7q8r', 'passenger');

-- Истгоҳҳои роҳи оҳани Ҷумҳурии Тоҷикистон
INSERT INTO `stations` (`id`, `name`, `code`, `city`, `is_active`) VALUES
(1, 'Вокзали Роҳи Оҳани Душанбе-1', 'DSH1', 'Душанбе', 1),
(2, 'Вокзали Хуҷанд', 'KHJ', 'Хуҷанд', 1),
(3, 'Истгоҳи Бохтар (Қурғонтеппа)', 'BOK', 'Бохтар', 1),
(4, 'Истгоҳи Кӯлоб', 'KLB', 'Кӯлоб', 1),
(5, 'Истгоҳи Регар (Турсунзода)', 'REG', 'Турсунзода', 1),
(6, 'Истгоҳи Конибодом', 'KAN', 'Конибодом', 1);

-- Қатораҳо
INSERT INTO `trains` (`id`, `train_number`, `train_name`, `train_type`, `status`) VALUES
(1, '001/002', 'Экспресси «Сомониён»', 'highspeed', 'active'),
(2, '003/004', 'Қатораи «Помир Express»', 'express', 'active'),
(3, '005/006', 'Қатораи байнишаҳрии «Хатлон»', 'intercity', 'active');

-- Хатсайрҳо
INSERT INTO `routes` (`id`, `route_name`, `from_station_id`, `to_station_id`, `distance_km`, `duration_hours`) VALUES
(1, 'Душанбе — Хуҷанд', 1, 2, 341.00, '4 соату 45 дақиқа'),
(2, 'Хуҷанд — Душанбе', 2, 1, 341.00, '4 соату 45 дақиқа'),
(3, 'Душанбе — Бохтар', 1, 3, 115.00, '2 соату 10 дақиқа'),
(4, 'Бохтар — Душанбе', 3, 1, 115.00, '2 соату 10 дақиқа'),
(5, 'Душанбе — Кӯлоб', 1, 4, 185.00, '3 соату 20 дақиқа'),
(6, 'Душанбе — Турсунзода', 1, 5, 65.00, '1 соату 15 дақиқа');

-- Қатнавҳо (Trips)
-- Барои санаҳои наздиктарин: имрӯз, фардо ва рӯзҳои минбаъда
INSERT INTO `trips` (`id`, `route_id`, `train_id`, `departure_time`, `arrival_time`, `base_price`, `status`) VALUES
(1, 1, 1, DATE_ADD(CURRENT_DATE, INTERVAL '08:00:00' HOUR_SECOND), DATE_ADD(CURRENT_DATE, INTERVAL '12:45:00' HOUR_SECOND), 120.00, 'scheduled'),
(2, 2, 1, DATE_ADD(CURRENT_DATE, INTERVAL '15:30:00' HOUR_SECOND), DATE_ADD(CURRENT_DATE, INTERVAL '20:15:00' HOUR_SECOND), 120.00, 'scheduled'),
(3, 1, 2, DATE_ADD(DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY), INTERVAL '07:30:00' HOUR_SECOND), DATE_ADD(DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY), INTERVAL '12:30:00' HOUR_SECOND), 110.00, 'scheduled'),
(4, 3, 3, DATE_ADD(CURRENT_DATE, INTERVAL '09:00:00' HOUR_SECOND), DATE_ADD(CURRENT_DATE, INTERVAL '11:10:00' HOUR_SECOND), 45.00, 'scheduled'),
(5, 5, 2, DATE_ADD(CURRENT_DATE, INTERVAL '14:00:00' HOUR_SECOND), DATE_ADD(CURRENT_DATE, INTERVAL '17:20:00' HOUR_SECOND), 60.00, 'scheduled'),
(6, 1, 1, DATE_ADD(DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), INTERVAL '08:00:00' HOUR_SECOND), DATE_ADD(DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), INTERVAL '12:45:00' HOUR_SECOND), 120.00, 'scheduled');

-- Вагонҳо барои қатораи 1 (Экспресси Сомониён)
INSERT INTO `cars` (`id`, `train_id`, `car_number`, `car_type`, `total_seats`, `price_multiplier`) VALUES
(1, 1, 1, 'sv_lux', 18, 2.00),     -- Вагони 1: СВ / Люкс (18 ҷой, нарх x 2)
(2, 1, 2, 'coupe', 36, 1.40),      -- Вагони 2: Купе (36 ҷой, нарх x 1.4)
(3, 1, 3, 'coupe', 36, 1.40),      -- Вагони 3: Купе (36 ҷой)
(4, 1, 4, 'platskart', 54, 1.00);  -- Вагони 4: Плацкарт (54 ҷой, нарх x 1.0)

-- Вагонҳо барои қатораи 2 (Помир Express)
INSERT INTO `cars` (`id`, `train_id`, `car_number`, `car_type`, `total_seats`, `price_multiplier`) VALUES
(5, 2, 1, 'coupe', 36, 1.35),
(6, 2, 2, 'platskart', 54, 1.00);

-- Вагонҳо барои қатораи 3 (Хатлон)
INSERT INTO `cars` (`id`, `train_id`, `car_number`, `car_type`, `total_seats`, `price_multiplier`) VALUES
(7, 3, 1, 'platskart', 54, 1.00),
(8, 3, 2, 'coupe', 36, 1.30);

-- Ҷойҳо (Seats): Ба таври автоматӣ пур кардани ҷойҳо барои вагонҳо
-- Вагони 1 (СВ / Люкс - 18 ҷой, ҳама поёнӣ ва бароҳат)
INSERT INTO `seats` (`car_id`, `seat_number`, `seat_type`) VALUES
(1, 1, 'lower'), (1, 2, 'lower'), (1, 3, 'lower'), (1, 4, 'lower'),
(1, 5, 'lower'), (1, 6, 'lower'), (1, 7, 'lower'), (1, 8, 'lower'),
(1, 9, 'lower'), (1, 10, 'lower'), (1, 11, 'lower'), (1, 12, 'lower'),
(1, 13, 'lower'), (1, 14, 'lower'), (1, 15, 'lower'), (1, 16, 'lower'),
(1, 17, 'lower'), (1, 18, 'lower');

-- Вагони 2 (Купе - 36 ҷой: 1-тоқ поёнӣ, 2-ҷуфт болоӣ)
INSERT INTO `seats` (`car_id`, `seat_number`, `seat_type`) VALUES
(2, 1, 'lower'), (2, 2, 'upper'), (2, 3, 'lower'), (2, 4, 'upper'),
(2, 5, 'lower'), (2, 6, 'upper'), (2, 7, 'lower'), (2, 8, 'upper'),
(2, 9, 'lower'), (2, 10, 'upper'), (2, 11, 'lower'), (2, 12, 'upper'),
(2, 13, 'lower'), (2, 14, 'upper'), (2, 15, 'lower'), (2, 16, 'upper'),
(2, 17, 'lower'), (2, 18, 'upper'), (2, 19, 'lower'), (2, 20, 'upper'),
(2, 21, 'lower'), (2, 22, 'upper'), (2, 23, 'lower'), (2, 24, 'upper'),
(2, 25, 'lower'), (2, 26, 'upper'), (2, 27, 'lower'), (2, 28, 'upper'),
(2, 29, 'lower'), (2, 30, 'upper'), (2, 31, 'lower'), (2, 32, 'upper'),
(2, 33, 'lower'), (2, 34, 'upper'), (2, 35, 'lower'), (2, 36, 'upper');

-- Вагони 3 (Купе - 36 ҷой)
INSERT INTO `seats` (`car_id`, `seat_number`, `seat_type`) VALUES
(3, 1, 'lower'), (3, 2, 'upper'), (3, 3, 'lower'), (3, 4, 'upper'),
(3, 5, 'lower'), (3, 6, 'upper'), (3, 7, 'lower'), (3, 8, 'upper'),
(3, 9, 'lower'), (3, 10, 'upper'), (3, 11, 'lower'), (3, 12, 'upper'),
(3, 13, 'lower'), (3, 14, 'upper'), (3, 15, 'lower'), (3, 16, 'upper'),
(3, 17, 'lower'), (3, 18, 'upper'), (3, 19, 'lower'), (3, 20, 'upper'),
(3, 21, 'lower'), (3, 22, 'upper'), (3, 23, 'lower'), (3, 24, 'upper'),
(3, 25, 'lower'), (3, 26, 'upper'), (3, 27, 'lower'), (3, 28, 'upper'),
(3, 29, 'lower'), (3, 30, 'upper'), (3, 31, 'lower'), (3, 32, 'upper'),
(3, 33, 'lower'), (3, 34, 'upper'), (3, 35, 'lower'), (3, 36, 'upper');

-- Вагони 4 (Плацкарт - 54 ҷой: 1-36 купе, 37-54 паҳлӯӣ/боковой)
INSERT INTO `seats` (`car_id`, `seat_number`, `seat_type`) VALUES
(4, 1, 'lower'), (4, 2, 'upper'), (4, 3, 'lower'), (4, 4, 'upper'),
(4, 5, 'lower'), (4, 6, 'upper'), (4, 7, 'lower'), (4, 8, 'upper'),
(4, 9, 'lower'), (4, 10, 'upper'), (4, 11, 'lower'), (4, 12, 'upper'),
(4, 13, 'lower'), (4, 14, 'upper'), (4, 15, 'lower'), (4, 16, 'upper'),
(4, 17, 'lower'), (4, 18, 'upper'), (4, 19, 'lower'), (4, 20, 'upper'),
(4, 21, 'lower'), (4, 22, 'upper'), (4, 23, 'lower'), (4, 24, 'upper'),
(4, 25, 'lower'), (4, 26, 'upper'), (4, 27, 'lower'), (4, 28, 'upper'),
(4, 29, 'lower'), (4, 30, 'upper'), (4, 31, 'lower'), (4, 32, 'upper'),
(4, 33, 'lower'), (4, 34, 'upper'), (4, 35, 'lower'), (4, 36, 'upper'),
(4, 37, 'side_lower'), (4, 38, 'side_upper'), (4, 39, 'side_lower'), (4, 40, 'side_upper'),
(4, 41, 'side_lower'), (4, 42, 'side_upper'), (4, 43, 'side_lower'), (4, 44, 'side_upper'),
(4, 45, 'side_lower'), (4, 46, 'side_upper'), (4, 47, 'side_lower'), (4, 48, 'side_upper'),
(4, 49, 'side_lower'), (4, 50, 'side_upper'), (4, 51, 'side_lower'), (4, 52, 'side_upper'),
(4, 53, 'side_lower'), (4, 54, 'side_upper');

-- Ҷойҳо барои вагонҳои дигар (вагон 5, 6)
INSERT INTO `seats` (`car_id`, `seat_number`, `seat_type`) VALUES
(5, 1, 'lower'), (5, 2, 'upper'), (5, 3, 'lower'), (5, 4, 'upper'),
(5, 5, 'lower'), (5, 6, 'upper'), (5, 7, 'lower'), (5, 8, 'upper'),
(6, 1, 'lower'), (6, 2, 'upper'), (6, 3, 'lower'), (6, 4, 'upper');

-- Чиптаҳои намунавӣ (Барои нишон додани ҷойҳои харидашуда ва фаъол будани ҳисоботҳо)
INSERT INTO `tickets` 
(`id`, `ticket_number`, `trip_id`, `car_id`, `seat_id`, `passenger_id`, `passenger_name`, `passenger_passport`, `passenger_phone`, `price`, `payment_method`, `payment_status`, `status`, `reserved_at`, `expires_at`, `purchased_at`)
VALUES
(1, 'TKT-2026-784102', 1, 2, 23, 3, 'Рустам Саидов', 'A30405060', '+992935551122', 168.00, 'online_card', 'paid', 'confirmed', NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 1 HOUR, NOW() - INTERVAL 2 HOUR),
(2, 'TKT-2026-891034', 1, 2, 24, 4, 'Нигина Каримова', 'A40506070', '+992988884433', 168.00, 'alif_mobi', 'paid', 'confirmed', NOW() - INTERVAL 1 HOUR, NOW(), NOW() - INTERVAL 1 HOUR);
