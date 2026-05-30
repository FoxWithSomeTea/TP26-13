-- Create the database (run this first)
CREATE DATABASE dupe_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Switch to the app database (update this name if yours is different)
USE app;

-- ========== Classes (třídy) ==========
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL,          -- e.g. "V3A"
    final_year YEAR NOT NULL            -- graduation year
);

-- ========== Users (uživatelé) ==========
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE,          -- used for login
    password VARCHAR(255) NOT NULL,     -- hashed with password_hash()
    role ENUM('student', 'teacher', 'admin') NOT NULL,
    class_id INT NULL,                  -- NULL for teachers/admins
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
);

-- ========== Theses (maturitní práce) ==========
CREATE TABLE theses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    student_id INT NOT NULL UNIQUE,     -- each student has one thesis
    supervisor_id INT NULL,             -- teacher supervising the thesis
    assignment_pdf_path VARCHAR(255),   -- path to the thesis file
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- TODO: add FOREIGN KEY constraints for student_id and supervisor_id
