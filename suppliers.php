<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('Direct access not allowed.');
}

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
        $id = filter_var($_POST['supplier_id'] ?? 0, FILTER_VALIDATE_INT);

        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM suppliers WHERE supplier_id = ?");
            $stmt->execute([$id]);
        }

        header("Location: index.php?page=suppliers");
        exit;
    }

    if ($action === "create" || $action === "update") {
        $supplier_name = trim($_POST["supplier_name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $address = trim($_POST["address"] ?? "");
        $product_types = trim($_POST["product_types"] ?? "");
        $partnership_date = $_POST["partnership_date"] ?? "";

        $formValues = [
            "supplier_name" => $supplier_name,
            "email" => $email,
            "phone" => $phone,
            "address" => $address,
            "product_types" => $product_types,
            "partnership_date" => $partnership_date
        ];

        if ($action === "update") {
            $mode = "edit";
            $editId = filter_var($_POST['supplier_id'] ?? 0, FILTER_VALIDATE_INT);
            $editId = $editId > 0 ? $editId : 0;
        }

        if ($supplier_name === "") {
            $error = "Please enter the supplier name.";
        } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address.";
        } elseif ($partnership_date !== "" && !DateTime::createFromFormat('Y-m-d', $partnership_date)) {
            $error = "Invalid partnership date.";
        }

        if ($error === "") {
            if ($action === "create") {
                $stmt = $pdo->prepare("
                    INSERT INTO suppliers
                    (supplier_name, email, phone, address, product_types, partnership_date)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $supplier_name,
                    $email ?: null,
                    $phone ?: null,
                    $address ?: null,
                    $product_types ?: null,
                    $partnership_date ?: null
                ]);
            } else {
                $check = $pdo->prepare("
                    SELECT supplier_id FROM suppliers
                    WHERE supplier_id = ?
                ");

                $check->execute([$editId]);

                if (!$check->fetchColumn()) {
                    $error = "Supplier not found.";
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE suppliers
                        SET supplier_name = ?, email = ?, phone = ?,
                            address = ?, product_types = ?, partnership_date = ?
                        WHERE supplier_id = ?
                    ");

                    $stmt->execute([
                        $supplier_name,
                        $email ?: null,
                        $phone ?: null,
                        $address ?: null,
                        $product_types ?: null,
                        $partnership_date ?: null,
                        $editId
                    ]);
                }
            }
        }

        if ($error === "") {
            header("Location: index.php?page=suppliers");
            exit;
        }
    } else {
        $error = "Invalid action.";
    }
}

$editSupplier = null;

if ($mode === "edit" && $editId > 0) {
    $stmt = $pdo->prepare("
        SELECT * FROM suppliers
        WHERE supplier_id = ?
    ");

    $stmt->execute([$editId]);
    $editSupplier = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editSupplier && $error === "") {
        $error = "Supplier not found.";
    }
}

$stmt = $pdo->query("
    SELECT * FROM suppliers
    ORDER BY supplier_id DESC
");

$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$formData = [
    "supplier_name" => "",
    "email" => "",
    "phone" => "",
    "address" => "",
    "product_types" => "",
    "partnership_date" => ""
];

if ($editSupplier) {
    $formData = array_merge($formData, $editSupplier);
}

if ($formValues !== null) {
    $formData = array_merge($formData, $formValues);
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Suppliers</title>
    <style>
        * {
            font-family: Arial, Helvetica, sans-serif;
        }

        main {
            width: 35%;
            margin: 20px auto;
        }

        .supplier-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
    </style>
</head>
<body>

<main>

    <h1>Suppliers</h1>

    <a href="index.php">Kembali ke halaman utama</a>

    <hr>

    <a href="index.php?page=suppliers&mode=create">Create</a>
    |
    <a href="index.php?page=suppliers&mode=edit">Edit</a>

    <hr>

    <?php if ($error !== ""): ?>
        <p style="color: red;">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <?php if ($mode === "edit"): ?>

        <h2>Select Supplier to Edit</h2>

        <form method="GET" action="index.php">
            <input type="hidden" name="page" value="suppliers">
            <input type="hidden" name="mode" value="edit">

            <select name="edit_id" required>
                <option value="">Choose a supplier</option>

                <?php foreach ($suppliers as $entry): ?>
                    <option
                        value="<?= (int)$entry['supplier_id'] ?>"
                        <?= $editId == $entry['supplier_id'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($entry['supplier_name'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Load Supplier</button>
        </form>

        <hr>

    <?php endif; ?>

    <?php if ($mode === "create" || $editSupplier || ($mode === "edit" && $formValues !== null)): ?>

        <h2>
            <?= $mode === "create" ? "Create Supplier" : "Edit Supplier" ?>
        </h2>

        <form
            method="POST"
            action="<?= $mode === 'edit'
                ? 'index.php?page=suppliers&mode=edit&edit_id=' . (int)$editId
                : 'index.php?page=suppliers' ?>"
        >

            <input
                type="hidden"
                name="action"
                value="<?= $mode === "create" ? "create" : "update" ?>"
            >

            <?php if ($mode === "edit"): ?>
                <input
                    type="hidden"
                    name="supplier_id"
                    value="<?= (int)$editId ?>"
                >
            <?php endif; ?>

            <label>Supplier Name</label>
            <input
                type="text"
                name="supplier_name"
                maxlength="100"
                value="<?= htmlspecialchars($formData['supplier_name'] ?? '') ?>"
                required
            >

            <br><br>

            <label>Email</label>
            <input
                type="email"
                name="email"
                maxlength="100"
                value="<?= htmlspecialchars($formData['email'] ?? '') ?>"
            >

            <br><br>

            <label>Phone</label>
            <input
                type="text"
                name="phone"
                maxlength="15"
                value="<?= htmlspecialchars($formData['phone'] ?? '') ?>"
            >

            <br><br>

            <label>Address</label>
            <textarea name="address"><?= htmlspecialchars($formData['address'] ?? '') ?></textarea>

            <br><br>

            <label>Product Types</label>
            <input
                type="text"
                name="product_types"
                maxlength="100"
                value="<?= htmlspecialchars($formData['product_types'] ?? '') ?>"
            >

            <br><br>

            <label>Partnership Date</label>
            <input
                type="date"
                name="partnership_date"
                value="<?= htmlspecialchars($formData['partnership_date'] ?? '') ?>"
            >

            <br><br>

            <button type="submit">
                <?= $mode === "create" ? "Save Supplier" : "Update Supplier" ?>
            </button>

        </form>

    <?php elseif ($mode === "edit"): ?>

        <p>Select a supplier above to edit it.</p>

    <?php endif; ?>

    <h2>Saved Suppliers</h2>

    <?php foreach ($suppliers as $entry): ?>

        <div>
            <div class="supplier-actions">
                <h3>
                    <?= htmlspecialchars($entry['supplier_name'] ?? '') ?>
                </h3>

                <a href="index.php?page=suppliers&mode=edit&edit_id=<?= (int)$entry['supplier_id'] ?>">
                    Edit
                </a>

                <form
                    method="POST"
                    action="index.php?page=suppliers"
                    onsubmit="return confirm('Are you sure you want to delete this supplier?');"
                >
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="supplier_id" value="<?= (int)$entry['supplier_id'] ?>">
                    <button type="submit">Delete</button>
                </form>
            </div>

            <p>Email: <?= htmlspecialchars($entry['email'] ?? '') ?></p>
            <p>Phone: <?= htmlspecialchars($entry['phone'] ?? '') ?></p>
            <p>Address: <?= nl2br(htmlspecialchars($entry['address'] ?? '')) ?></p>
            <p>Product Types: <?= htmlspecialchars($entry['product_types'] ?? '') ?></p>
            <p>Partnership Date: <?= htmlspecialchars($entry['partnership_date'] ?? '') ?></p>

            <hr>
        </div>

    <?php endforeach; ?>

</main>

</body>
</html>
