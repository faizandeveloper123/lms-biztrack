<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Creat Employees PayRoll';

$message = '';
$error = '';

$month = $_GET['month'] ?? '';
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int) date('Y');
if ($month === '') { $month = date('n'); }

$allowancePct = (float) get_setting('payroll_allowance_percent', '0');
$deductionPct = (float) get_setting('payroll_deduction_percent', '0');

function db_escape_name($v) { return str_replace("'", "''", (string)$v); }

$employees = [];
$res = db_query("SELECT * FROM employees WHERE status=1 ORDER BY first_name");
while ($row = $res->fetch_assoc()) {
    $row['paid_this'] = db_query("SELECT COUNT(*) c FROM payroll WHERE emp_id={$row['emp_id']} AND month='" . db_escape_name($month) . "' AND year=$year")->fetch_assoc()['c'] ?? 0;
    $employees[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'GeneratePayroll') {
    $year = (int) ($_POST['year'] ?? 0);
    $month = trim($_POST['month'] ?? '');
    $sel = $_POST['emp_ids'] ?? [];
    $created = 0;
    if ($year > 0 && $month !== '' && is_array($sel)) {
        foreach ($sel as $emp_id) {
            $emp_id = (int)$emp_id;
            $emp = db_query("SELECT * FROM employees WHERE emp_id=$emp_id")->fetch_assoc();
            if (!$emp) continue;
            $check = db_query("SELECT COUNT(*) c FROM payroll WHERE emp_id=$emp_id AND month='$month' AND year=$year")->fetch_assoc()['c'] ?? 0;
            if ($check > 0) continue;
            $basic = (float)($emp['salary'] ?? 0);
            $allowances = round($basic * ($allowancePct / 100), 2);
            $deductions = round($basic * ($deductionPct / 100), 2);
            $net = $basic + $allowances - $deductions;
            $st2 = db_prepare("INSERT INTO payroll (emp_id, month, year, basic_salary, allowances, deductions, net_salary, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $st2->bind_param('isiddid', $emp_id, $month, $year, $basic, $allowances, $deductions, $net);
            $st2->execute();
            $created++;
        }
        $message = "Payroll generated for $created employee(s) ($month $year)!";
    } else {
        $error = 'Month, year aur kam se kam ek employee select karein.';
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
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-money"></i> Create Employees PayRoll</h3>
            <div>
                <a href="<?php echo BASE_URL; ?>view_payroll.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-file-text"></i> View PayRoll</a>
                <a href="<?php echo BASE_URL; ?>payroll_setting.php" class="btn btn-warning" style="color:#fff;"><i class="fa fa-cog"></i> Settings</a>
            </div>
        </div>

        <form method="get" action="creat_payroll.php" class="search-bar-student">
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <label>Month</label>
                <input type="number" name="month" class="form-control" min="1" max="12" value="<?php echo e($month); ?>">
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <label>Year</label>
                <input type="number" name="year" class="form-control" value="<?php echo $year; ?>">
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-refresh"></i> Load</button>
            </div>
        </form>

        <form method="post" action="creat_payroll.php" style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
            <input type="hidden" name="action" value="GeneratePayroll">
            <input type="hidden" name="month" value="<?php echo e($month); ?>">
            <input type="hidden" name="year" value="<?php echo $year; ?>">
            <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                <thead><tr><th style="width:30px;">Sel</th><th>Employee</th><th>Designation</th><th>Basic Salary</th><th>Allowances (<small><?php echo $allowancePct; ?>%</small>)</th><th>Deductions (<small><?php echo $deductionPct; ?>%</small>)</th><th>Net</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if (count($employees) === 0): ?><tr><td colspan="8" style="text-align:center; color:#6B7280; padding:25px;">Koi active employee nahi.</td></tr><?php endif; ?>
                    <?php foreach ($employees as $e): $basic = (float)$e['salary']; $al = round($basic * ($allowancePct / 100), 2); $dd = round($basic * ($deductionPct / 100), 2); $net = $basic + $al - $dd; ?>
                        <tr>
                            <td><input type="checkbox" name="emp_ids[]" value="<?php echo $e['emp_id']; ?>" <?php echo $e['paid_this'] > 0 ? 'disabled' : ''; ?>></td>
                            <td><strong><?php echo e($e['first_name'] . ' ' . $e['last_name']); ?></strong><?php if ($e['paid_this'] > 0): ?><span class="status-badge status-present" style="margin-left:8px;">Already</span><?php endif; ?></td>
                            <td><?php echo e($e['designation'] ?? '-'); ?></td>
                            <td><?php echo number_format($basic, 2); ?></td>
                            <td style="color:#16A34A;"><?php echo number_format($al, 2); ?></td>
                            <td style="color:#DC2626;"><?php echo number_format($dd, 2); ?></td>
                            <td><strong><?php echo number_format($net, 2); ?></strong></td>
                            <td><span class="status-badge status-pending">Pending</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="padding:14px; text-align:right;">
                <button type="submit" class="btn btn-success"><i class="fa fa-plus"></i> Generate Selected Payroll</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>