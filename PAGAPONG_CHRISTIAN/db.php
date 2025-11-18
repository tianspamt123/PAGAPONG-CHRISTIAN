<?php

{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dbHost = '127.0.0.1';
    $dbName = 'pagapong'; // default DB name (you can change)
    $dbUser = 'root';
    $dbPass = ''; // XAMPP default is empty password for root
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$charset}";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
        return $pdo;
    } catch (PDOException $e) {
        // If the database doesn't exist, give a helpful message.
        // In production you may want to hide details.
        die("Database connection failed: " . htmlspecialchars($e->getMessage()));
    }
}


?>
