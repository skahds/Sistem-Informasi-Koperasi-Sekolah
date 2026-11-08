<?php

define('APP_BOOTSTRAPPED', true);

// ======================================
// INITIALIZATION
// ======================================

$host = "localhost";
$dbname = "db_cooperative";
$username = "root";
$password = "";

$pdo = new PDO(
    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
    $username,
    $password
);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


// ======================================
// PAGE ROUTING
// ======================================

$page = $_GET['page'] ?? 'home';

switch ($page) {

    case 'products':
        require __DIR__ . '/products.php';
        break;

    case 'home':
        ?>

        <!DOCTYPE html>
        <html>
        <head>
            <title>Home</title>
            <style>
              * {
                font-family: Arial, Helvetica, sans-serif;
              }
            </style>
        </head>

        <body>

            <h1>Welcome</h1>

            <a href="index.php?page=products">
                Manage Products
            </a>

        </body>
        </html>

        <?php
        break;

    default:
        http_response_code(404);
        echo "Page not found.";
        break;
}

?>