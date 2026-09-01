<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Fee Collection Reports';

$sel_class = (int) ($_GET['class_id'] ?? 0);
$sel_month = (int) ($_GET['month'] ?? (int) date('m'));
$sel_year  = (int) ($_GET['year'] ?? (int) date('Y'));

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

$totals = [
    'billed' => 0, 'collected' => 0, 'due' => 0, 'paid_challans' => 0, 'unpaid_challans' => 0,
];

$where = "MONTH(c.created_at)=$sel_month AND YEAR(c.created_at)=$sel_year";
if ($sel_class > 0) { $where .= " AND c.class_id=$sel_class"; }

$challans = [];
$res = db_query("SELECT c.*, s.first_name, s.father_name, cl.class_name
                 FROM fee_challans c
                 JOIN students s ON c.student_id=s.student_id
                 LEFT JOIN classes cl ON c.class_id=cl.class_id
                 WHERE $where ORDER BY c.challan_id");
while ($row = $res->fetch_assoc()) {
    $challans[] = $row;
    $totals['billed'] += (float) $row['total_amount'];
    $totals['collected'] += (float) $row['paid_amount'];
    $totals['due'] += ((float) $row['total_amount'] - (float) $row['paid_amount']);
    if ($row['status'] === 'paid') $totals['paid_challans']++;
    else $totals['unpaid_challans']++;
}

include __DIR__ . '/includes/header.php';
?>
<style>
.search-bar-student { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px; }
.analytics-cards { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:16px; }
.analytics-cards .ac { background:#fff; border:1px solid #E5E7EB; border-radius:12px; padding:14px; text-align:center; }
.analytics-cards .ac .n { font-size:20px; font-weight:800; }
.analytics-cards .ac .l { font-size:11.5px; color:#6B7280; }
@media (max-width:900px){ .analytics-cards{ grid-template-columns:repeat(2,1fr);} }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-bar-chart"></i> Fee Collection Reports</h3>
        </div>

        <form method="get" action="multi_fee_reports.php" class="search-bar-student">
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>Class</label>
                <select name="class_id" class="form-control">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?php echo $c['class_id']; ?>" <?php echo $sel_class == $c['class_id'] ? 'selected' : ''; ?>><?php echo e($c['class_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <label>Month</label>
                <select name="month" class="form-control">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $sel_month == $m ? 'selected' : ''; ?>><?php echo date('M', mktime(0,0,0,$m,1)); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <label>Year</label>
                <select name="year" class="form-control">
                    <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $sel_year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-search"></i> Load</button>
            </div>
        </form>

        <div class="analytics-cards">
            <div class="ac"><div class="n" style="color:#374151;"><?php echo number_format($totals['billed'], 2); ?></div><div class="l">Total Billed</div></div>
            <div class="ac"><div class="n" style="color:#16A34A;"><?php echo number_format($totals['collected'], 2); ?></div><div class="l">Total Collected</div></div>
            <div class="ac"><div class="n" style="color:#DC2626;"><?php echo number_format($totals['due'], 2); ?></div><div class="l">Total Due</div></div>
            <div class="ac"><div class="n" style="color:#377DFF;"><?php echo $totals['paid_challans']; ?></div><div class="l">Paid Challans</div></div>
            <div class="ac"><div class="n" style="color:#F59E0B;"><?php echo $totals['unpaid_challans']; ?></div><div class="l">Unpaid Challans</div></div>
        </div>

        <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
            <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                <thead>
                    <tr>
                        <th>Challan No</th>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Billed</th>
                        <th>Collected</th>
                        <th>Due</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($challans) === 0): ?>
                        <tr><td colspan="7" style="text-align:center; color:#6B7280; padding:30px;">No challans for the selected period.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($challans as $c): $due = (float)$c['total_amount'] - (float)$c['paid_amount']; ?>
                        <tr>
                            <td><strong><?php echo e($c['challan_no']); ?></strong></td>
                            <td><?php echo e($c['first_name']); ?></td>
                            <td><?php echo e($c['class_name'] ?? '-'); ?></td>
                            <td><?php echo number_format($c['total_amount'], 2); ?></td>
                            <td style="color:#16A34A; font-weight:700;"><?php echo number_format($c['paid_amount'], 2); ?></td>
                            <td style="color:#DC2626; font-weight:700;"><?php echo number_format($due, 2); ?></td>
                            <td><span class="status-badge status-<?php echo $c['status']; ?>"><?php echo ucfirst($c['status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>