CREATE DATABASE IF NOT EXISTS week3_employee_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE week3_employee_db;

CREATE TABLE IF NOT EXISTS employees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    department VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO employees (name, email, department) VALUES
('Rahul Patil', 'rahul.patil@example.com', 'Information Technology'),
('Priya Sharma', 'priya.sharma@example.com', 'Human Resources'),
('Amit Deshmukh', 'amit.deshmukh@example.com', 'Finance');
