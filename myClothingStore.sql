/*
    GROUP MEMBERS:
    Tlou Pheme - ST10177726
    Mahlatse Mphelo - ST10449570

    Declaration: This SQL is our own group work except where external sources are referenced.
*/

DROP DATABASE IF EXISTS ClothingStore;
CREATE DATABASE ClothingStore;
USE ClothingStore;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
    verification_status ENUM('pending', 'verified') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    image VARCHAR(255),
    category_id INT,
    status ENUM('available', 'sold') NOT NULL DEFAULT 'available',
    featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE inquiries (
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
);

CREATE TABLE favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorite (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart_item (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE orders (
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
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NULL,
    product_name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

CREATE TABLE tblUser (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    FullName VARCHAR(100) NOT NULL,
    EmailAddress VARCHAR(190) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    UserRole ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
    VerificationStatus ENUM('pending', 'verified') NOT NULL DEFAULT 'pending',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tblAdmin (
    AdminID INT AUTO_INCREMENT PRIMARY KEY,
    FullName VARCHAR(100) NOT NULL,
    EmailAddress VARCHAR(190) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tblClothes (
    ClothesID INT AUTO_INCREMENT PRIMARY KEY,
    ClothesName VARCHAR(255) NOT NULL,
    Description TEXT,
    SellPrice DECIMAL(10,2) NOT NULL,
    ImageFile VARCHAR(255),
    CategoryName VARCHAR(100),
    Status VARCHAR(30) DEFAULT 'available'
);

CREATE TABLE tblAorder (
    AOrderID INT AUTO_INCREMENT PRIMARY KEY,
    CustomerName VARCHAR(100) NOT NULL,
    CustomerEmail VARCHAR(190) NOT NULL,
    Total DECIMAL(10,2) NOT NULL,
    OrderStatus VARCHAR(30) DEFAULT 'pending',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO categories (id, name) VALUES
(1, 'Men'),
(2, 'Women'),
(3, 'Vintage'),
(4, 'Accessories');

INSERT INTO users (id, name, email, password, role, verification_status) VALUES
(1, 'Admin User', 'admin@pastimes.co.za', '$2y$10$c9vB6uscuXWMkgiJTh..M.PUH2WrVUxBqwwHGfhaWbgR1tpV085ia', 'admin', 'verified'),
(2, 'John Doe', 'j.doe@abc.co.za', '$2y$10$tx91ya1AZrH59CCmv7UUU.0OzH3jF0xknb3wSEDK1./nwiEFw44Ni', 'customer', 'verified'),
(3, 'Ayesha Khan', 'ayesha@example.co.za', '$2y$10$OjGG6C8vX9bkLp8ve.waJeA9I8hqv9WEG0jbN30hkXpwQFwWhnKSW', 'customer', 'pending');

INSERT INTO products (id, name, description, price, image, category_id, status, featured) VALUES
(1, '90s Vintage Adidas Jacket', 'Classic second-hand Adidas jacket with visible vintage details.', 650.00, '90s vintage adidas jacket front.webp', 3, 'available', 1),
(2, 'Adidas Black Track Pants', 'Pre-owned black Adidas track pants with logo and tag details.', 420.00, 'adidas pants black front.webp', 1, 'available', 0),
(3, 'Nike Court Box Logo T-shirt', 'Second-hand Nike Court t-shirt with box logo front.', 280.00, 'nike court t-shirt box logo front.webp', 1, 'available', 1);

INSERT INTO product_images (product_id, image) VALUES
(1, '90s adidas jacket back.webp'),
(1, '90s adidas jacket tag.webp'),
(1, '90s adidas jacket zipper.webp'),
(2, 'adidas pants black back.webp'),
(2, 'adidas pants black detail.webp'),
(2, 'adidas pants black logo.webp'),
(3, 'nike court t-shirt box logo logo.webp'),
(3, 'nike court t-shirt box logo zoom.webp');

INSERT INTO inquiries (product_id, user_id, name, email, message, status) VALUES
(1, 2, 'John Doe', 'j.doe@abc.co.za', 'Is this jacket still available for pickup?', 'new');

INSERT INTO favorites (user_id, product_id) VALUES
(2, 1);

INSERT INTO orders (id, user_id, name, email, phone, delivery_method, address, notes, total, status) VALUES
(1, 2, 'John Doe', 'j.doe@abc.co.za', '0712345678', 'pickup', NULL, 'Demo order for Part 2.', 280.00, 'pending');

INSERT INTO order_items (order_id, product_id, product_name, price, image) VALUES
(1, 3, 'Nike Court Box Logo T-shirt', 280.00, 'nike court t-shirt box logo front.webp');

INSERT INTO tblUser (FullName, EmailAddress, PasswordHash, UserRole, VerificationStatus) VALUES
('Admin User', 'admin@pastimes.co.za', '$2y$10$c9vB6uscuXWMkgiJTh..M.PUH2WrVUxBqwwHGfhaWbgR1tpV085ia', 'admin', 'verified'),
('John Doe', 'j.doe@abc.co.za', '$2y$10$tx91ya1AZrH59CCmv7UUU.0OzH3jF0xknb3wSEDK1./nwiEFw44Ni', 'customer', 'verified'),
('Lerato Mokoena', 'lerato@example.co.za', '$2y$10$1f4yv/r9QdPCaGo.qfXjwuh.1B7t68L747jfele8O8eZGpxDSwNNa', 'customer', 'verified'),
('Ayesha Khan', 'ayesha@example.co.za', '$2y$10$OjGG6C8vX9bkLp8ve.waJeA9I8hqv9WEG0jbN30hkXpwQFwWhnKSW', 'customer', 'pending'),
('Michael Smith', 'michael@example.co.za', '$2y$10$FbLhoiPwsqrfQ4rHo/bZt.w3gldgat2qPAAW6YlKos3ddr8hZdLQy', 'customer', 'pending');

INSERT INTO tblAdmin (FullName, EmailAddress, PasswordHash) VALUES
('Admin User', 'admin@pastimes.co.za', '$2y$10$c9vB6uscuXWMkgiJTh..M.PUH2WrVUxBqwwHGfhaWbgR1tpV085ia');

INSERT INTO tblClothes (ClothesName, Description, SellPrice, ImageFile, CategoryName, Status) VALUES
('90s Vintage Adidas Jacket', 'Classic second-hand Adidas jacket with visible vintage details.', 650.00, '90s vintage adidas jacket front.webp', 'Vintage', 'available'),
('Adidas Black Track Pants', 'Pre-owned black Adidas track pants with logo and tag details.', 420.00, 'adidas pants black front.webp', 'Men', 'available'),
('Nike Court Box Logo T-shirt', 'Second-hand Nike Court t-shirt with box logo front.', 280.00, 'nike court t-shirt box logo front.webp', 'Men', 'available');

INSERT INTO tblAorder (CustomerName, CustomerEmail, Total, OrderStatus) VALUES
('John Doe', 'j.doe@abc.co.za', 280.00, 'pending');
