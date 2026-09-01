<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'List of Revenues';

$message = '';
$error = '';

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$head_id = (int) ($_GET['head_id'] ?? 0);

$heads = [];
$res = db_query("SELECT * FROM revenue_heads ORDER BY head_name");
while ($row = $res->fetch_assoc()) { $heads[] = $row; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'DeleteRevenue') {
    $rid = (int) ($_POST['revenue_id'] ?? 0);
    if ($rid > 0) {
        $st2 = db_prepare("DELETE FROM revenues WHERE revenue_id=?");
        $st2->bind_param('i', $rid);
        $st2->execute();
        $message = 'Revenue deleted successfully!';
    }
}

$where = [];
$params = [];
$types = '';
if ($from !== '') { $where[] = "revenue_date >= ?"; $params[] = $from; $types .= 's'; }
if ($to !== '') { $where[] = "revenue_date <= ?"; $params[] = $to; $types .= 's'; }
if ($head_id > 0) { $where[] = "head_id = ?"; $params[] = $head_id; $types .= 'i'; }

$sql = "SELECT r.*, h.head_name FROM revenues r LEFT JOIN revenue_heads h ON r.head_id=h.head_id";
if (count($where) > 0) { $sql .= " WHERE " . implode(' AND ', $where); }
$sql .= " ORDER BY r.revenue_date DESC, r.revenue_id DESC";

$rows = [];
if (count($params) > 0) {
    $st2 = db_prepare($sql);
    $st2->bind_param($types, ...$params);
    $st2->execute();
    $res = $st2->get_result();
} else {
    $res = db_query($sql);
}
while ($row = $res->fetch_assoc()) { $rows[] = $row; }

$grand = 0.0;
foreach ($rows as $r) { $grand += (float) $r['amount']; }

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
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-list"></i> List of Revenues</h3>
            <a href="<?php echo BASE_URL; ?>add_revenue.php" class="btn btn-success" style="color:#fff;"><i class="fa fa-plus"></i> Add Revenue</a>
        </div>

        <form method="get" action="revenue_list.php" class="search-bar-student">
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>From</label>
                <input type="date" name="from" class="form-control" value="<?php echo e($from); ?>">
            </div>
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>To</label>
                <input type="date" name="to" class="form-control" value="<?php echo e($to); ?>">
            </div>
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>Head</label>
                <select name="head_id" class="form-control">
                    <option value="">All Heads</option>
                    <?php foreach ($heads as $h): ?>
                        <option value="<?php echo $h['head_id']; ?>" <?php echo $head_id == $h['head_id'] ? 'selected' : ''; ?>><?php echo e($h['head_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-search"></i> Filter</button>
            </div>
        </form>

        <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
            <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                <thead>
                    <tr><th>#</th><th>Date</th><th>Head</th><th>Description</th><th>Amount</th><th></th></tr>
                </thead>
                <tbody>
                    <?php if (count($rows) === 0): ?>
                        <tr><td colspan="6" style="text-align:center; color:#6B7280; padding:25px;">Koi revenue record nahi mila.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?php echo $r['revenue_id']; ?></td>
                            <td><?php echo $r['revenue_date'] ? date('d M Y', strtotime($r['revenue_date'])) : '-'; ?></td>
                            <td><span class="status-badge" style="background:#E0E7FF; color:#4338CA;"><?php echo e($r['head_name'] ?: '-'); ?></span></td>
                            <td><?php echo e($r['description'] ?: '-'); ?></td>
                            <td style="color:#16A34A; font-weight:700;"><?php echo number_format($r['amount'], 2); ?></td>
                            <td>
                                <form method="post" action="revenue_list.php" style="display:inline;" onsubmit="return confirm('Delete this revenue?');">
                                    <input type="hidden" name="action" value="DeleteRevenue">
                                    <input type="hidden" name="revenue_id" value="<?php echo $r['revenue_id']; ?>">
                                    <button class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:#F9FAFB;">
                        <th colspan="4" style="text-align:right;">Total</th>
                        <th style="color:#16A34A;"><?php echo number_format($grand, 2); ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>