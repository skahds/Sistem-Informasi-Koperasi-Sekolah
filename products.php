
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


$mode = $_GET['mode'] ?? 'create';

if (!in_array($mode, ['create', 'edit'], true)) {
    $mode = 'create';
}

$editId = filter_var($_GET['edit_id'] ?? 0, FILTER_VALIDATE_INT);
$editId = $editId > 0 ? $editId : 0;

$error = "";
$formValues = null;


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST['action'] ?? '';

    if ($action === "delete") {

        $id = filter_var($_POST['product_id'] ?? 0, FILTER_VALIDATE_INT);

        if ($id > 0) {
            $stmt = $pdo->prepare(
                "DELETE FROM products WHERE product_id = ?"
            );
            $stmt->execute([$id]);
        }

        header("Location: products.php");
        exit;
    }


    if ($action === "create" || $action === "update") {

        $product_code = trim($_POST["product_code"] ?? "");
        $product_name = trim($_POST["product_name"] ?? "");
        $category = $_POST["category"] ?? "Food";
        $unit = $_POST["unit"] ?? "Pes";
        $price = trim($_POST["price"] ?? "");
        $stock = trim($_POST["stock"] ?? "");
        $entry_date = $_POST["entry_date"] ?? "";
        $description = trim($_POST["description"] ?? "");

        $formValues = [
            "product_code" => $product_code,
            "product_name" => $product_name,
            "category" => $category,
            "unit" => $unit,
            "price" => $price,
            "stock" => $stock,
            "entry_date" => $entry_date,
            "description" => $description
        ];

        if ($action === "update") {
            $mode = "edit";
            $editId = filter_var(
                $_POST['product_id'] ?? 0,
                FILTER_VALIDATE_INT
            );
            $editId = $editId > 0 ? $editId : 0;
        }


        $validStock = filter_var($stock, FILTER_VALIDATE_INT);

        $allowedCategories = ["Food", "Drink", "Stationery", "Uniform"];
        $allowedUnits = ["Pes", "Pack", "Bottle"];

        if (
            $product_code === "" ||
            $product_name === "" ||
            $entry_date === ""
        ) {
            $error = "Please fill in all required fields.";

        } elseif (!in_array($category, $allowedCategories, true)) {
            $error = "Invalid category.";

        } elseif (!in_array($unit, $allowedUnits, true)) {
            $error = "Invalid unit.";

        } elseif (!is_numeric($price) || (float)$price < 0) {
            $error = "Invalid price.";

        } elseif ($validStock === false || $validStock < 0) {
            $error = "Invalid stock.";
        }


        if ($error === "") {

            if ($action === "create") {

                $stmt = $pdo->prepare("
                    INSERT INTO products
                    (
                        product_code,
                        product_name,
                        category,
                        unit,
                        price,
                        stock,
                        entry_date,
                        description
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $product_code,
                    $product_name,
                    $category,
                    $unit,
                    $price,
                    $validStock,
                    $entry_date,
                    $description
                ]);

            } else {

                $check = $pdo->prepare("
                    SELECT product_id FROM products
                    WHERE product_id = ?
                ");

                $check->execute([$editId]);

                if (!$check->fetchColumn()) {

                    $error = "Product not found.";

                } else {

                    $stmt = $pdo->prepare("
                        UPDATE products
                        SET
                            product_code = ?,
                            product_name = ?,
                            category = ?,
                            unit = ?,
                            price = ?,
                            stock = ?,
                            entry_date = ?,
                            description = ?
                        WHERE product_id = ?
                    ");

                    $stmt->execute([
                        $product_code,
                        $product_name,
                        $category,
                        $unit,
                        $price,
                        $validStock,
                        $entry_date,
                        $description,
                        $editId
                    ]);
                }
            }
        }


        if ($error === "") {
            header("Location: products.php");
            exit;
        }

    } else {
        $error = "Invalid action.";
    }
}

$editProduct = null;

if ($mode === "edit" && $editId > 0) {

    $stmt = $pdo->prepare("
        SELECT * FROM products
        WHERE product_id = ?
    ");

    $stmt->execute([$editId]);

    $editProduct = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editProduct && $error === "") {
        $error = "Product not found.";
    }
}


$stmt = $pdo->query("
    SELECT * FROM products
    ORDER BY product_id DESC
");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);


$formData = [
    "product_code" => "",
    "product_name" => "",
    "category" => "Food",
    "unit" => "Pes",
    "price" => "",
    "stock" => "",
    "entry_date" => date("Y-m-d"),
    "description" => ""
];

if ($editProduct) {
    $formData = array_merge($formData, $editProduct);
}

if ($formValues !== null) {
    $formData = array_merge($formData, $formValues);
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Products</title>

    <style>
        * {
            font-family: Arial, Helvetica, sans-serif;
        }

        main {
            width: 35%;
            margin: 20px auto;
        }

        .product-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
    </style>

</head>

<body>

<main>

    <h1>Products</h1>

    <a href="index.php">Kembali ke halaman utama</a>

    <hr>

    <!-- MODE SELECTION -->

    <a href="products.php?mode=create">Create</a>

    |

    <a href="products.php?mode=edit">Edit</a>

    <hr>


    <!-- ERROR MESSAGE -->

    <?php if ($error !== ""): ?>

        <p style="color: red;">
            <?= htmlspecialchars($error) ?>
        </p>

    <?php endif; ?>


    <!-- EDIT PRODUCT DROPDOWN -->

    <?php if ($mode === "edit"): ?>

        <h2>Select Product to Edit</h2>

        <form method="GET">

            <input
                type="hidden"
                name="mode"
                value="edit"
            >

            <select name="edit_id" required>

                <option value="">Choose a product</option>

                <?php foreach ($products as $entry): ?>

                    <option
                        value="<?= (int)$entry['product_id'] ?>"
                        <?= $editId == $entry['product_id'] ? 'selected' : '' ?>
                    >

                        <?= htmlspecialchars(
                            $entry['product_code'] . " - " . $entry['product_name']
                        ) ?>

                    </option>

                <?php endforeach; ?>

            </select>

            <button type="submit">Load Product</button>

        </form>

        <hr>

    <?php endif; ?>


    <!-- CREATE / EDIT FORM -->

    <?php if ($mode === "create" || $editProduct): ?>

        <h2>
            <?= $mode === "create" ? "Create Product" : "Edit Product" ?>
        </h2>

        <form
            method="POST"
            action="<?= $mode === 'edit'
                ? 'products.php?mode=edit&edit_id=' . (int)$editId
                : 'products.php' ?>"
        >

            <input
                type="hidden"
                name="action"
                value="<?= $mode === "create" ? "create" : "update" ?>"
            >

            <?php if ($mode === "edit"): ?>

                <input
                    type="hidden"
                    name="product_id"
                    value="<?= (int)$editProduct['product_id'] ?>"
                >

            <?php endif; ?>


            <label>Product Code</label>

            <input
                type="text"
                name="product_code"
                value="<?= htmlspecialchars($formData['product_code']) ?>"
                required
            >

            <br><br>


            <label>Product Name</label>

            <input
                type="text"
                name="product_name"
                value="<?= htmlspecialchars($formData['product_name']) ?>"
                required
            >

            <br><br>


            <label>Category</label>

            <select name="category">

                <option value="Food" <?= $formData['category'] === 'Food' ? 'selected' : '' ?>>Food</option>

                <option value="Drink" <?= $formData['category'] === 'Drink' ? 'selected' : '' ?>>Drink</option>

                <option value="Stationery" <?= $formData['category'] === 'Stationery' ? 'selected' : '' ?>>Stationery</option>

                <option value="Uniform" <?= $formData['category'] === 'Uniform' ? 'selected' : '' ?>>Uniform</option>

            </select>

            <br><br>


            <label>Unit</label>

            <select name="unit">

                <option value="Pes" <?= $formData['unit'] === 'Pes' ? 'selected' : '' ?>>Pes</option>

                <option value="Pack" <?= $formData['unit'] === 'Pack' ? 'selected' : '' ?>>Pack</option>

                <option value="Bottle" <?= $formData['unit'] === 'Bottle' ? 'selected' : '' ?>>Bottle</option>

            </select>

            <br><br>


            <label>Price</label>

            <input
                type="number"
                name="price"
                min="0"
                step="0.01"
                value="<?= htmlspecialchars((string)$formData['price']) ?>"
                required
            >

            <br><br>


            <label>Stock</label>

            <input
                type="number"
                name="stock"
                min="0"
                step="1"
                value="<?= htmlspecialchars((string)$formData['stock']) ?>"
                required
            >

            <br><br>


            <label>Date</label>

            <input
                type="date"
                name="entry_date"
                value="<?= htmlspecialchars($formData['entry_date']) ?>"
                required
            >

            <br><br>


            <label>Description</label>

            <textarea name="description"><?= htmlspecialchars($formData['description']) ?></textarea>

            <br><br>


            <button type="submit">

                <?= $mode === "create" ? "Save Product" : "Update Product" ?>

            </button>

        </form>

    <?php elseif ($mode === "edit"): ?>

        <p>Select a product above to edit it.</p>

    <?php endif; ?>


    <!-- SAVED PRODUCTS -->

    <h2>Saved Products</h2>

    <?php foreach ($products as $entry): ?>

        <div>

            <div class="product-actions">

                <h3>
                    <?= htmlspecialchars($entry['product_name']) ?>
                </h3>

                <a href="products.php?mode=edit&edit_id=<?= (int)$entry['product_id'] ?>">
                    Edit
                </a>

                <form
                    method="POST"
                    onsubmit="return confirm('Are you sure you want to delete this product?');"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="delete"
                    >

                    <input
                        type="hidden"
                        name="product_id"
                        value="<?= (int)$entry['product_id'] ?>"
                    >

                    <button type="submit">Delete</button>

                </form>

            </div>


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
