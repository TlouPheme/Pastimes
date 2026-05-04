<?php
/*
    GROUP MEMBERS:
    Tlou Pheme - ST10177726
    Mahlatse Mphelo - ST10449570

    Declaration: This code is our own group work except where external sources are referenced.
*/

global $conn;
$databaseName = 'ClothingStore';

$serverConnection = new mysqli('localhost', 'root', '');

if ($serverConnection->connect_error) {
    die('Connection failed: ' . $serverConnection->connect_error);
}

$serverConnection->query("CREATE DATABASE IF NOT EXISTS $databaseName");
$serverConnection->close();

include 'DBConn.php';

$conn->query('DROP TABLE IF EXISTS tblUser');
$conn->query("
    CREATE TABLE IF NOT EXISTS tblUser (
        UserID INT AUTO_INCREMENT PRIMARY KEY,
        FullName VARCHAR(100) NOT NULL,
        EmailAddress VARCHAR(190) NOT NULL UNIQUE,
        PasswordHash VARCHAR(255) NOT NULL,
        UserRole ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
        VerificationStatus ENUM('pending', 'verified') NOT NULL DEFAULT 'pending',
        CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$dataFile = __DIR__ . '/userData.txt';
$rowsLoaded = 0;

if (!is_file($dataFile)) {
    die('userData.txt was not found.');
}

$handle = fopen($dataFile, 'r');

while (($line = fgets($handle)) !== false) {
    $line = trim($line);

    if ($line === '') {
        continue;
    }

    $parts = preg_split('/\t+/', $line);

    if (count($parts) < 5) {
        continue;
    }

    [$fullName, $email, $passwordHash, $role, $verificationStatus] = $parts;
    $stmt = $conn->prepare('INSERT INTO tblUser (FullName, EmailAddress, PasswordHash, UserRole, VerificationStatus) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('sssss', $fullName, $email, $passwordHash, $role, $verificationStatus);
    $stmt->execute();
    $rowsLoaded++;
}

fclose($handle);

echo "tblUser recreated and loaded with $rowsLoaded rows.";
