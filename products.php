
<?php

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


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $product_name = trim($_POST["product_name"] ?? "");

    if ($product_name !== "") {

        $stmt = $pdo->prepare(
            "INSERT INTO products (product_name) VALUES (?)"
        );

        $stmt->execute([$product_name]);

    }

    header("Location: products.php");
    exit;
}


$stmt = $pdo->query(
    "SELECT * FROM products ORDER BY product_id DESC"
);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>
    <title>My First Website</title>
    <style>
      * {
        font-family: Arial, Helvetica, sans-serif;
      }
    </style>
</head>

<body>
    <h1>Koperasi Sekolah</h1>
    <a href="index.php">Kembali ke halaman utama</a>
    <hr>

    <form method="POST">

        <input
            type="text"
            name="product_name"
            placeholder="Write something..."
            required
        >

        <button type="submit">Save</button>

    </form>



    <h2>Saved products</h2>

    <?php foreach ($products as $entry): ?>

        <p>
            <?= htmlspecialchars($entry['product_name'], ENT_QUOTES, 'UTF-8') ?>
        </p>

    <?php endforeach; ?>

</body>
</html>
