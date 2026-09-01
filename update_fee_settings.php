<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Fee Settings';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'AddHead') {
        $name = trim($_POST['head_name'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);
        $class_id = (int) ($_POST['class_id'] ?? 0);
        if ($name === '') {
            $error = 'Fee head name is required.';
        } else {
            $stmt = db_prepare("INSERT INTO fee_heads (head_name, amount, class_id, status) VALUES (?, ?, ?, 1)");
            $cid = $class_id > 0 ? $class_id : null;
            $stmt->bind_param('sdi', $name, $amount, $cid);
            $stmt->execute();
            $message = 'Fee head added successfully!';
        }
    }

    if ($action === 'UpdateHead') {
        $head_id = (int) ($_POST['head_id'] ?? 0);
        $name = trim($_POST['head_name'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);
        if ($head_id <= 0 || $name === '') {
            $error = 'Invalid fee head data.';
        } else {
            $stmt = db_prepare("UPDATE fee_heads SET head_name=?, amount=? WHERE head_id=?");
            $stmt->bind_param('sdi', $name, $amount, $head_id);
            $stmt->execute();
            $message = 'Fee head updated successfully!';
        }
    }

    if ($action === 'DeleteHead') {
        $head_id = (int) ($_POST['head_id'] ?? 0);
        if ($head_id > 0) {
            $stmt = db_prepare("DELETE FROM fee_heads WHERE head_id=?");
            $stmt->bind_param('i', $head_id);
            $stmt->execute();
            $message = 'Fee head deleted successfully!';
        }
    }
}

$heads = [];
$res = db_query("SELECT f.*, c.class_name FROM fee_heads f LEFT JOIN classes c ON f.class_id=c.class_id ORDER BY f.head_id");
while ($row = $res->fetch_assoc()) { $heads[] = $row; }

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

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
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-cog"></i> Fee Settings <span style="font-size:14px; color:#6B7280;">(<?php echo count($heads); ?> heads)</span></h3>
        </div>

        <div class="row">
            <div class="col-md-4">
                <form method="post" action="update_fee_settings.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px;">
                    <h4 style="font-size:15px; font-weight:800; margin:0 0 14px;">Add New Fee Head</h4>
                    <input type="hidden" name="action" value="AddHead">
                    <div class="form-group">
                        <label class="required">Fee Head Name</label>
                        <input type="text" name="head_name" class="form-control" placeholder="e.g. Tuition Fee" required>
                    </div>
                    <div class="form-group">
                        <label>Amount</label>
                        <input type="number" step="0.01" name="amount" class="form-control" value="0">
                    </div>
                    <div class="form-group">
                        <label>Class (optional)</label>
                        <select name="class_id" class="form-control">
                            <option value="0">All Classes</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo $c['class_id']; ?>"><?php echo e($c['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success" style="width:100%;">Add Fee Head</button>
                </form>
            </div>

            <div class="col-md-8">
                <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
                    <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                        <thead>
                            <tr><th>#</th><th>Head Name</th><th>Amount</th><th>Class</th><th style="width:140px;">Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if (count($heads) === 0): ?>
                                <tr><td colspan="5" style="text-align:center; color:#6B7280; padding:30px;">No fee heads added yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($heads as $h): ?>
                                <tr>
                                    <td><?php echo $h['head_id']; ?></td>
                                    <td><strong><?php echo e($h['head_name']); ?></strong></td>
                                    <td><?php echo number_format($h['amount'], 2); ?></td>
                                    <td><?php echo e($h['class_name'] ?? 'All'); ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-xs edit-head" data-id="<?php echo $h['head_id']; ?>" data-name="<?php echo e($h['head_name']); ?>" data-amount="<?php echo $h['amount']; ?>"><i class="fa fa-pencil"></i></button>
                                        <form method="post" action="update_fee_settings.php" style="display:inline;" onsubmit="return confirm('Delete this fee head?');">
                                            <input type="hidden" name="action" value="DeleteHead">
                                            <input type="hidden" name="head_id" value="<?php echo $h['head_id']; ?>">
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
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editHeadModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header" style="border-bottom:1px solid #E5E7EB; padding:16px 20px;">
                <h4 class="modal-title" style="font-weight:800; font-size:16px;">Edit Fee Head</h4>
            </div>
            <form method="post" action="update_fee_settings.php">
                <div class="modal-body" style="padding:20px;">
                    <input type="hidden" name="action" value="UpdateHead">
                    <input type="hidden" name="head_id" id="ehId">
                    <div class="form-group">
                        <label class="required">Fee Head Name</label>
                        <input type="text" name="head_name" id="ehName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Amount</label>
                        <input type="number" step="0.01" name="amount" id="ehAmount" class="form-control">
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #E5E7EB;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-head').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.getElementById('ehId').value = this.dataset.id;
        document.getElementById('ehName').value = this.dataset.name;
        document.getElementById('ehAmount').value = this.dataset.amount;
        jQuery('#editHeadModal').modal('show');
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>