<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Vehicles';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'AddVehicle') {
        $no = trim($_POST['vehicle_no'] ?? '');
        $name = trim($_POST['vehicle_name'] ?? '');
        $capacity = (int) ($_POST['capacity'] ?? 0);
        $driver = trim($_POST['driver_name'] ?? '');
        if ($no === '') {
            $error = 'Vehicle number is required.';
        } else {
            $st2 = db_prepare("INSERT INTO vehicles (vehicle_no, vehicle_name, capacity, driver_name, status) VALUES (?, ?, ?, ?, 1)");
            $st2->bind_param('ssis', $no, $name, $capacity, $driver);
            $st2->execute();
            $message = 'Vehicle added successfully!';
        }
    }

    if ($action === 'UpdateVehicle') {
        $vid = (int) ($_POST['vehicle_id'] ?? 0);
        $no = trim($_POST['vehicle_no'] ?? '');
        $name = trim($_POST['vehicle_name'] ?? '');
        $capacity = (int) ($_POST['capacity'] ?? 0);
        $driver = trim($_POST['driver_name'] ?? '');
        $st2 = db_prepare("UPDATE vehicles SET vehicle_no=?, vehicle_name=?, capacity=?, driver_name=? WHERE vehicle_id=?");
        $st2->bind_param('ssisi', $no, $name, $capacity, $driver, $vid);
        $st2->execute();
        $message = 'Vehicle updated successfully!';
    }

    if ($action === 'DeleteVehicle') {
        $vid = (int) ($_POST['vehicle_id'] ?? 0);
        $st2 = db_prepare("DELETE FROM vehicles WHERE vehicle_id=?");
        $st2->bind_param('i', $vid);
        $st2->execute();
        $message = 'Vehicle deleted successfully!';
    }
}

$vehicles = [];
$res = db_query("SELECT * FROM vehicles ORDER BY vehicle_id");
while ($row = $res->fetch_assoc()) { $vehicles[] = $row; }

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
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-bus"></i> Vehicles <span style="font-size:14px; color:#6B7280;">(<?php echo count($vehicles); ?> vehicles)</span></h3>
            <a href="<?php echo BASE_URL; ?>route.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-route"></i> Routes</a>
        </div>

        <div class="row">
            <div class="col-md-4">
                <form method="post" action="vehicles.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px;">
                    <h4 style="font-size:15px; font-weight:800; margin:0 0 14px;">Add Vehicle</h4>
                    <input type="hidden" name="action" value="AddVehicle">
                    <div class="form-group">
                        <label class="required">Vehicle No</label>
                        <input type="text" name="vehicle_no" class="form-control" placeholder="e.g. LEA-826" required>
                    </div>
                    <div class="form-group">
                        <label>Vehicle Name</label>
                        <input type="text" name="vehicle_name" class="form-control" placeholder="e.g. Hino">
                    </div>
                    <div class="form-group">
                        <label>Capacity</label>
                        <input type="number" name="capacity" class="form-control" value="50">
                    </div>
                    <div class="form-group">
                        <label>Driver Name</label>
                        <input type="text" name="driver_name" class="form-control" placeholder="e.g. Muhammad Ali">
                    </div>
                    <button type="submit" class="btn btn-success" style="width:100%;">Add Vehicle</button>
                </form>
            </div>

            <div class="col-md-8">
                <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
                    <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                        <thead>
                            <tr><th>#</th><th>Vehicle No</th><th>Name</th><th>Capacity</th><th>Driver</th><th style="width:150px;">Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if (count($vehicles) === 0): ?>
                                <tr><td colspan="6" style="text-align:center; color:#6B7280; padding:30px;">No vehicles added yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($vehicles as $v): ?>
                                <tr>
                                    <td><?php echo $v['vehicle_id']; ?></td>
                                    <td><strong><?php echo e($v['vehicle_no']); ?></strong></td>
                                    <td><?php echo e($v['vehicle_name'] ?? '-'); ?></td>
                                    <td><?php echo $v['capacity']; ?></td>
                                    <td><?php echo e($v['driver_name'] ?? '-'); ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-xs edit-v" data-id="<?php echo $v['vehicle_id']; ?>" data-no="<?php echo e($v['vehicle_no']); ?>" data-name="<?php echo e($v['vehicle_name']); ?>" data-cap="<?php echo $v['capacity']; ?>" data-driver="<?php echo e($v['driver_name']); ?>"><i class="fa fa-pencil"></i></button>
                                        <form method="post" action="vehicles.php" style="display:inline;" onsubmit="return confirm('Delete this vehicle?');">
                                            <input type="hidden" name="action" value="DeleteVehicle">
                                            <input type="hidden" name="vehicle_id" value="<?php echo $v['vehicle_id']; ?>">
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
<div class="modal fade" id="editVehicleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header" style="border-bottom:1px solid #E5E7EB; padding:16px 20px;">
                <h4 class="modal-title" style="font-weight:800; font-size:16px;">Edit Vehicle</h4>
            </div>
            <form method="post" action="vehicles.php">
                <div class="modal-body" style="padding:20px;">
                    <input type="hidden" name="action" value="UpdateVehicle">
                    <input type="hidden" name="vehicle_id" id="evId">
                    <div class="form-group">
                        <label class="required">Vehicle No</label>
                        <input type="text" name="vehicle_no" id="evNo" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Vehicle Name</label>
                        <input type="text" name="vehicle_name" id="evName" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Capacity</label>
                        <input type="number" name="capacity" id="evCap" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Driver Name</label>
                        <input type="text" name="driver_name" id="evDriver" class="form-control">
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
document.querySelectorAll('.edit-v').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.getElementById('evId').value = this.dataset.id;
        document.getElementById('evNo').value = this.dataset.no;
        document.getElementById('evName').value = this.dataset.name;
        document.getElementById('evCap').value = this.dataset.cap;
        document.getElementById('evDriver').value = this.dataset.driver;
        jQuery('#editVehicleModal').modal('show');
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>