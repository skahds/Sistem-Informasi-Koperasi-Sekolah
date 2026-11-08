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

$error = '';
$formValues = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = filter_var($_POST['employee_id'] ?? 0, FILTER_VALIDATE_INT);

        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM employees WHERE employee_id = ?');
            $stmt->execute([$id]);
        }

        header('Location: index.php?page=employees');
        exit;
    }

    if ($action === 'create' || $action === 'update') {
        $employee_number = trim($_POST['employee_number'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $gender = $_POST['gender'] ?? '';
        $birth_date = $_POST['birth_date'] ?? '';
        $position = $_POST['position'] ?? '';
        $salary = trim($_POST['salary'] ?? '');

        $formValues = [
            'employee_number' => $employee_number,
            'full_name' => $full_name,
            'gender' => $gender,
            'birth_date' => $birth_date,
            'position' => $position,
            'salary' => $salary
        ];

        if ($action === 'update') {
            $mode = 'edit';
            $editId = filter_var($_POST['employee_id'] ?? 0, FILTER_VALIDATE_INT);
            $editId = $editId > 0 ? $editId : 0;
        }

        $allowedGenders = ['M', 'F'];
        $allowedPositions = ['Cashier', 'Warehouse Staff', 'Admin'];
        $salaryValue = filter_var($salary, FILTER_VALIDATE_FLOAT);
        $birthDateObject = DateTime::createFromFormat('!Y-m-d', $birth_date);
        $birthDateErrors = DateTime::getLastErrors();
        $validBirthDate = $birthDateObject
            && $birthDateObject->format('Y-m-d') === $birth_date
            && ($birthDateErrors === false || (
                $birthDateErrors['warning_count'] === 0
                && $birthDateErrors['error_count'] === 0
            ));

        if ($employee_number === '' || $full_name === '' || $birth_date === '' || $salary === '') {
            $error = 'Please fill in all required fields.';
        } elseif (strlen($employee_number) > 10) {
            $error = 'Employee number must not exceed 10 characters.';
        } elseif (strlen($full_name) > 100) {
            $error = 'Full name must not exceed 100 characters.';
        } elseif (!in_array($gender, $allowedGenders, true)) {
            $error = 'Invalid gender.';
        } elseif (!$validBirthDate) {
            $error = 'Invalid birth date.';
        } elseif (!in_array($position, $allowedPositions, true)) {
            $error = 'Invalid position.';
        } elseif ($salaryValue === false || $salaryValue < 0 || $salaryValue > 9999999999.99
            || !preg_match('/^\d+(\.\d{1,2})?$/', $salary)) {
            $error = 'Invalid salary. Enter a non-negative amount with up to two decimal places.';
        }

        if ($error === '') {
            if ($action === 'create') {
                $check = $pdo->prepare('SELECT employee_id FROM employees WHERE employee_number = ?');
                $check->execute([$employee_number]);
            } else {
                $check = $pdo->prepare(
                    'SELECT employee_id FROM employees WHERE employee_number = ? AND employee_id <> ?'
                );
                $check->execute([$employee_number, $editId]);
            }

            if ($check->fetchColumn()) {
                $error = 'This employee number is already in use.';
            }
        }

        if ($error === '') {
            if ($action === 'create') {
                $stmt = $pdo->prepare(
                    'INSERT INTO employees
                    (employee_number, full_name, gender, birth_date, position, salary)
                    VALUES (?, ?, ?, ?, ?, ?)'
                );

                $stmt->execute([
                    $employee_number,
                    $full_name,
                    $gender,
                    $birth_date,
                    $position,
                    $salary
                ]);
            } else {
                $check = $pdo->prepare('SELECT employee_id FROM employees WHERE employee_id = ?');
                $check->execute([$editId]);

                if (!$check->fetchColumn()) {
                    $error = 'Employee not found.';
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE employees
                        SET employee_number = ?, full_name = ?, gender = ?,
                            birth_date = ?, position = ?, salary = ?
                        WHERE employee_id = ?'
                    );

                    $stmt->execute([
                        $employee_number,
                        $full_name,
                        $gender,
                        $birth_date,
                        $position,
                        $salary,
                        $editId
                    ]);
                }
            }
        }

        if ($error === '') {
            header('Location: index.php?page=employees');
            exit;
        }
    } else {
        $error = 'Invalid action.';
    }
}

$editEmployee = null;

if ($mode === 'edit' && $editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM employees WHERE employee_id = ?');
    $stmt->execute([$editId]);
    $editEmployee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editEmployee && $error === '') {
        $error = 'Employee not found.';
    }
}

$stmt = $pdo->query('SELECT * FROM employees ORDER BY employee_id DESC');
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

$formData = [
    'employee_number' => '',
    'full_name' => '',
    'gender' => '',
    'birth_date' => '',
    'position' => '',
    'salary' => ''
];

if ($editEmployee) {
    $formData = array_merge($formData, $editEmployee);
}

if ($formValues !== null) {
    $formData = array_merge($formData, $formValues);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Employees</title>
    <style>
        * {
            font-family: Arial, Helvetica, sans-serif;
        }

        main {
            width: 35%;
            margin: 20px auto;
        }

        .employee-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
    </style>
</head>
<body>

<main>

    <h1>Employees</h1>

    <a href="index.php">Kembali ke halaman utama</a>

    <hr>

    <a href="index.php?page=employees&mode=create">Create</a>
    |
    <a href="index.php?page=employees&mode=edit">Edit</a>

    <hr>

    <?php if ($error !== ''): ?>
        <p style="color: red;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if ($mode === 'edit'): ?>

        <h2>Select Employee to Edit</h2>

        <form method="GET" action="index.php">
            <input type="hidden" name="page" value="employees">
            <input type="hidden" name="mode" value="edit">

            <select name="edit_id" required>
                <option value="">Choose an employee</option>

                <?php foreach ($employees as $entry): ?>
                    <option
                        value="<?= (int)$entry['employee_id'] ?>"
                        <?= $editId == $entry['employee_id'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars(
                            ($entry['employee_number'] ?? '') . ' - ' . ($entry['full_name'] ?? ''),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Load Employee</button>
        </form>

        <hr>

    <?php endif; ?>

    <?php if ($mode === 'create' || $editEmployee || ($mode === 'edit' && $formValues !== null)): ?>

        <h2>
            <?= $mode === 'create' ? 'Create Employee' : 'Edit Employee' ?>
        </h2>

        <form
            method="POST"
            action="<?= $mode === 'edit'
                ? 'index.php?page=employees&mode=edit&edit_id=' . (int)$editId
                : 'index.php?page=employees' ?>"
        >
            <input
                type="hidden"
                name="action"
                value="<?= $mode === 'create' ? 'create' : 'update' ?>"
            >

            <?php if ($mode === 'edit'): ?>
                <input
                    type="hidden"
                    name="employee_id"
                    value="<?= (int)$editId ?>"
                >
            <?php endif; ?>

            <label>Employee Number</label>
            <input
                type="text"
                name="employee_number"
                maxlength="10"
                value="<?= htmlspecialchars((string)$formData['employee_number'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <br><br>

            <label>Full Name</label>
            <input
                type="text"
                name="full_name"
                maxlength="100"
                value="<?= htmlspecialchars((string)$formData['full_name'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <br><br>

            <label>Gender</label>
            <select name="gender" required>
                <option value="">Choose gender</option>
                <option value="M" <?= $formData['gender'] === 'M' ? 'selected' : '' ?>>Male</option>
                <option value="F" <?= $formData['gender'] === 'F' ? 'selected' : '' ?>>Female</option>
            </select>

            <br><br>

            <label>Birth Date</label>
            <input
                type="date"
                name="birth_date"
                value="<?= htmlspecialchars((string)$formData['birth_date'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <br><br>

            <label>Position</label>
            <select name="position" required>
                <option value="">Choose position</option>
                <option value="Cashier" <?= $formData['position'] === 'Cashier' ? 'selected' : '' ?>>Cashier</option>
                <option value="Warehouse Staff" <?= $formData['position'] === 'Warehouse Staff' ? 'selected' : '' ?>>Warehouse Staff</option>
                <option value="Admin" <?= $formData['position'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
            </select>

            <br><br>

            <label>Salary</label>
            <input
                type="number"
                name="salary"
                min="0"
                max="9999999999.99"
                step="0.01"
                value="<?= htmlspecialchars((string)$formData['salary'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <br><br>

            <button type="submit">
                <?= $mode === 'create' ? 'Save Employee' : 'Update Employee' ?>
            </button>

        </form>

    <?php elseif ($mode === 'edit'): ?>

        <p>Select an employee above to edit it.</p>

    <?php endif; ?>

    <h2>Saved Employees</h2>

    <?php foreach ($employees as $entry): ?>

        <div>
            <div class="employee-actions">
                <h3>
                    <?= htmlspecialchars((string)$entry['full_name'], ENT_QUOTES, 'UTF-8') ?>
                </h3>

                <a href="index.php?page=employees&mode=edit&edit_id=<?= (int)$entry['employee_id'] ?>">
                    Edit
                </a>

                <form
                    method="POST"
                    action="index.php?page=employees"
                    onsubmit="return confirm('Are you sure you want to delete this employee?');"
                >
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="employee_id" value="<?= (int)$entry['employee_id'] ?>">
                    <button type="submit">Delete</button>
                </form>
            </div>

            <p>Employee Number: <?= htmlspecialchars((string)($entry['employee_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <p>Gender: <?= htmlspecialchars((string)($entry['gender'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <p>Birth Date: <?= htmlspecialchars((string)($entry['birth_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <p>Position: <?= htmlspecialchars((string)($entry['position'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <p>Salary: Rp <?= number_format((float)$entry['salary'], 2, ',', '.') ?></p>

            <hr>
        </div>

    <?php endforeach; ?>

</main>

</body>
</html>
