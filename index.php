<?php

define('APP_BOOTSTRAPPED', true);

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

$page = $_GET['page'] ?? 'home';

switch ($page) {

    case 'products':
        require __DIR__ . '/products.php';
        break;

    case 'suppliers':
        require __DIR__ . '/suppliers.php';
        break;

    case 'employees':
        require __DIR__ . '/employees.php';
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

            main {
                width: 35%;
                margin: 20px auto;
            }
            </style>
        </head>

        <body>
            <main>
                <h1>Welcome</h1>

                <a href="index.php?page=products">
                    Manage Products
                </a>

                <br>

                <a href="index.php?page=suppliers">
                    Manage suppliers
                </a>

                <br>
                
                <a href="index.php?page=employees">
                    Manage employees
                </a>

            </main> 
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