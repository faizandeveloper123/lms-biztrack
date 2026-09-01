<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'View Challans';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'DeleteChallan') {
    $cid = (int) ($_POST['challan_id'] ?? 0);
    if ($cid > 0) {
        $stmt = db_prepare("DELETE FROM fee_challans WHERE challan_id=?");
        $stmt->bind_param('i', $cid);
        $stmt->execute();
        $message = 'Challan deleted successfully.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'PayFee') {
    $cid = (int) ($_POST['challan_id'] ?? 0);
    $amount = (float) ($_POST['amount'] ?? 0);
    $method = trim($_POST['payment_method'] ?? 'cash');
    if ($cid <= 0 || $amount <= 0) {
        $error = 'Invalid payment request.';
    } else {
        $ch = db_query("SELECT * FROM fee_challans WHERE challan_id=$cid")->fetch_assoc();
        if (!$ch) {
            $error = 'Challan not found.';
        } else {
            $due = (float) $ch['total_amount'] - (float) $ch['paid_amount'];
            if ($amount > $due) {
                $error = 'Payment amount cannot exceed due amount (' . number_format($due, 2) . ').';
            } else {
                $new_paid = (float) $ch['paid_amount'] + $amount;
                $new_status = abs($new_paid - (float) $ch['total_amount']) < 0.01 ? 'paid' : 'partial';
                $uid = $_SESSION['user_id'];
                $stmt = db_prepare("INSERT INTO fee_payments (challan_id, amount, payment_method, received_by) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('idsi', $cid, $amount, $method, $uid);
                $stmt->execute();
                $stmt2 = db_prepare("UPDATE fee_challans SET paid_amount=?, status=? WHERE challan_id=?");
                $stmt2->bind_param('dsi', $new_paid, $new_status, $cid);
                $stmt2->execute();
                $message = 'Payment of ' . number_format($amount, 2) . ' recorded successfully!';
            }
        }
    }
}

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

$sel_class = (int) ($_GET['class_id'] ?? 0);
$sel_status = $_GET['status'] ?? '';
$q = trim($_GET['q'] ?? '');

$where = [];
$params = []; $types = '';
if ($sel_class > 0) { $where[] = "c.class_id=?"; $params[] = $sel_class; $types .= 'i'; }
if ($sel_status !== '') { $where[] = "c.status=?"; $params[] = $sel_status; $types .= 's'; }
if ($q !== '') { $where[] = "(s.first_name LIKE ? OR s.father_name LIKE ? OR c.challan_no LIKE ?)"; $like = "%$q%"; $params[] = $like; $params[] = $like; $params[] = $like; $types .= 'sss'; }

$sql = "SELECT c.*, s.first_name, s.father_name, s.roll_no, cl.class_name
        FROM fee_challans c
        JOIN students s ON c.student_id = s.student_id
        LEFT JOIN classes cl ON c.class_id = cl.class_id";
if (count($where)) { $sql .= " WHERE " . implode(' AND ', $where); }
$sql .= " ORDER BY c.created_at DESC, c.challan_id DESC LIMIT 300";

$challans = [];
if (count($params)) {
    $stmt = db_prepare($sql); $stmt->bind_param($types, ...$params); $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = db_query($sql);
}
while ($row = $res->fetch_assoc()) { $challans[] = $row; }

include __DIR__ . '/includes/header.php';
?>
<style>
.search-bar-student { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px; }
.status-badge { padding:4px 12px; border-radius:999px; font-size:12px; font-weight:700; }
.status-unpaid { background:#FEE2E2; color:#DC2626; }
.status-partial { background:#FFF7E0; color:#F59E0B; }
.status-paid { background:#DCFCE7; color:#16A34A; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-eye"></i> View Challans <span style="font-size:14px; color:#6B7280;">(<?php echo count($challans); ?> records)</span></h3>
            <a href="<?php echo BASE_URL; ?>monthly_challan.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-plus"></i> Create Challan</a>
        </div>

        <form method="get" action="view_challan_details.php" class="search-bar-student">
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
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="unpaid" <?php echo $sel_status === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                    <option value="partial" <?php echo $sel_status === 'partial' ? 'selected' : ''; ?>>Partial</option>
                    <option value="paid" <?php echo $sel_status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                </select>
            </div>
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>Search</label>
                <input type="text" name="q" class="form-control" placeholder="Student / Challan No" value="<?php echo e($q); ?>">
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-search"></i> Search</button>
            </div>
        </form>

        <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
            <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                <thead>
                    <tr>
                        <th>Challan No</th>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Month</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Due</th>
                        <th>Status</th>
                        <th style="width:160px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($challans) === 0): ?>
                        <tr><td colspan="9" style="text-align:center; color:#6B7280; padding:30px;">No challans found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($challans as $c): $due = (float)$c['total_amount'] - (float)$c['paid_amount']; ?>
                        <tr>
                            <td><strong><?php echo e($c['challan_no']); ?></strong></td>
                            <td><?php echo e($c['first_name']); ?><br><small style="color:#6B7280;"><?php echo e($c['father_name']); ?></small></td>
                            <td><?php echo e($c['class_name'] ?? '-'); ?></td>
                            <td><?php echo e($c['month']); ?></td>
                            <td style="font-weight:700;"><?php echo number_format($c['total_amount'], 2); ?></td>
                            <td style="color:#16A34A; font-weight:700;"><?php echo number_format($c['paid_amount'], 2); ?></td>
                            <td style="color:#DC2626; font-weight:700;"><?php echo number_format($due, 2); ?></td>
                            <td><span class="status-badge status-<?php echo $c['status']; ?>"><?php echo ucfirst($c['status']); ?></span></td>
                            <td>
                                <?php if ($due > 0): ?>
                                <button class="btn btn-success btn-xs pay-btn" data-id="<?php echo $c['challan_id']; ?>" data-no="<?php echo e($c['challan_no']); ?>" data-due="<?php echo $due; ?>"><i class="fa fa-money"></i> Pay</button>
                                <?php endif; ?>
                                <form method="post" action="view_challan_details.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this challan?');">
                                    <input type="hidden" name="action" value="DeleteChallan">
                                    <input type="hidden" name="challan_id" value="<?php echo $c['challan_id']; ?>">
                                    <button class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="payModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header" style="border-bottom:1px solid #E5E7EB; padding:16px 20px;">
                <h4 class="modal-title" style="font-weight:800; font-size:16px;"><i class="fa fa-money"></i> Receive Payment — <span id="payChallanNo"></span></h4>
            </div>
            <form method="post" action="view_challan_details.php">
                <div class="modal-body" style="padding:20px;">
                    <input type="hidden" name="action" value="PayFee">
                    <input type="hidden" name="challan_id" id="payChallanId">
                    <div class="form-group">
                        <label>Due Amount</label>
                        <input type="text" class="form-control" id="payDue" disabled>
                    </div>
                    <div class="form-group">
                        <label class="required">Payment Amount</label>
                        <input type="number" step="0.01" class="form-control" name="amount" id="payAmount" min="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method" class="form-control">
                            <option value="cash">Cash</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="jazzcash">JazzCash</option>
                            <option value="easypaisa">EasyPaisa</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #E5E7EB;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.pay-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.getElementById('payChallanId').value = this.dataset.id;
        document.getElementById('payChallanNo').textContent = this.dataset.no;
        document.getElementById('payDue').value = this.dataset.due;
        document.getElementById('payAmount').value = this.dataset.due;
        jQuery('#payModal').modal('show');
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>