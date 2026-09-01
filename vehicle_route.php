<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Vehicle Route';

$message = '';
$error = '';

$vehicles = [];
$res = db_query("SELECT * FROM vehicles WHERE status=1 ORDER BY vehicle_name");
while ($row = $res->fetch_assoc()) { $vehicles[] = $row; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'AddRoute') {
    $route_name = trim($_POST['route_name'] ?? '');
    $fare = (float) ($_POST['fare'] ?? 0);
    if ($route_name === '') {
        $error = 'Route name is required.';
    } else {
        $st2 = db_prepare("INSERT INTO routes (route_name, fare) VALUES (?, ?)");
        $st2->bind_param('sd', $route_name, $fare);
        $st2->execute();
        $message = "Route '$route_name' added!";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'MapVehicle') {
    $route_id = (int) ($_POST['route_id'] ?? 0);
    $vehicle_id = (int) ($_POST['vehicle_id'] ?? 0);
    $key = "route_vehicle_{$route_id}";
    $st2 = db_prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    $st2->bind_param('si', $key, $vehicle_id);
    $st2->execute();
    $message = 'Vehicle mapped to route!';
}

$routes = [];
$res = db_query("SELECT * FROM routes ORDER BY route_name");
while ($row = $res->fetch_assoc()) {
    $vid = (int) (db_query("SELECT setting_value FROM settings WHERE setting_key='route_vehicle_{$row['route_id']}'")->fetch_assoc()['setting_value'] ?? 0);
    $row['vehicle'] = $vid > 0 ? db_query("SELECT * FROM vehicles WHERE vehicle_id=$vid")->fetch_assoc() : null;
    $routes[] = $row;
}

include __DIR__ . '/includes/header.php';
?>
<div class="main-content">
    <div class="container-fluid">
        <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-road"></i> Add / View Vehicle Route</h3>
            <a href="<?php echo BASE_URL; ?>route.php" class="btn btn-info" style="color:#fff;"><i class="fa fa-plus"></i> Routes</a>
        </div>

        <div class="row">
            <div class="col-md-5">
                <form method="post" action="vehicle_route.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:14px;">
                    <h4 style="font-size:15px; font-weight:800; margin:0 0 12px;">Add New Route</h4>
                    <input type="hidden" name="action" value="AddRoute">
                    <div class="form-group"><label class="required">Route Name</label><input type="text" name="route_name" class="form-control" placeholder="e.g. Johar Town" required></div>
                    <div class="form-group"><label>Fare</label><input type="number" step="0.01" name="fare" class="form-control" value="0"></div>
                    <button type="submit" class="btn btn-success" style="width:100%;"><i class="fa fa-plus"></i> Add Route</button>
                </form>
            </div>

            <div class="col-md-7">
                <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
                    <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                        <thead><tr><th>#</th><th>Route</th><th>Fare</th><th>Vehicle</th><th>Vehicle Name</th></tr></thead>
                        <tbody>
                            <?php if (count($routes) === 0): ?><tr><td colspan="5" style="text-align:center; color:#6B7280; padding:25px;">Koi route nahi. Left side form se add karein.</td></tr><?php endif; ?>
                            <?php foreach ($routes as $r): ?>
                                <tr>
                                    <td><?php echo $r['route_id']; ?></td>
                                    <td><strong><?php echo e($r['route_name']); ?></strong></td>
                                    <td><?php echo number_format($r['fare'], 2); ?></td>
                                    <td>
                                        <form method="post" action="vehicle_route.php" style="display:flex; gap:6px;">
                                            <input type="hidden" name="action" value="MapVehicle">
                                            <input type="hidden" name="route_id" value="<?php echo $r['route_id']; ?>">
                                            <select name="vehicle_id" class="form-control" style="width:150px;">
                                                <option value="0">None</option>
                                                <?php foreach ($vehicles as $v): ?><option value="<?php echo $v['vehicle_id']; ?>" <?php echo ($r['vehicle']['vehicle_id'] ?? 0) == $v['vehicle_id'] ? 'selected' : ''; ?>><?php echo e($v['vehicle_no']); ?></option><?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-primary btn-xs" style="color:#fff;"><i class="fa fa-check"></i></button>
                                        </form>
                                    </td>
                                    <td><?php echo e($r['vehicle']['vehicle_name'] ?? '-'); ?></td>
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