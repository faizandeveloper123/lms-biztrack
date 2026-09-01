<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Manage Expenses';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'AddExpense') {
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);
        $date = trim($_POST['expense_date'] ?? date('Y-m-d'));
        if ($title === '' || $amount <= 0) {
            $error = 'Title and amount are required.';
        } else {
            $uid = $_SESSION['user_id'];
            $st2 = db_prepare("INSERT INTO expenses (title, category, amount, expense_date, created_by) VALUES (?, ?, ?, ?, ?)");
            $st2->bind_param('ssdsi', $title, $category, $amount, $date, $uid);
            $st2->execute();
            $message = 'Expense added successfully!';
        }
    }

    if ($action === 'DeleteExpense') {
        $eid = (int) ($_POST['expense_id'] ?? 0);
        $st2 = db_prepare("DELETE FROM expenses WHERE expense_id=?");
        $st2->bind_param('i', $eid);
        $st2->execute();
        $message = 'Expense deleted successfully!';
    }

    if ($action === 'AddRevenue') {
        $desc = trim($_POST['description'] ?? '');
        $head_id = (int) ($_POST['head_id'] ?? 0);
        $amount = (float) ($_POST['amount'] ?? 0);
        $date = trim($_POST['revenue_date'] ?? date('Y-m-d'));
        if ($desc === '' || $amount <= 0) {
            $error = 'Description and amount are required.';
        } else {
            $uid = $_SESSION['user_id'];
            $st2 = db_prepare("INSERT INTO revenues (head_id, description, amount, revenue_date, created_by) VALUES (?, ?, ?, ?, ?)");
            $hd = $head_id > 0 ? $head_id : null;
            $st2->bind_param('isdsi', $hd, $desc, $amount, $date, $uid);
            $st2->execute();
            $message = 'Revenue recorded successfully!';
        }
    }
}

$expenses = [];
$res = db_query("SELECT * FROM expenses ORDER BY expense_date DESC, expense_id DESC LIMIT 100");
while ($row = $res->fetch_assoc()) { $expenses[] = $row; }

$revenues = [];
$res = db_query("SELECT r.*, h.head_name FROM revenues r LEFT JOIN revenue_heads h ON r.head_id=h.head_id ORDER BY r.revenue_date DESC, r.revenue_id DESC LIMIT 100");
while ($row = $res->fetch_assoc()) { $revenues[] = $row; }

$heads = [];
$res = db_query("SELECT * FROM revenue_heads WHERE status=1 ORDER BY head_name");
while ($row = $res->fetch_assoc()) { $heads[] = $row; }

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
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-money"></i> Manage Expenses / Revenue</h3>
            <a href="<?php echo BASE_URL; ?>monthly_expenses_report.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-bar-chart"></i> Reports</a>
        </div>

        <div class="row">
            <div class="col-md-5">
                <form method="post" action="manage_expenses.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:14px;">
                    <h4 style="font-size:15px; font-weight:800; margin:0 0 14px;">Add Expense</h4>
                    <input type="hidden" name="action" value="AddExpense">
                    <div class="form-group">
                        <label class="required">Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Electricity Bill" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" name="category" class="form-control" placeholder="e.g. Utilities">
                    </div>
                    <div class="form-group">
                        <label class="required">Amount</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="expense_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <button type="submit" class="btn btn-danger" style="width:100%;"><i class="fa fa-plus"></i> Add Expense</button>
                </form>

                <form method="post" action="manage_expenses.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px;">
                    <h4 style="font-size:15px; font-weight:800; margin:0 0 14px;">Add Revenue</h4>
                    <input type="hidden" name="action" value="AddRevenue">
                    <div class="form-group">
                        <label>Revenue Head</label>
                        <select name="head_id" class="form-control">
                            <option value="0">Other</option>
                            <?php foreach ($heads as $h): ?>
                                <option value="<?php echo $h['head_id']; ?>"><?php echo e($h['head_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="required">Description</label>
                        <input type="text" name="description" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="required">Amount</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="revenue_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <button type="submit" class="btn btn-success" style="width:100%;"><i class="fa fa-plus"></i> Add Revenue</button>
                </form>
            </div>

            <div class="col-md-7">
                <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px; margin-bottom:14px;">
                    <h4 style="font-size:15px; font-weight:800; padding:14px 16px; margin:0; border-bottom:1px solid #F3F4F6;">Expenses</h4>
                    <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                        <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Amount</th><th>Date</th><th></th></tr></thead>
                        <tbody>
                            <?php if (count($expenses) === 0): ?><tr><td colspan="6" style="text-align:center; color:#6B7280; padding:25px;">No expenses added yet.</td></tr><?php endif; ?>
                            <?php foreach ($expenses as $e): ?>
                                <tr>
                                    <td><?php echo $e['expense_id']; ?></td>
                                    <td><strong><?php echo e($e['title']); ?></strong></td>
                                    <td><?php echo e($e['category'] ?? '-'); ?></td>
                                    <td style="color:#DC2626; font-weight:700;"><?php echo number_format($e['amount'], 2); ?></td>
                                    <td><?php echo date('d M Y', strtotime($e['expense_date'])); ?></td>
                                    <td>
                                        <form method="post" action="manage_expenses.php" style="display:inline;" onsubmit="return confirm('Delete?');">
                                            <input type="hidden" name="action" value="DeleteExpense">
                                            <input type="hidden" name="expense_id" value="<?php echo $e['expense_id']; ?>">
                                            <button class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
                    <h4 style="font-size:15px; font-weight:800; padding:14px 16px; margin:0; border-bottom:1px solid #F3F4F6;">Revenue</h4>
                    <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                        <thead><tr><th>#</th><th>Description</th><th>Head</th><th>Amount</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php if (count($revenues) === 0): ?><tr><td colspan="5" style="text-align:center; color:#6B7280; padding:25px;">No revenue recorded yet.</td></tr><?php endif; ?>
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