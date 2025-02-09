<?php
require_once 'db_connect.php';

$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    cnic VARCHAR(15) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating table: " . $conn->error);
}

$sql = "CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    program VARCHAR(20) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    father_name VARCHAR(100) NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    campus_name VARCHAR(100) NOT NULL,
    board_roll_no VARCHAR(50) NOT NULL,
    board_marks VARCHAR(20) NOT NULL,
    college_name VARCHAR(100) NOT NULL,
    photo_path VARCHAR(255),
    registration_slip_no VARCHAR(20),
    status ENUM('draft', 'submitted', 'rejected', 'accepted') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating applications table: " . $conn->error);
}
?>
