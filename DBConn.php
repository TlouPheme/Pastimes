<?php
/*
    GROUP MEMBERS:
    Tlou Pheme - ST10177726
    Mahlatse Mphelo - ST10449570

    Declaration: This code is our own group work except where external sources are referenced.
*/

$dbHost = 'localhost';
$dbUser = 'root';
$dbPassword = '';
$dbName = $databaseName ?? 'thrift_store';

$conn = new mysqli($dbHost, $dbUser, $dbPassword, $dbName);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

if (!function_exists('column_exists')) {
    function column_exists(mysqli $conn, string $table, string $column): bool
    {
        $stmt = $conn->prepare('
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?
        ');
        $stmt->bind_param('ss', $table, $column);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return (int)($result['total'] ?? 0) > 0;
    }
}

$conn->query("
    CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL
    )
");

$conn->query("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
        verification_status ENUM('pending', 'verified') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

if (!column_exists($conn, 'users', 'role')) {
    $conn->query("ALTER TABLE users ADD role ENUM('admin', 'customer') NOT NULL DEFAULT 'admin' AFTER password");
}

if (!column_exists($conn, 'users', 'verification_status')) {
    $conn->query("ALTER TABLE users ADD verification_status ENUM('pending', 'verified') NOT NULL DEFAULT 'verified' AFTER role");
}

$conn->query("UPDATE users SET verification_status = 'verified' WHERE role = 'admin'");

$conn->query("
    CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL DEFAULT 0,
        image VARCHAR(255),
        category_id INT,
        status ENUM('available', 'sold') NOT NULL DEFAULT 'available',
        featured TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    )
");

if (!column_exists($conn, 'products', 'status')) {
    $conn->query("ALTER TABLE products ADD status ENUM('available', 'sold') NOT NULL DEFAULT 'available' AFTER category_id");
}

if (!column_exists($conn, 'products', 'featured')) {
    $conn->query("ALTER TABLE products ADD featured TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
}

$conn->query("
    CREATE TABLE IF NOT EXISTS inquiries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        user_id INT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(190) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('new', 'contacted', 'closed') NOT NULL DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )
");

$conn->query("
    CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_favorite (user_id, product_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )
");

$conn->query("
    CREATE TABLE IF NOT EXISTS product_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        image VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )
");

$conn->query("
    CREATE TABLE IF NOT EXISTS cart_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_cart_item (user_id, product_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )
");

$conn->query("
    CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(40) NOT NULL,
        delivery_method ENUM('pickup', 'delivery') NOT NULL DEFAULT 'pickup',
        address TEXT NULL,
        notes TEXT NULL,
        total DECIMAL(10,2) NOT NULL DEFAULT 0,
        status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
");

$conn->query("
    CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NULL,
        product_name VARCHAR(255) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        image VARCHAR(255) NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
    )
");
