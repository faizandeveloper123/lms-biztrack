<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Monthly Invoices';

$month = $_GET['month'] ?? date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int) date('Y');

$invoices = [];
$res = db_query("SELECT c.*, s.first_name, s.father_name, cl.class_name,
        (SELECT COUNT(*) FROM fee_payments p WHERE p.challan_id=c.challan_id) payments_count
        FROM fee_challans c
        LEFT JOIN students s ON c.student_id=s.student_id
        LEFT JOIN classes cl ON c.class_id=cl.class_id
        WHERE MONTH(c.created_at)=$month AND YEAR(c.created_at)=$year
        ORDER BY c.challan_id DESC");
while ($row = $res->fetch_assoc()) { $invoices[] = $row; }

$grandTotal = 0.0; $grandPaid = 0.0;
foreach ($invoices as $i) { $grandTotal += (float)$i['total_amount']; $grandPaid += (float)$i['paid_amount']; }

include __DIR__ . '/includes/header.php';
?>
<style>
.search-bar-student { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-file-text"></i> Monthly Invoice List View</h3>
            <button onclick="window.print()" class="btn btn-success" style="color:#fff;"><i class="fa fa-print"></i> Print</button>
        </div>

        <form method="get" action="monthly_invoices.php" class="search-bar-student">
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <label>Month</label>
                <input type="month" name="ym" class="form-control" value="<?php echo sprintf('%04d-%02d', $year, $month); ?>">
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-filter"></i> Show</button>
            </div>
        </form>

        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:16px;">
            <div style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:14px;">
                <div style="color:#6B7280; font-size:12px; text-transform:uppercase;">Invoices</div>
                <div style="font-size:22px; font-weight:800;"><?php echo count($invoices); ?></div>
            </div>
            <div style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:14px;">
                <div style="color:#6B7280; font-size:12px; text-transform:uppercase;">Total Billed</div>
                <div style="font-size:22px; font-weight:800;"><?php echo number_format($grandTotal, 2); ?></div>
            </div>
            <div style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:14px;">
                <div style="color:#6B7280; font-size:12px; text-transform:uppercase;">Total Collected</div>
                <div style="font-size:22px; font-weight:800; color:#16A34A;"><?php echo number_format($grandPaid, 2); ?></div>
            </div>
        </div>

        <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
            <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                <thead>
                    <tr><th>#</th><th>Invoice / Challan</th><th>Student</th><th>Class</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    <?php if (count($invoices) === 0): ?>
                        <tr><td colspan="9" style="text-align:center; color:#6B7280; padding:25px;">Is month mein koi invoice nahi.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($invoices as $i): $bal = (float)$i['total_amount'] - (float)$i['paid_amount']; ?>
                        <tr>
                            <td><?php echo $i['challan_id']; ?></td>
                            <td><strong><?php echo e($i['challan_no']); ?></strong><br><small style="color:#6B7280;"><?php echo e($i['month']) . ' / ' . e($i['year']); ?></small></td>
                            <td><?php echo e($i['first_name']); ?></td>
                            <td><?php echo e($i['class_name'] ?? '-'); ?></td>
                            <td><?php echo number_format($i['total_amount'], 2); ?></td>
                            <td style="color:#16A34A;"><?php echo number_format($i['paid_amount'], 2); ?></td>
                            <td style="color:<?php echo $bal > 0 ? '#DC2626' : '#16A34A'; ?>; font-weight:700;"><?php echo number_format($bal, 2); ?></td>
                            <td><span class="status-badge status-<?php echo $i['status']; ?>"><?php echo ucfirst($i['status']); ?></span></td>
                            <td><a href="<?php echo BASE_URL; ?>view_challan_details.php?challan_id=<?php echo $i['challan_id']; ?>" class="btn btn-info btn-xs" style="color:#fff;"><i class="fa fa-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:#F9FAFB;">
                        <th colspan="4" style="text-align:right;">Totals</th>
                        <th><?php echo number_format($grandTotal, 2); ?></th>
                        <th style="color:#16A34A;"><?php echo number_format($grandPaid, 2); ?></th>
                        <th style="color:#DC2626;"><?php echo number_format($grandTotal - $grandPaid, 2); ?></th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>