-- InfinityFree setup:
-- 1. Drop all tables, then run the whole file in phpMyAdmin SQL tab.
-- 2. Update db.php with your InfinityFree DB credentials (already done).
--
-- Login after reset: admin@purkynka.cz / password
--
-- Migration from old schema (run these ALTER TABLEs if already have data):
--   ALTER TABLE thesis MODIFY status ENUM('in_progress','submitted') DEFAULT 'in_progress';
--   ALTER TABLE thesis ADD COLUMN grade INT NULL AFTER status;
--   ALTER TABLE thesis CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
--   ALTER TABLE thesis MODIFY status ENUM('in_progress','submitted') DEFAULT 'in_progress';
--   ALTER TABLE thesis ADD COLUMN grade INT NULL AFTER status;
--   ALTER TABLE thesis DROP COLUMN teacher_note, ADD COLUMN teacher_note TEXT AFTER student_note;
--

-- Drop all tables (uncomment to reset):
-- DROP TABLE IF EXISTS thesis_files, messages, thesis, user, class;

-- On InfinityFree skip the next two lines (your DB already exists).
-- CREATE DATABASE IF NOT EXISTS my_database ...
-- USE ...

CREATE TABLE class (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL,
    final_year YEAR NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher', 'admin') NOT NULL,
    class_id INT NULL,
    teacher_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES class(id) ON DELETE SET NULL,
    FOREIGN KEY (teacher_id) REFERENCES user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE thesis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    student_id INT NOT NULL UNIQUE,
    supervisor_id INT NULL,
    instruction_pdf_path VARCHAR(255),
    student_note TEXT,
    teacher_note TEXT,
    status ENUM('in_progress', 'submitted') DEFAULT 'in_progress',
    grade INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (supervisor_id) REFERENCES user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE thesis_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thesis_id INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thesis_id) REFERENCES thesis(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (sender_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed data: all passwords are "password"
-- Login: admin@purkynka.cz / password
INSERT INTO class (name, final_year) VALUES
    ('V3A', 2026),
    ('V2A', 2027),
    ('V4A', 2025),
    ('V1A', 2029);

INSERT INTO user (first_name, last_name, email, password, role) VALUES
    ('Admin', 'User', 'admin@purkynka.cz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

INSERT INTO user (first_name, last_name, email, password, role, class_id) VALUES
    ('Jan', 'Novak',    'novak.jan@purkynka.cz',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
    ('Peter', 'Svoboda',  'svoboda.peter@purkynka.cz',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 2),
    ('Martha', 'Dvorak',  'dvorak.martha@purkynka.cz',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 3),
    ('Anna', 'Novotna',  'novotna.anna@purkynka.cz',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
    ('Tomas', 'Dvoracek', 'dvoracek.tomas@purkynka.cz',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 2),
    ('Lucie', 'Horakova', 'horakova.lucie@purkynka.cz',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 4);

INSERT INTO user (first_name, last_name, email, password, role) VALUES
    ('Karel', 'Ucitel',   'karel.ucitel@purkynka.cz',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher'),
    ('Eva', 'Mistr',     'eva.mistr@purkynka.cz',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher'),
    ('Petr', 'Skolitel',  'petr.skolitel@purkynka.cz',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher');

UPDATE user SET teacher_id = 8 WHERE id IN (2, 4);
UPDATE user SET teacher_id = 9 WHERE id IN (3, 5);
UPDATE user SET teacher_id = 10 WHERE id IN (6, 7);

INSERT INTO thesis (title, description, student_id, supervisor_id, status) VALUES
    ('Maturita Manager',    'Webová aplikace pro správu maturitních prací',    2, 8,  'in_progress'),
    ('AI Chatbot',          'Chatbot pro školní web',                         3, 9,  'submitted'),
    ('Web redesign',        'Redesign školního webu',                         4, 8,  'in_progress'),
    ('Databázový systém',   'Systém pro správu databáze studentů',            5, 9,  'in_progress'),
    ('Bezpečnost sítě',     'Analýza zabezpečení školní sítě',                6, 10, 'submitted'),
    ('Mobilní aplikace',    'Mobilní aplikace pro školu',                     7, 10, 'in_progress');

