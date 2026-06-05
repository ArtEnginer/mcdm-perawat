CREATE DATABASE IF NOT EXISTS mcdm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mcdm;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    username VARCHAR(80) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'operator') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY users_username_unique (username)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS criteria (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(12) NOT NULL,
    name VARCHAR(150) NOT NULL,
    sort_order TINYINT UNSIGNED NOT NULL,
    weight DECIMAL(10,6) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY criteria_code_unique (code),
    UNIQUE KEY criteria_sort_unique (sort_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS perawat (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    employee_code VARCHAR(30) NULL,
    name VARCHAR(150) NOT NULL,
    c1 DECIMAL(10,2) NOT NULL DEFAULT 0,
    c2 DECIMAL(10,2) NOT NULL DEFAULT 0,
    c3 DECIMAL(10,2) NOT NULL DEFAULT 0,
    c4 DECIMAL(10,2) NOT NULL DEFAULT 0,
    c5 DECIMAL(10,2) NOT NULL DEFAULT 0,
    c6 DECIMAL(10,2) NOT NULL DEFAULT 0,
    c7 DECIMAL(10,2) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY perawat_employee_code_unique (employee_code)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ahp_pairs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    criteria_id_left INT UNSIGNED NOT NULL,
    criteria_id_right INT UNSIGNED NOT NULL,
    value DECIMAL(12,6) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY ahp_pair_unique (criteria_id_left, criteria_id_right),
    CONSTRAINT fk_ahp_left FOREIGN KEY (criteria_id_left) REFERENCES criteria (id) ON DELETE CASCADE,
    CONSTRAINT fk_ahp_right FOREIGN KEY (criteria_id_right) REFERENCES criteria (id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO criteria (code, name, sort_order, weight, is_active) VALUES
('C1', 'Komp. Klinis', 1, 0.418116, 1),
('C2', 'Profesionalisme', 2, 0.229216, 1),
('C3', 'Kolaborasi Tim', 3, 0.112646, 1),
('C4', 'Orientasi Hasil', 4, 0.050868, 1),
('C5', 'Keselamatan', 5, 0.112646, 1),
('C6', 'Mgmt Waktu', 6, 0.050868, 1),
('C7', 'Komunikasi', 7, 0.025640, 1)
ON DUPLICATE KEY UPDATE
name = VALUES(name),
weight = VALUES(weight),
is_active = VALUES(is_active);

INSERT INTO users (name, username, password_hash, role) VALUES
('Administrator', 'admin', '$2y$10$QJ9vEn8/7P/yMpBpKhNpi.Y//7vv3jRaqYSaOw/2/vo4AzZg4od9q', 'admin'),
('Operator', 'operator', '$2y$10$/Zv4bsKcXhl7F3bqJ4BXu.JkC6Ei3/XTP.6SNCv7SRbRgIb5qWhEu', 'operator')
ON DUPLICATE KEY UPDATE
name = VALUES(name),
password_hash = VALUES(password_hash),
role = VALUES(role);

INSERT INTO perawat (employee_code, name, c1, c2, c3, c4, c5, c6, c7, notes, is_active) VALUES
('P001', 'Andi Setiawan', 8, 7, 9, 8, 9, 7, 8, 'Tim unggul', 1),
('P002', 'Budi Santoso', 5, 6, 5, 6, 5, 6, 5, 'Butuh pendampingan', 1),
('P003', 'Citra Dewi', 9, 9, 8, 9, 9, 8, 9, 'Kinerja sangat baik', 1),
('P004', 'Dian Pratiwi', 6, 5, 6, 5, 6, 5, 6, 'Stabil', 1),
('P005', 'Eko Wahyudi', 7, 8, 7, 8, 7, 8, 7, 'Konsisten', 1),
('P006', 'Fitri Handayani', 4, 5, 4, 5, 4, 5, 4, 'Perlu pembinaan', 1),
('P007', 'Galih Nugroho', 9, 8, 9, 9, 8, 9, 8, 'Sangat baik', 1),
('P008', 'Hana Putri', 6, 6, 7, 6, 7, 6, 7, 'Cukup baik', 1),
('P009', 'Indra Kusuma', 8, 9, 8, 8, 9, 9, 8, 'Kinerja baik', 1),
('P010', 'Joko Purnomo', 5, 4, 5, 4, 5, 4, 5, 'Butuh perhatian', 1)
ON DUPLICATE KEY UPDATE
name = VALUES(name),
c1 = VALUES(c1),
c2 = VALUES(c2),
c3 = VALUES(c3),
c4 = VALUES(c4),
c5 = VALUES(c5),
c6 = VALUES(c6),
c7 = VALUES(c7),
notes = VALUES(notes),
is_active = VALUES(is_active);
