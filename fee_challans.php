<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'View Challan';

$status = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
$cls = (int) ($_GET['class_id'] ?? 0);

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

$where = [];
$params = [];
$types = '';
if ($status !== '') { $where[] = "c.status = ?"; $params[] = $status; $types .= 's'; }
if ($search !== '') { $where[] = "c.challan_no LIKE ?"; $params[] = "%$search%"; $types .= 's'; }
if ($cls > 0) { $where[] = "c.class_id = ?"; $params[] = $cls; $types .= 'i'; }

$sql = "SELECT c.*, s.first_name, s.father_name, cl.class_name,
        (SELECT COUNT(*) FROM fee_payments p WHERE p.challan_id=c.challan_id) payments_count
        FROM fee_challans c
        LEFT JOIN students s ON c.student_id=s.student_id
        LEFT JOIN classes cl ON c.class_id=cl.class_id";
if (count($where) > 0) { $sql .= " WHERE " . implode(' AND ', $where); }
$sql .= " ORDER BY c.created_at DESC";

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

include __DIR__ . '/includes/header.php';
?>
<style>
.search-bar-student { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-file-invoice"></i> View Challan</h3>
            <a href="<?php echo BASE_URL; ?>monthly_challan.php" class="btn btn-success" style="color:#fff;"><i class="fa fa-plus"></i> Create Challan</a>
        </div>

        <form method="get" action="fee_challans.php" class="search-bar-student">
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>Search</label>
                <input type="text" name="search" class="form-control" placeholder="Challan no / search" value="<?php echo e($search); ?>">
            </div>
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="">All</option>
                    <option value="unpaid" <?php echo $status == 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                    <option value="partial" <?php echo $status == 'partial' ? 'selected' : ''; ?>>Partial</option>
                    <option value="paid" <?php echo $status == 'paid' ? 'selected' : ''; ?>>Paid</option>
                </select>
            </div>
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>Class</label>
                <select name="class_id" class="form-control">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $cl): ?><option value="<?php echo $cl['class_id']; ?>" <?php echo $cls == $cl['class_id'] ? 'selected' : ''; ?>><?php echo e($cl['class_name']); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-search"></i> Filter</button>
            </div>
        </form>

        <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
            <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                <thead>
                    <tr><th>#</th><th>Challan No</th><th>Student</th><th>Class</th><th>Month</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php if (count($rows) === 0): ?>
                        <tr><td colspan="9" style="text-align:center; color:#6B7280; padding:25px;">Koi challan nahi mila.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $c): $bal = (float)$c['total_amount'] - (float)$c['paid_amount']; ?>
                        <tr>
                            <td><?php echo $c['challan_id']; ?></td>
                            <td><a href="<?php echo BASE_URL; ?>view_challan_details.php?challan_id=<?php echo $c['challan_id']; ?>"><strong><?php echo e($c['challan_no']); ?></strong></a></td>
                            <td><?php echo e($c['first_name']); ?><br><small style="color:#6B7280;"><?php echo e($c['father_name'] ?? ''); ?></small></td>
                            <td><?php echo e($c['class_name'] ?? '-'); ?></td>
                            <td><?php echo e($c['month']) . ' / ' . e($c['year']); ?></td>
                            <td><strong><?php echo number_format($c['total_amount'], 2); ?></strong></td>
                            <td style="color:#16A34A;"><?php echo number_format($c['paid_amount'], 2); ?></td>
                            <td style="color:<?php echo $bal > 0 ? '#DC2626' : '#16A34A'; ?>; font-weight:700;"><?php echo number_format($bal, 2); ?></td>
                            <td><span class="status-badge status-<?php echo $c['status']; ?>"><?php echo ucfirst($c['status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>