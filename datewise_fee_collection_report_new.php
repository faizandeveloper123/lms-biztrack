<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Fee Collection Report';

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

$rows = [];
$params = [];
$types = 'ss';
$params[] = $from;
$params[] = $to;

$sql = "SELECT p.*, c.challan_no, c.month, c.year, s.first_name, s.father_name, cl.class_name, u.full_name collected_by
        FROM fee_payments p
        LEFT JOIN fee_challans c ON p.challan_id=c.challan_id
        LEFT JOIN students s ON c.student_id=s.student_id
        LEFT JOIN classes cl ON c.class_id=cl.class_id
        LEFT JOIN users u ON p.received_by=u.user_id
        WHERE DATE(p.created_at) BETWEEN ? AND ?
        ORDER BY p.created_at DESC";

$st2 = db_prepare($sql);
$st2->bind_param($types, ...$params);
$st2->execute();
$res = $st2->get_result();
while ($row = $res->fetch_assoc()) { $rows[] = $row; }

$grand = 0.0;
$byMethod = [];
foreach ($rows as $r) { $grand += (float)$r['amount']; $m = $r['payment_method'] ?: 'cash'; $byMethod[$m] = ($byMethod[$m] ?? 0) + (float)$r['amount']; }

include __DIR__ . '/includes/header.php';
?>
<style>
.search-bar-student { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-chart-line"></i> Fee Collection Report</h3>
            <button onclick="window.print()" class="btn btn-success" style="color:#fff;"><i class="fa fa-print"></i> Print Report</button>
        </div>

        <form method="get" action="datewise_fee_collection_report_new.php" class="search-bar-student">
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>From Date</label>
                <input type="date" name="from" class="form-control" value="<?php echo e($from); ?>">
            </div>
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>To Date</label>
                <input type="date" name="to" class="form-control" value="<?php echo e($to); ?>">
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-filter"></i> Filter</button>
            </div>
            <div class="form-group col-md-4" style="margin-bottom:0; font-size:13px; color:#6B7280; padding-top:22px;">
                Total Collected: <strong style="color:#16A34A; font-size:16px;"><?php echo get_setting('currency_symbol', 'Rs.') . number_format($grand, 2); ?></strong>
                <?php if (count($byMethod) > 0): ?>
                    <div style="font-size:12px; margin-top:3px;">
                        <?php foreach ($byMethod as $m => $amt): ?><span class="status-badge" style="background:#F3F4F6; color:#374151;"><?php echo e(ucfirst($m)) . ': ' . number_format($amt, 2); ?></span> <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </form>

        <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
            <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                <thead>
                    <tr><th>#</th><th>Date</th><th>Challan No</th><th>Student</th><th>Class</th><th>Month</th><th>Amount</th><th>Method</th><th>Collected By</th></tr>
                </thead>
                <tbody>
                    <?php if (count($rows) === 0): ?>
                        <tr><td colspan="9" style="text-align:center; color:#6B7280; padding:25px;">Is range mein koi payment nahi mili.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?php echo $r['payment_id']; ?></td>
                            <td><?php echo date('d M Y h:i A', strtotime($r['created_at'])); ?></td>
                            <td><strong><?php echo e($r['challan_no']); ?></strong></td>
                            <td><?php echo e($r['first_name']); ?><br><small style="color:#6B7280;"><?php echo e($r['father_name'] ?? ''); ?></small></td>
                            <td><?php echo e($r['class_name'] ?? '-'); ?></td>
                            <td><?php echo e($r['month']) . ' / ' . e($r['year']); ?></td>
                            <td style="color:#16A34A; font-weight:700;"><?php echo number_format($r['amount'], 2); ?></td>
                            <td><span class="status-badge" style="background:#E0E7FF; color:#4338CA;"><?php echo e(ucfirst($r['payment_method'])); ?></span></td>
                            <td><?php echo e($r['collected_by'] ?? 'Admin'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:#F9FAFB;">
                        <th colspan="6" style="text-align:right;">Total</th>
                        <th style="color:#16A34A;"><?php echo number_format($grand, 2); ?></th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>