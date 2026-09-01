<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Monthly Expense Report';

$sel_month = (int) ($_GET['month'] ?? (int) date('m'));
$sel_year  = (int) ($_GET['year'] ?? (int) date('Y'));

$expenses = [];
$res = db_query("SELECT * FROM expenses WHERE MONTH(expense_date)=$sel_month AND YEAR(expense_date)=$sel_year ORDER BY expense_date");
while ($row = $res->fetch_assoc()) { $expenses[] = $row; }

$revenues = [];
$res = db_query("SELECT r.*, h.head_name FROM revenues r LEFT JOIN revenue_heads h ON r.head_id=h.head_id WHERE MONTH(r.revenue_date)=$sel_month AND YEAR(r.revenue_date)=$sel_year ORDER BY r.revenue_date");
while ($row = $res->fetch_assoc()) { $revenues[] = $row; }

$total_expense = 0;
foreach ($expenses as $e) { $total_expense += (float) $e['amount']; }
$total_revenue = 0;
foreach ($revenues as $r) { $total_revenue += (float) $r['amount']; }
$balance = $total_revenue - $total_expense;

$byCategory = [];
foreach ($expenses as $e) { $cat = $e['category'] ?: 'Other'; $byCategory[$cat] = ($byCategory[$cat] ?? 0) + (float) $e['amount']; }

include __DIR__ . '/includes/header.php';
?>
<style>
.search-bar-student { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px; }
.analytics-cards { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:16px; }
.analytics-cards .ac { background:#fff; border:1px solid #E5E7EB; border-radius:12px; padding:14px; text-align:center; }
.analytics-cards .ac .n { font-size:20px; font-weight:800; }
.analytics-cards .ac .l { font-size:11.5px; color:#6B7280; }
@media (max-width:900px){ .analytics-cards{ grid-template-columns:repeat(1,1fr);} }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-bar-chart"></i> Monthly Report <span style="font-size:14px; color:#6B7280;">(<?php echo date('F Y', mktime(0,0,0,$sel_month,1,$sel_year)); ?>)</span></h3>
            <a href="<?php echo BASE_URL; ?>manage_expenses.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-plus"></i> Add Expense</a>
        </div>

        <form method="get" action="monthly_expenses_report.php" class="search-bar-student">
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
                <button type="submit" class="btn btn-primary" style="width:100%;">Load</button>
            </div>
        </form>

        <div class="analytics-cards">
            <div class="ac"><div class="n" style="color:#16A34A;"><?php echo number_format($total_revenue, 2); ?></div><div class="l">Total Revenue</div></div>
            <div class="ac"><div class="n" style="color:#DC2626;"><?php echo number_format($total_expense, 2); ?></div><div class="l">Total Expenses</div></div>
            <div class="ac"><div class="n" style="color:<?php echo $balance >= 0 ? '#FF7A1B' : '#DC2626'; ?>;"><?php echo number_format($balance, 2); ?></div><div class="l">Net Balance</div></div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
                    <h4 style="font-size:15px; font-weight:800; padding:14px 16px; margin:0; border-bottom:1px solid #F3F4F6;">Expenses by Category</h4>
                    <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                        <thead><tr><th>Category</th><th>Amount</th></tr></thead>
                        <tbody>
                            <?php if (count($byCategory) === 0): ?><tr><td colspan="2" style="text-align:center; color:#6B7280; padding:25px;">No expenses this month.</td></tr><?php endif; ?>
                            <?php foreach ($byCategory as $cat => $amt): ?>
                                <tr>
                                    <td><strong><?php echo e($cat); ?></strong></td>
                                    <td style="color:#DC2626; font-weight:700;"><?php echo number_format($amt, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-7">
                <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px; margin-bottom:14px;">
                    <h4 style="font-size:15px; font-weight:800; padding:14px 16px; margin:0; border-bottom:1px solid #F3F4F6;">Revenue Details</h4>
                    <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                        <thead><tr><th>#</th><th>Description</th><th>Head</th><th>Amount</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php if (count($revenues) === 0): ?><tr><td colspan="5" style="text-align:center; color:#6B7280; padding:25px;">No revenue this month.</td></tr><?php endif; ?>
                            <?php foreach ($revenues as $rv): ?>
                                <tr>
                                    <td><?php echo $rv['revenue_id']; ?></td>
                                    <td><strong><?php echo e($rv['description']); ?></strong></td>
                                    <td><?php echo e($rv['head_name'] ?? '-'); ?></td>
                                    <td style="color:#16A34A; font-weight:700;"><?php echo number_format($rv['amount'], 2); ?></td>
                                    <td><?php echo date('d M Y', strtotime($rv['revenue_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>