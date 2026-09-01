<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'View Employees';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'Deactivate') {
    $emp_id = (int) ($_POST['emp_id'] ?? 0);
    if ($emp_id > 0) {
        $st2 = db_prepare("UPDATE employees SET status=0 WHERE emp_id=?");
        $st2->bind_param('i', $emp_id);
        $st2->execute();
        $message = 'Employee deactivated (moved to old employees).';
    }
}

$search = trim($_GET['search'] ?? '');

$sql = "SELECT e.*, (SELECT COUNT(*) FROM employee_attendance a WHERE a.emp_id=e.emp_id AND a.status='present') present_days,
        (SELECT COUNT(*) FROM payroll p WHERE p.emp_id=e.emp_id) payslips
        FROM employees e WHERE e.status=1";
$params = [];
$types = '';
if ($search !== '') { $sql .= " AND (e.first_name LIKE ? OR e.email LIKE ? OR e.designation LIKE ? OR e.department LIKE ?)"; $params = ["%$search%", "%$search%", "%$search%", "%$search%"]; $types = 'ssss'; }
$sql .= " ORDER BY e.first_name";

$employees = [];
if (count($params) > 0) { $st2 = db_prepare($sql); $st2->bind_param($types, ...$params); $st2->execute(); $res = $st2->get_result(); } else { $res = db_query($sql); }
while ($row = $res->fetch_assoc()) { $employees[] = $row; }

include __DIR__ . '/includes/header.php';
?>
<style>
.search-bar-student { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-users"></i> List View Employee</h3>
            <a href="<?php echo BASE_URL; ?>add_emp.php" class="btn btn-success" style="color:#fff;"><i class="fa fa-plus"></i> Add Employee</a>
        </div>

        <form method="get" action="staff_security.php" class="search-bar-student">
            <div class="form-group col-md-4" style="margin-bottom:0;">
                <label>Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name / email / designation / department" value="<?php echo e($search); ?>">
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-search"></i> Search</button>
            </div>
        </form>

        <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
            <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                <thead><tr><th>#</th><th>Employee</th><th>Designation</th><th>Department</th><th>Phone</th><th>Salary</th><th>Present Days</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <?php if (count($employees) === 0): ?><tr><td colspan="9" style="text-align:center; color:#6B7280; padding:25px;">Koi employee nahi mila.</td></tr><?php endif; ?>
                    <?php foreach ($employees as $e): ?>
                        <tr>
                            <td><?php echo $e['emp_id']; ?></td>
                            <td><strong><?php echo e($e['first_name'] . ' ' . $e['last_name']); ?></strong><br><small style="color:#6B7280;"><?php echo e($e['email'] ?? ''); ?></small></td>
                            <td><?php echo e($e['designation'] ?? '-'); ?></td>
                            <td><?php echo e($e['department'] ?? '-'); ?></td>
                            <td><?php echo e($e['phone'] ?? '-'); ?></td>
                            <td><?php echo number_format($e['salary'] ?? 0, 2); ?></td>
                            <td><span class="status-badge status-present"><?php echo $e['present_days']; ?> days</span></td>
                            <td><span class="status-badge status-present">Active</span></td>
                            <td>
                                <form method="post" action="staff_security.php" style="display:inline;" onsubmit="return confirm('Is employee ko deactivate karna hai?');">
                                    <input type="hidden" name="action" value="Deactivate">
                                    <input type="hidden" name="emp_id" value="<?php echo $e['emp_id']; ?>">
                                    <button class="btn btn-danger btn-xs"><i class="fa fa-user-times"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>