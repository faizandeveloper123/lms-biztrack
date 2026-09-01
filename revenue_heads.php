<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Revenue Heads';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'AddHead') {
        $name = trim($_POST['head_name'] ?? '');
        if ($name === '') {
            $error = 'Head name is required.';
        } else {
            $st2 = db_prepare("INSERT INTO revenue_heads (head_name, status) VALUES (?, 1)");
            $st2->bind_param('s', $name);
            $st2->execute();
            $message = 'Revenue head added successfully!';
        }
    }

    if ($action === 'DeleteHead') {
        $hid = (int) ($_POST['head_id'] ?? 0);
        $st2 = db_prepare("DELETE FROM revenue_heads WHERE head_id=?");
        $st2->bind_param('i', $hid);
        $st2->execute();
        $message = 'Revenue head deleted successfully!';
    }
}

$heads = [];
$res = db_query("SELECT * FROM revenue_heads ORDER BY head_name");
while ($row = $res->fetch_assoc()) { $heads[] = $row; }

include __DIR__ . '/includes/header.php';
?>
<div class="main-content">
    <div class="container-fluid">
        <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-tags"></i> Revenue Heads <span style="font-size:14px; color:#6B7280;">(<?php echo count($heads); ?> heads)</span></h3>
        </div>

        <div class="row">
            <div class="col-md-4">
                <form method="post" action="revenue_heads.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px;">
                    <h4 style="font-size:15px; font-weight:800; margin:0 0 14px;">Add Revenue Head</h4>
                    <input type="hidden" name="action" value="AddHead">
                    <div class="form-group">
                        <label class="required">Head Name</label>
                        <input type="text" name="head_name" class="form-control" placeholder="e.g. Book Sales" required>
                    </div>
                    <button type="submit" class="btn btn-success" style="width:100%;">Add Head</button>
                </form>
            </div>
            <div class="col-md-8">
                <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
                    <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                        <thead><tr><th>#</th><th>Head Name</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            <?php if (count($heads) === 0): ?><tr><td colspan="4" style="text-align:center; color:#6B7280; padding:25px;">No revenue heads yet.</td></tr><?php endif; ?>
                            <?php foreach ($heads as $h): ?>
                                <tr>
                                    <td><?php echo $h['head_id']; ?></td>
                                    <td><strong><?php echo e($h['head_name']); ?></strong></td>
                                    <td><span class="status-badge status-<?php echo $h['status'] ? 'paid' : 'unpaid'; ?>"><?php echo $h['status'] ? 'Active' : 'Inactive'; ?></span></td>
                                    <td>
                                        <form method="post" action="revenue_heads.php" style="display:inline;" onsubmit="return confirm('Delete?');">
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

<?php include __DIR__ . '/includes/footer.php'; ?>