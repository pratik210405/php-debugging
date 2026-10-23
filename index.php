<?php
declare(strict_types=1);
session_start();

/*
 * Week 3 Internship Project:
 * Employee Management System (PHP + MySQL)
 * Features: CRUD, search, validation, prepared statements, CSRF protection.
 */

$dbHost = 'localhost';
$dbName = 'week3_employee_db';
$dbUser = 'root';
$dbPass = ''; // Change if your local MySQL has a password.

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit(
        '<h2>Database connection failed</h2>' .
        '<p>Import database.sql in phpMyAdmin and check the database settings at the top of index.php.</p>'
    );
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_check(): void {
    $sent = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';
    if (!is_string($sent) || !is_string($stored) || !hash_equals($stored, $sent)) {
        http_response_code(403);
        exit('Security check failed. Refresh the page and try again.');
    }
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $department = trim((string)($_POST['department'] ?? ''));

        if ($name === '' || mb_strlen($name) > 100) {
            $error = 'Enter a name (maximum 100 characters).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            $error = 'Enter a valid email address (maximum 150 characters).';
        } elseif ($department === '' || mb_strlen($department) > 100) {
            $error = 'Enter a department (maximum 100 characters).';
        } else {
            try {
                if ($id && $id > 0) {
                    $stmt = $pdo->prepare(
                        'UPDATE employees SET name = :name, email = :email, department = :department WHERE id = :id'
                    );
                    $stmt->execute([
                        ':name' => $name,
                        ':email' => $email,
                        ':department' => $department,
                        ':id' => $id,
                    ]);
                    $_SESSION['flash'] = 'Employee updated successfully.';
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO employees (name, email, department) VALUES (:name, :email, :department)'
                    );
                    $stmt->execute([
                        ':name' => $name,
                        ':email' => $email,
                        ':department' => $department,
                    ]);
                    $_SESSION['flash'] = 'Employee added successfully.';
                }
                header('Location: index.php');
                exit;
            } catch (PDOException $e) {
                $error = $e->getCode() === '23000'
                    ? 'This email address already exists. Use another email.'
                    : 'Could not save the record. Please check your database setup.';
            }
        }
    } elseif ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id && $id > 0) {
            $stmt = $pdo->prepare('DELETE FROM employees WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $_SESSION['flash'] = 'Employee deleted successfully.';
            header('Location: index.php');
            exit;
        }
        $error = 'Invalid employee ID.';
    }
}

$editEmployee = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId && $editId > 0) {
    $stmt = $pdo->prepare('SELECT id, name, email, department FROM employees WHERE id = :id');
    $stmt->execute([':id' => $editId]);
    $editEmployee = $stmt->fetch() ?: null;
}

$search = trim((string)($_GET['search'] ?? ''));
if ($search !== '') {
    $stmt = $pdo->prepare(
        'SELECT id, name, email, department, created_at
         FROM employees
         WHERE name LIKE :name OR email LIKE :email OR department LIKE :department
         ORDER BY id DESC'
    );
    $term = '%' . $search . '%';
    $stmt->execute([':name' => $term, ':email' => $term, ':department' => $term]);
    $employees = $stmt->fetchAll();
} else {
    $employees = $pdo->query(
        'SELECT id, name, email, department, created_at FROM employees ORDER BY id DESC'
    )->fetchAll();
}
$totalEmployees = (int)$pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EmployeeHub | Management System</title>
    <style>
        :root{--navy:#14213d;--blue:#3157d5;--bg:#f4f7fb;--text:#1d2939;--muted:#667085;--border:#e4e7ec;--green:#067647;--red:#b42318}
        *{box-sizing:border-box}
        body{margin:0;background:var(--bg);font-family:Inter,Arial,sans-serif;color:var(--text)}
        .topbar{background:linear-gradient(120deg,#14213d,#3157d5);color:#fff;padding:26px 18px}
        .top-inner{max-width:1120px;margin:auto;display:flex;justify-content:space-between;gap:15px;align-items:center;flex-wrap:wrap}
        .brand{font-size:25px;font-weight:800;letter-spacing:.2px}.brand span{color:#a9c1ff}
        .topbar p{margin:6px 0 0;color:#dbe5ff;font-size:14px}
        .badge{background:#ffffff1c;border:1px solid #ffffff40;border-radius:999px;padding:9px 13px;font-size:13px}
        main{max-width:1120px;margin:28px auto;padding:0 16px}
        .stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px;margin-bottom:20px}
        .stat,.panel{background:#fff;border:1px solid var(--border);border-radius:15px;box-shadow:0 5px 18px #10182808}
        .stat{padding:18px 20px}.stat .label{font-size:13px;color:var(--muted)}.stat .number{font-size:28px;font-weight:800;margin-top:8px;color:var(--navy)}
        .panel{padding:22px;margin-bottom:20px}.panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:17px}
        h2{font-size:18px;margin:0}.sub{color:var(--muted);font-size:13px;margin:6px 0 0}
        .form-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
        label{display:block;font-size:13px;font-weight:700;margin-bottom:7px}
        input{width:100%;border:1px solid #d0d5dd;border-radius:9px;padding:12px 13px;font-size:14px;background:#fff;color:var(--text);outline:none}
        input:focus{border-color:var(--blue);box-shadow:0 0 0 3px #3157d51a}
        .form-actions{display:flex;gap:9px;align-items:center;margin-top:16px;flex-wrap:wrap}
        button,.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;border:0;border-radius:9px;padding:11px 14px;background:var(--blue);color:#fff;font-weight:700;font-size:13px;text-decoration:none;cursor:pointer}
        .btn-light{background:#eef2ff;color:#2b49ae}.btn-danger{background:#fff0ee;color:var(--red)}.btn-small{padding:8px 10px;font-size:12px}
        .flash,.error{padding:12px 14px;border-radius:10px;margin-bottom:16px;font-size:14px}
        .flash{background:#ecfdf3;color:var(--green);border:1px solid #abefc6}.error{background:#fef3f2;color:var(--red);border:1px solid #fecdca}
        .tools{display:flex;gap:8px;flex-wrap:wrap}.tools input{min-width:230px}
        .table-wrap{overflow-x:auto;border:1px solid var(--border);border-radius:10px}
        table{width:100%;border-collapse:collapse;min-width:700px}
        th,td{text-align:left;padding:13px 14px;border-bottom:1px solid var(--border);font-size:13px}
        th{background:#f9fafb;color:#475467;font-size:12px;text-transform:uppercase;letter-spacing:.4px}
        tr:last-child td{border-bottom:0}tbody tr:hover{background:#fafbff}
        .person{font-weight:700}.muted{color:var(--muted);font-size:12px;margin-top:3px}.actions{display:flex;gap:7px;align-items:center}.actions form{margin:0}
        .empty{text-align:center;padding:30px;color:var(--muted)}footer{text-align:center;color:var(--muted);font-size:12px;padding:6px 0 28px}
        @media(max-width:760px){.stats{grid-template-columns:1fr}.form-grid{grid-template-columns:1fr}.panel{padding:16px}.topbar{padding:22px 16px}.brand{font-size:22px}.tools{width:100%}.tools input{min-width:0;flex:1}}
    </style>
</head>
<body>
<div class="topbar">
    <div class="top-inner">
        <div>
            <div class="brand">Employee<span>Hub</span></div>
            <p>Simple, secure employee record management</p>
        </div>
        <div class="badge">Week 3 Internship Project · PHP + MySQL</div>
    </div>
</div>

<main>
    <section class="stats">
        <div class="stat"><div class="label">TOTAL EMPLOYEES</div><div class="number"><?= h((string)$totalEmployees) ?></div></div>
        <div class="stat"><div class="label">APPLICATION</div><div class="number">PHP / MySQL</div></div>
        <div class="stat"><div class="label">SECURITY</div><div class="number">Enabled ✓</div></div>
    </section>

    <?php if ($flash !== ''): ?><div class="flash"><?= h((string)$flash) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="error"><?= h($error) ?></div><?php endif; ?>

    <section class="panel">
        <div class="panel-head">
            <div>
                <h2><?= $editEmployee ? 'Update Employee' : 'Add New Employee' ?></h2>
                <p class="sub"><?= $editEmployee ? 'Change the details and save your updates.' : 'Enter employee details to create a record.' ?></p>
            </div>
        </div>
        <form method="post" action="index.php">
            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= h((string)($editEmployee['id'] ?? '')) ?>">
            <div class="form-grid">
                <div><label for="name">FULL NAME</label><input id="name" name="name" maxlength="100" required placeholder="e.g. Rahul Patil" value="<?= h((string)($editEmployee['name'] ?? '')) ?>"></div>
                <div><label for="email">EMAIL ADDRESS</label><input id="email" type="email" name="email" maxlength="150" required placeholder="name@example.com" value="<?= h((string)($editEmployee['email'] ?? '')) ?>"></div>
                <div><label for="department">DEPARTMENT</label><input id="department" name="department" maxlength="100" required placeholder="e.g. Information Technology" value="<?= h((string)($editEmployee['department'] ?? '')) ?>"></div>
            </div>
            <div class="form-actions">
                <button type="submit"><?= $editEmployee ? 'Save Changes' : '+ Add Employee' ?></button>
                <?php if ($editEmployee): ?><a class="btn btn-light" href="index.php">Cancel Edit</a><?php endif; ?>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="panel-head">
            <div><h2>Employee Directory</h2><p class="sub">View, search, edit, or delete employee records.</p></div>
            <form class="tools" method="get" action="index.php">
                <input name="search" aria-label="Search employees" placeholder="Search name, email, department..." value="<?= h($search) ?>">
                <button type="submit">Search</button>
                <?php if ($search !== ''): ?><a class="btn btn-light" href="index.php">Clear</a><?php endif; ?>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Employee</th><th>Email</th><th>Department</th><th>Created</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (!$employees): ?>
                    <tr><td colspan="5" class="empty">No employees found. Add a record above to get started.</td></tr>
                <?php else: foreach ($employees as $employee): ?>
                    <tr>
                        <td><div class="person"><?= h($employee['name']) ?></div><div class="muted">ID #<?= h((string)$employee['id']) ?></div></td>
                        <td><?= h($employee['email']) ?></td>
                        <td><?= h($employee['department']) ?></td>
                        <td><?= h(date('d M Y', strtotime($employee['created_at']))) ?></td>
                        <td><div class="actions">
                            <a class="btn btn-light btn-small" href="?edit=<?= h((string)$employee['id']) ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Are you sure you want to delete this employee?');">
                                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= h((string)$employee['id']) ?>">
                                <button class="btn-danger btn-small" type="submit">Delete</button>
                            </form>
                        </div></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <footer>EmployeeHub · Built for the Week 3 PHP debugging and enhancement task</footer>
</main>
</body>
</html>
