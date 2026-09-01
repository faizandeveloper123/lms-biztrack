<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Add Revenue';

$message = '';
$error = '';

$heads = [];
$res = db_query("SELECT * FROM revenue_heads ORDER BY head_name");
while ($row = $res->fetch_assoc()) { $heads[] = $row; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'AddRevenue') {
    $head_id = (int) ($_POST['head_id'] ?? 0);
    $amount = (float) ($_POST['amount'] ?? 0);
    $desc = trim($_POST['description'] ?? '');
    $rdate = trim($_POST['revenue_date'] ?? date('Y-m-d'));
    if ($amount <= 0) {
        $error = 'Valid amount required.';
    } else {
        $st2 = db_prepare("INSERT INTO revenues (head_id, description, amount, revenue_date, created_by) VALUES (?, ?, ?, ?, ?)");
        $st2->bind_param('isdsi', $head_id, $desc, $amount, $rdate, $_SESSION['user_id']);
        $st2->execute();
        $message = 'Revenue added successfully!';
    }
}

$total = (float) (db_query("SELECT COALESCE(SUM(amount),0) t FROM revenues")->fetch_assoc()['t'] ?? 0);

include __DIR__ . '/includes/header.php';
?>
<div class="main-content">
    <div class="container-fluid">
        <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-plus-circle"></i> Add Revenue</h3>
            <div>
                <a href="<?php echo BASE_URL; ?>revenue_heads.php" class="btn btn-warning" style="color:#fff;"><i class="fa fa-tags"></i> Revenue Heads</a>
                <a href="<?php echo BASE_URL; ?>revenue_list.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-list"></i> List Revenues</a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <form method="post" action="add_revenue.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px;">
                    <input type="hidden" name="action" value="AddRevenue">
                    <div class="form-group">
                        <label class="required">Revenue Head</label>
                        <select name="head_id" class="form-control" required>
                            <option value="">Select Head</option>
                            <?php foreach ($heads as $h): ?>
                                <option value="<?php echo $h['head_id']; ?>"><?php echo e($h['head_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="required">Amount</label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="revenue_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="e.g. Book sale, donation"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success" style="width:100%;"><i class="fa fa-save"></i> Save Revenue</button>
                </form>
            </div>
            <div class="col-md-7">
                <div style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px;">
                    <h4 style="font-size:15px; font-weight:800; margin:0 0 10px;">Info</h4>
                    <p style="color:#6B7280;">Total revenue recorded: <strong style="color:#16A34A; font-size:18px;"><?php echo get_setting('currency_symbol') ?: 'Rs.'; ?> <?php echo number_format($total, 2); ?></strong></p>
                    <p style="color:#6B7280;">Manage revenue heads in <a href="<?php echo BASE_URL; ?>revenue_heads.php">Revenue Heads</a> page.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>