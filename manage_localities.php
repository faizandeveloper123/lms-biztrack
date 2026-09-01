<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Manage Localities';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'AddLocality') {
        $name = trim($_POST['locality_name'] ?? '');
        if ($name === '') {
            $error = 'Locality name is required.';
        } else {
            $st2 = db_prepare("INSERT INTO localities (locality_name, status) VALUES (?, 1)");
            $st2->bind_param('s', $name);
            $st2->execute();
            $message = 'Locality added successfully!';
        }
    }

    if ($action === 'DeleteLocality') {
        $lid = (int) ($_POST['locality_id'] ?? 0);
        $st2 = db_prepare("DELETE FROM localities WHERE locality_id=?");
        $st2->bind_param('i', $lid);
        $st2->execute();
        $message = 'Locality deleted successfully!';
    }
}

$localities = [];
$res = db_query("SELECT * FROM localities ORDER BY locality_name");
while ($row = $res->fetch_assoc()) { $localities[] = $row; }

include __DIR__ . '/includes/header.php';
?>
<div class="main-content">
    <div class="container-fluid">
        <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-map-marker"></i> Manage Localities <span style="font-size:14px; color:#6B7280;">(<?php echo count($localities); ?> localities)</span></h3>
        </div>

        <div class="row">
            <div class="col-md-4">
                <form method="post" action="manage_localities.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px;">
                    <h4 style="font-size:15px; font-weight:800; margin:0 0 14px;">Add Locality</h4>
                    <input type="hidden" name="action" value="AddLocality">
                    <div class="form-group">
                        <label class="required">Locality Name</label>
                        <input type="text" name="locality_name" class="form-control" placeholder="e.g. Model Town" required>
                    </div>
                    <button type="submit" class="btn btn-success" style="width:100%;">Add Locality</button>
                </form>
            </div>
            <div class="col-md-8">
                <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
                    <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                        <thead><tr><th>#</th><th>Locality</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            <?php if (count($localities) === 0): ?><tr><td colspan="4" style="text-align:center; color:#6B7280; padding:25px;">No localities yet.</td></tr><?php endif; ?>
                            <?php foreach ($localities as $l): ?>
                                <tr>
                                    <td><?php echo $l['locality_id']; ?></td>
                                    <td><strong><?php echo e($l['locality_name']); ?></strong></td>
                                    <td><span class="status-badge status-<?php echo $l['status'] ? 'paid' : 'unpaid'; ?>"><?php echo $l['status'] ? 'Active' : 'Inactive'; ?></span></td>
                                    <td>
                                        <form method="post" action="manage_localities.php" style="display:inline;" onsubmit="return confirm('Delete?');">
                                            <input type="hidden" name="action" value="DeleteLocality">
                                            <input type="hidden" name="locality_id" value="<?php echo $l['locality_id']; ?>">
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