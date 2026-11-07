
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

    $product_code = trim($_POST["product_code"] ?? "");
    $product_name = trim($_POST["product_name"] ?? "");
    $category = $_POST["category"] ?? "Food";
    $unit = $_POST["unit"] ?? "Pes";
    $price = $_POST["price"] ?? 0;
    $stock = $_POST["stock"] ?? 0;
    $entry_date = $_POST["entry_date"] ?? date("Y-m-d");
    $description = trim($_POST["description"] ?? "");

    if ($product_code !== "" && $product_name !== "" &&
      is_numeric($price) && is_numeric($stock) &&
      $price >= 0 && $stock >= 0) {

        $stmt = $pdo->prepare("
            INSERT INTO products
            (product_code, product_name, category, unit, price, stock, entry_date, description)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $product_code,
            $product_name,
            $category,
            $unit,
            $price,
            $stock,
            $entry_date,
            $description
        ]);

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

      main {
        width: 35%;
        margin: 20px auto;
      }
    </style>
</head>

<body>
  <main>
    <h1>Products</h1>
    <a href="index.php">Kembali ke halaman utama</a>

    <hr>

    <form method="POST">

        <label>Product Code</label>
        <input
            type="text"
            name="product_code"
            required
        >

        <br><br>

        <label>Product Name</label>
        <input
            type="text"
            name="product_name"
            required
        >

        <br><br>

        <label>Category</label>
        <select name="category">
            <option value="Food">Food</option>
            <option value="Drink">Drink</option>
            <option value="Stationery">Stationery</option>
            <option value="Uniform">Uniform</option>
        </select>

        <br><br>

        <label>Unit</label>
        <select name="unit">
            <option value="Pes">Pes</option>
            <option value="Pack">Pack</option>
            <option value="Bottle">Bottle</option>
        </select>

        <br><br>

        <label>Price</label>
        <input
            type="number"
            name="price"
            min="0"
            step="0.01"
            required
        >

        <br><br>

        <label>Stock</label>
        <input
            type="number"
            name="stock"
            min="0"
            required
        >

        <br><br>

        <label>Date</label>
        <input
            type="date"
            name="entry_date"
            required
        >

        <br><br>

        <label>Description</label>
        <textarea name="description"></textarea>

        <br><br>

        <button type="submit">Save Product</button>

    </form>



    <h2>Saved Products</h2>

    <?php foreach ($products as $entry): ?>

        <div>

            <h3>
                <?= htmlspecialchars($entry['product_name']) ?>
            </h3>

            <p>
                Price: Rp <?= number_format($entry['price'], 0, ',', '.') ?>
            </p>

            <p>
                Stock: <?= htmlspecialchars($entry['stock']) ?>
            </p>

            <p>
                Category: <?= htmlspecialchars($entry['category']) ?>
            </p>

            <p>
                Description: <?= htmlspecialchars($entry['description'] ?? '') ?>
            </p>

            <hr>

        </div>

    <?php endforeach; ?>
  </main>
</body>
</html>
