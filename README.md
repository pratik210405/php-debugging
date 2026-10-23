# EmployeeHub — PHP Web Application Debugging Project

A responsive Employee Management System built with PHP and MySQL for the Week 3 internship task.

## Features
- Create, read, update and delete employee records (CRUD)
- Search by employee name, email or department
- Server-side input validation
- PDO prepared statements to help prevent SQL injection
- HTML output escaping to help prevent cross-site scripting (XSS)
- CSRF token validation for form submissions
- Responsive layout for mobile and desktop
- Duplicate email handling and user feedback

## Run locally with XAMPP
1. Start Apache and MySQL in XAMPP.
2. Place this project folder inside `C:\xampp\htdocs\week3-php-debugging`.
3. Open phpMyAdmin and import `database.sql`.
4. If required, update the database credentials at the top of `index.php`.
5. Open `http://localhost/week3-php-debugging/`.

## Notes
This is a learning project. For production use, add authentication/authorization, environment-based secrets, logging, and deployment-specific security controls.
