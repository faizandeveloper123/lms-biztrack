<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Receivable Fee Report';

$cls = (int) ($_GET['class_id'] ?? 0);
$month = $_GET['month'] ?? '';
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int) date('Y');

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

$where = ["c.status != 'paid'"];
$params = [];
$types = '';
if ($cls > 0) { $where[] = "c.class_id = ?"; $params[] = $cls; $types .= 'i'; }
if ($month !== '') { $where[] = "c.month = ?"; $params[] = $month; $types .= 's'; }
if ($year > 0) { $where[] = "c.year = ?"; $params[] = $year; $types .= 'i'; }

$sql = "SELECT c.*, s.first_name, s.father_name, s.phone, cl.class_name
        FROM fee_challans c
        LEFT JOIN students s ON c.student_id=s.student_id
        LEFT JOIN classes cl ON c.class_id=cl.class_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY c.class_id, s.first_name";

$rows = [];
if (count($params) > 0) {
    $st2 = db_prepare($sql);
    $st2->bind_param($types, ...$params);
    $st2->execute();
    $res = $st2->get_result();
} else {
    $res = db_query($sql);
}
while ($row = $res->fetch_assoc()) {
    $row['due'] = (float)$row['total_amount'] - (float)$row['paid_amount'];
    $rows[] = $row;
}

$grand = 0.0;
foreach ($rows as $r) { $grand += $r['due']; }

include __DIR__ . '/includes/header.php';
?>
<style>
.search-bar-student { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-file-text-o"></i> Receivable Fee Report <span style="font-size:13px; color:#6B7280;">(Unpaid Challans)</span></h3>
            <button onclick="window.print()" class="btn btn-success" style="color:#fff;"><i class="fa fa-print"></i> Print Report</button>
        </div>

        <form method="get" action="print_unpaid_fee_new.php" class="search-bar-student">
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>Class</label>
                <select name="class_id" class="form-control">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $cl): ?><option value="<?php echo $cl['class_id']; ?>" <?php echo $cls == $cl['class_id'] ? 'selected' : ''; ?>><?php echo e($cl['class_name']); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <label>Month</label>
                <select name="month" class="form-control">
                    <option value="">All</option>
                    <?php foreach (['January','February','March','April','May','June','July','August','September','October','November','December'] as $mm): ?>
                        <option value="<?php echo $mm; ?>" <?php echo $month == $mm ? 'selected' : ''; ?>><?php echo $mm; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <label>Year</label>
                <input type="number" name="year" class="form-control" value="<?php echo $year; ?>">
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-filter"></i> Filter</button>
            </div>
            <div class="form-group col-md-3" style="margin-bottom:0; font-size:13px; color:#6B7280; padding-top:22px;">
                Total Receivable: <strong style="color:#DC2626; font-size:16px;"><?php echo get_setting('currency_symbol', 'Rs.') . number_format($grand, 2); ?></strong> (<?php echo count($rows); ?> challans)
            </div>
        </form>

        <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
            <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                <thead>
                    <tr><th>#</th><th>Challan No</th><th>Student</th><th>Phone</th><th>Class</th><th>Month</th><th>Total</th><th>Paid</th><th>Due</th></tr>
                </thead>
                <tbody>
                    <?php if (count($rows) === 0): ?>
                        <tr><td colspan="9" style="text-align:center; color:#6B7280; padding:25px;">Koi unpaid challan nahi mila. Well done!</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?php echo $r['challan_id']; ?></td>
                            <td><a href="<?php echo BASE_URL; ?>view_challan_details.php?challan_id=<?php echo $r['challan_id']; ?>"><strong><?php echo e($r['challan_no']); ?></strong></a></td>
                            <td><?php echo e($r['first_name']); ?><br><small style="color:#6B7280;"><?php echo e($r['father_name'] ?? ''); ?></small></td>
                            <td><?php echo e($r['phone'] ?? '-'); ?></td>
                            <td><?php echo e($r['class_name'] ?? '-'); ?></td>
                            <td><?php echo e($r['month']) . ' / ' . e($r['year']); ?></td>
                            <td><?php echo number_format($r['total_amount'], 2); ?></td>
                            <td style="color:#16A34A;"><?php echo number_format($r['paid_amount'], 2); ?></td>
                            <td style="color:#DC2626; font-weight:800;"><?php echo number_format($r['due'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:#F9FAFB;">
                        <th colspan="8" style="text-align:right;">Total Receivable</th>
                        <th style="color:#DC2626;"><?php echo number_format($grand, 2); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>