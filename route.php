<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Transport Routes';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'AddRoute') {
        $name = trim($_POST['route_name'] ?? '');
        $fare = (float) ($_POST['fare'] ?? 0);
        if ($name === '') {
            $error = 'Route name is required.';
        } else {
            $st2 = db_prepare("INSERT INTO routes (route_name, fare, status) VALUES (?, ?, 1)");
            $st2->bind_param('sd', $name, $fare);
            $st2->execute();
            $message = 'Route added successfully!';
        }
    }

    if ($action === 'UpdateRoute') {
        $rid = (int) ($_POST['route_id'] ?? 0);
        $name = trim($_POST['route_name'] ?? '');
        $fare = (float) ($_POST['fare'] ?? 0);
        $st2 = db_prepare("UPDATE routes SET route_name=?, fare=? WHERE route_id=?");
        $st2->bind_param('sdi', $name, $fare, $rid);
        $st2->execute();
        $message = 'Route updated successfully!';
    }

    if ($action === 'DeleteRoute') {
        $rid = (int) ($_POST['route_id'] ?? 0);
        $st2 = db_prepare("DELETE FROM routes WHERE route_id=?");
        $st2->bind_param('i', $rid);
        $st2->execute();
        $message = 'Route deleted successfully!';
    }
}

$routes = [];
$res = db_query("SELECT * FROM routes ORDER BY route_id");
while ($row = $res->fetch_assoc()) { $routes[] = $row; }

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
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-route"></i> Transport Routes <span style="font-size:14px; color:#6B7280;">(<?php echo count($routes); ?> routes)</span></h3>
            <a href="<?php echo BASE_URL; ?>vehicles.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-bus"></i> Vehicles</a>
        </div>

        <div class="row">
            <div class="col-md-4">
                <form method="post" action="route.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px;">
                    <h4 style="font-size:15px; font-weight:800; margin:0 0 14px;">Add Route</h4>
                    <input type="hidden" name="action" value="AddRoute">
                    <div class="form-group">
                        <label class="required">Route Name</label>
                        <input type="text" name="route_name" class="form-control" placeholder="e.g. Gulberg" required>
                    </div>
                    <div class="form-group">
                        <label>Monthly Fare</label>
                        <input type="number" step="0.01" name="fare" class="form-control" value="2000">
                    </div>
                    <button type="submit" class="btn btn-success" style="width:100%;">Add Route</button>
                </form>
            </div>

            <div class="col-md-8">
                <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
                    <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                        <thead>
                            <tr><th>#</th><th>Route Name</th><th>Fare</th><th style="width:150px;">Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if (count($routes) === 0): ?>
                                <tr><td colspan="4" style="text-align:center; color:#6B7280; padding:30px;">No routes added yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($routes as $rt): ?>
                                <tr>
                                    <td><?php echo $rt['route_id']; ?></td>
                                    <td><strong><?php echo e($rt['route_name']); ?></strong></td>
                                    <td><?php echo number_format($rt['fare'], 2); ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-xs edit-r" data-id="<?php echo $rt['route_id']; ?>" data-name="<?php echo e($rt['route_name']); ?>" data-fare="<?php echo $rt['fare']; ?>"><i class="fa fa-pencil"></i></button>
                                        <form method="post" action="route.php" style="display:inline;" onsubmit="return confirm('Delete this route?');">
                                            <input type="hidden" name="action" value="DeleteRoute">
                                            <input type="hidden" name="route_id" value="<?php echo $rt['route_id']; ?>">
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
<div class="modal fade" id="editRouteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header" style="border-bottom:1px solid #E5E7EB; padding:16px 20px;">
                <h4 class="modal-title" style="font-weight:800; font-size:16px;">Edit Route</h4>
            </div>
            <form method="post" action="route.php">
                <div class="modal-body" style="padding:20px;">
                    <input type="hidden" name="action" value="UpdateRoute">
                    <input type="hidden" name="route_id" id="erId">
                    <div class="form-group">
                        <label class="required">Route Name</label>
                        <input type="text" name="route_name" id="erName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Monthly Fare</label>
                        <input type="number" step="0.01" name="fare" id="erFare" class="form-control">
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
document.querySelectorAll('.edit-r').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.getElementById('erId').value = this.dataset.id;
        document.getElementById('erName').value = this.dataset.name;
        document.getElementById('erFare').value = this.dataset.fare;
        jQuery('#editRouteModal').modal('show');
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>