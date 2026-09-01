<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Add Employee';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'AddEmployee') {
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $joining = trim($_POST['joining_date'] ?? '');
    $salary = (float) ($_POST['salary'] ?? 0);
    $address = trim($_POST['address'] ?? '');

    if ($first === '') {
        $error = 'Employee name is required.';
    } else {
        $dob_db = $dob ?: null;
        $join_db = $joining ?: null;
        $photo = null;
        $st2 = db_prepare("INSERT INTO employees (first_name, last_name, email, phone, designation, department, dob, joining_date, salary, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $st2->bind_param('ssssssssds', $first, $last, $email, $phone, $designation, $department, $dob_db, $join_db, $salary, $address);
        $st2->execute();
        $message = 'Employee added successfully!';
    }
}

include __DIR__ . '/includes/header.php';
?>
<style>
.search-bar-student { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-user-plus"></i> Add Employee</h3>
            <a href="<?php echo BASE_URL; ?>view_emp.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-users"></i> View Employees</a>
        </div>

        <form method="post" action="add_emp.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:20px;" enctype="multipart/form-data">
            <input type="hidden" name="action" value="AddEmployee">
            <div class="row">
                <div class="form-group col-md-4">
                    <label class="required">First Name</label>
                    <input type="text" name="first_name" class="form-control" required>
                </div>
                <div class="form-group col-md-4">
                    <label>Last Name</label>
                    <input type="text" name="last_name" class="form-control">
                </div>
                <div class="form-group col-md-4">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="form-group col-md-4">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" placeholder="03XX-XXXXXXX">
                </div>
                <div class="form-group col-md-4">
                    <label>Designation</label>
                    <input type="text" name="designation" class="form-control" placeholder="e.g. Teacher">
                </div>
                <div class="form-group col-md-4">
                    <label>Department</label>
                    <input type="text" name="department" class="form-control" placeholder="e.g. Academics">
                </div>
                <div class="form-group col-md-4">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" class="form-control">
                </div>
                <div class="form-group col-md-4">
                    <label>Joining Date</label>
                    <input type="date" name="joining_date" class="form-control">
                </div>
                <div class="form-group col-md-4">
                    <label>Salary</label>
                    <input type="number" step="0.01" name="salary" class="form-control" value="0">
                </div>
                <div class="form-group col-md-12">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-success" style="padding:10px 30px;"><i class="fa fa-save"></i> Save Employee</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>