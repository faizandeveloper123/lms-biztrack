<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'View Employees';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'DeleteEmployee') {
    $eid = (int) ($_POST['emp_id'] ?? 0);
    if ($eid > 0) {
        $st2 = db_prepare("DELETE FROM employees WHERE emp_id=?");
        $st2->bind_param('i', $eid);
        $st2->execute();
        $message = 'Employee deleted successfully!';
    }
}

$q = trim($_GET['q'] ?? '');
$emps = [];
$sql = "SELECT * FROM employees";
if ($q !== '') { $sql .= " WHERE first_name LIKE '%$q%' OR last_name LIKE '%$q%' OR designation LIKE '%$q%' OR department LIKE '%$q%'"; }
$sql .= " ORDER BY first_name";
$res = db_query($sql);
while ($row = $res->fetch_assoc()) { $emps[] = $row; }

include __DIR__ . '/includes/header.php';
?>
<style>
.search-bar-student { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px; }
.avatar { width:36px; height:36px; border-radius:999px; background:linear-gradient(135deg,#FF7A1B,#ffa35c); color:#fff; display:inline-flex; align-items:center; justify-content:center; font-weight:800; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-users"></i> View Employees <span style="font-size:14px; color:#6B7280;">(<?php echo count($emps); ?> employees)</span></h3>
            <a href="<?php echo BASE_URL; ?>add_emp.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-user-plus"></i> Add Employee</a>
        </div>

        <form method="get" action="view_emp.php" class="search-bar-student">
            <div class="form-group col-md-4" style="margin-bottom:0;">
                <input type="text" name="q" class="form-control" placeholder="Search by name, designation, department..." value="<?php echo e($q); ?>">
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-search"></i> Search</button>
            </div>
        </form>

        <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
            <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                <thead>
                    <tr><th>#</th><th>Employee</th><th>Designation</th><th>Department</th><th>Phone</th><th>Salary</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    <?php if (count($emps) === 0): ?>
                        <tr><td colspan="8" style="text-align:center; color:#6B7280; padding:30px;">No employees found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($emps as $em): ?>
                        <tr>
                            <td><?php echo $em['emp_id']; ?></td>
                            <td>
                                <span class="avatar" style="margin-right:8px;"><?php echo strtoupper(substr($em['first_name'], 0, 1)); ?></span>
                                <strong><?php echo e($em['first_name']); ?> <?php echo e($em['last_name']); ?></strong>
                            </td>
                            <td><?php echo e($em['designation'] ?? '-'); ?></td>
                            <td><?php echo e($em['department'] ?? '-'); ?></td>
                            <td><?php echo e($em['phone'] ?? '-'); ?></td>
                            <td><?php echo $em['salary'] > 0 ? number_format($em['salary'], 2) : '-'; ?></td>
                            <td><span class="status-badge status-<?php echo $em['status'] ? 'paid' : 'unpaid'; ?>"><?php echo $em['status'] ? 'Active' : 'Inactive'; ?></span></td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>payroll.php?emp_id=<?php echo $em['emp_id']; ?>" class="btn btn-info btn-xs" title="Payroll"><i class="fa fa-money"></i></a>
                                <form method="post" action="view_emp.php" style="display:inline;" onsubmit="return confirm('Delete this employee?');">
                                    <input type="hidden" name="action" value="DeleteEmployee">
                                    <input type="hidden" name="emp_id" value="<?php echo $em['emp_id']; ?>">
                                    <button class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></button>
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