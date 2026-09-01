<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'View Employees PayRoll';

$message = '';
$error = '';

$month = $_GET['month'] ?? '';
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int) date('Y');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'MarkPaid') {
    $pid = (int) ($_POST['payroll_id'] ?? 0);
    if ($pid > 0) {
        $st2 = db_prepare("UPDATE payroll SET status='paid' WHERE payroll_id=?");
        $st2->bind_param('i', $pid);
        $st2->execute();
        $message = 'Payroll marked as PAID!';
    }
}

$where = [];
$params = [];
$types = '';
if ($month !== '') { $where[] = "p.month = ?"; $params[] = $month; $types .= 's'; }
if ($year > 0) { $where[] = "p.year = ?"; $params[] = $year; $types .= 'i'; }

$sql = "SELECT p.*, e.first_name, e.last_name, e.designation FROM payroll p LEFT JOIN employees e ON p.emp_id=e.emp_id";
if (count($where) > 0) { $sql .= " WHERE " . implode(' AND ', $where); }
$sql .= " ORDER BY p.year DESC, p.month, p.payroll_id DESC";

$rows = [];
if (count($params) > 0) { $st2 = db_prepare($sql); $st2->bind_param($types, ...$params); $st2->execute(); $res = $st2->get_result(); } else { $res = db_query($sql); }
while ($row = $res->fetch_assoc()) { $rows[] = $row; }

$grand = 0.0; $paid = 0.0; $pending = 0.0;
foreach ($rows as $r) { $grand += (float)$r['net_salary']; if ($r['status'] == 'paid') { $paid += (float)$r['net_salary']; } else { $pending += (float)$r['net_salary']; } }

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
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-file-text"></i> View Employees PayRoll</h3>
            <div>
                <a href="<?php echo BASE_URL; ?>creat_payroll.php" class="btn btn-success" style="color:#fff;"><i class="fa fa-plus"></i> Generate PayRoll</a>
                <a href="<?php echo BASE_URL; ?>payroll_setting.php" class="btn btn-warning" style="color:#fff;"><i class="fa fa-cog"></i> Settings</a>
            </div>
        </div>

        <form method="get" action="view_payroll.php" class="search-bar-student">
            <div class="form-group col-md-2" style="margin-bottom:0;"><label>Month</label><input type="text" name="month" class="form-control" placeholder="e.g. 1" value="<?php echo e($month); ?>"></div>
            <div class="form-group col-md-2" style="margin-bottom:0;"><label>Year</label><input type="number" name="year" class="form-control" value="<?php echo $year; ?>"></div>
            <div class="form-group col-md-2" style="margin-bottom:0;"><button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-filter"></i> Filter</button></div>
        </form>

        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:16px;">
            <div style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:14px;"><div style="color:#6B7280; font-size:12px; text-transform:uppercase;">Slips</div><div style="font-size:22px; font-weight:800;"><?php echo count($rows); ?></div></div>
            <div style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:14px;"><div style="color:#6B7280; font-size:12px; text-transform:uppercase;">Total Payroll</div><div style="font-size:22px; font-weight:800;"><?php echo number_format($grand, 2); ?></div></div>
            <div style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:14px;"><div style="color:#6B7280; font-size:12px; text-transform:uppercase;">Paid / Pending</div><div style="font-size:22px; font-weight:800;"><span style="color:#16A34A;"><?php echo number_format($paid, 2); ?></span> / <span style="color:#DC2626;"><?php echo number_format($pending, 2); ?></span></div></div>
        </div>

        <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
            <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                <thead><tr><th>#</th><th>Employee</th><th>Designation</th><th>Month</th><th>Basic</th><th>Allowance</th><th>Deduction</th><th>Net</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <?php if (count($rows) === 0): ?><tr><td colspan="10" style="text-align:center; color:#6B7280; padding:25px;">Koi payroll slip nahi mili.</td></tr><?php endif; ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td style="color:#E5E7EB; font-size:11px;"><i class="fa fa-tag"></i> <?php echo $r['payroll_id']; ?></td>
                            <td><?php echo e($r['first_name'] . ' ' . $r['last_name']); ?></td>
                            <td><?php echo e($r['designation'] ?? '-'); ?></td>
                            <td><?php echo e($r['month']) . ' / ' . e($r['year']); ?></td>
                            <td><?php echo number_format($r['basic_salary'], 2); ?></td>
                            <td style="color:#16A34A;"><?php echo number_format($r['allowances'], 2); ?></td>
                            <td style="color:#DC2626;"><?php echo number_format($r['deductions'], 2); ?></td>
                            <td><strong><?php echo number_format($r['net_salary'], 2); ?></strong></td>
                            <td><span class="status-badge status-<?php echo $r['status']; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>payroll.php?month=<?php echo $r['month']; ?>&year=<?php echo $r['year']; ?>" target="_blank" class="btn btn-info btn-xs" style="color:#fff;"><i class="fa fa-file"></i> Slip</a>
                                <?php if ($r['status'] != 'paid'): ?>
                                    <form method="post" action="view_payroll.php" style="display:inline;">
                                        <input type="hidden" name="action" value="MarkPaid">
                                        <input type="hidden" name="payroll_id" value="<?php echo $r['payroll_id']; ?>">
                                        <button class="btn btn-success btn-xs"><i class="fa fa-check"></i> Pay</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot><tr style="background:#F9FAFB;"><th colspan="9" style="text-align:right;">Total Net</th><th><?php echo number_format($grand, 2); ?></th></tr></tfoot>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>