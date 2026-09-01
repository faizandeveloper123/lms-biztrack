<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Period Categories';

$categories = [];
$res = db_query("SELECT * FROM periods ORDER BY start_time, period_id");
while ($row = $res->fetch_assoc()) { $categories[] = $row; }

include __DIR__ . '/includes/header.php';
?>
<div class="main-content">
    <div class="container-fluid">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-clock-o"></i> Period Categories <span style="font-size:14px; color:#6B7280;">(<?php echo count($categories); ?> periods)</span></h3>
            <a href="<?php echo BASE_URL; ?>class_period.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-calendar"></i> Class Period</a>
        </div>

        <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
            <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                <thead>
                    <tr><th>#</th><th>Period Name</th><th>Start Time</th><th>End Time</th></tr>
                </thead>
                <tbody>
                    <?php if (count($categories) === 0): ?>
                        <tr><td colspan="4" style="text-align:center; color:#6B7280; padding:25px;">No periods created yet. Class Period page se periods add karein.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($categories as $p): ?>
                        <tr>
                            <td><?php echo $p['period_id']; ?></td>
                            <td><strong><?php echo e($p['period_name']); ?></strong></td>
                            <td><?php echo $p['start_time'] ? date('h:i A', strtotime($p['start_time'])) : '-'; ?></td>
                            <td><?php echo $p['end_time'] ? date('h:i A', strtotime($p['end_time'])) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>