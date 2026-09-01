<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Create Time Table';

$message = '';
$error = '';

$classes = [];
$res = db_query("SELECT c.class_id, c.class_name, (SELECT COUNT(*) FROM timetable t WHERE t.class_id=c.class_id) cnt FROM classes c WHERE c.status=1 ORDER BY c.class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

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
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-calendar"></i> Create Time Table</h3>
        </div>

        <?php foreach ($classes as $c): ?>
            <a href="<?php echo BASE_URL; ?>create_period_details.php?class_id=<?php echo $c['class_id']; ?>"
               style="display:flex; justify-content:space-between; align-items:center; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px 18px; margin-bottom:10px; text-decoration:none; color:#111827;">
                <div>
                    <div style="font-weight:800; font-size:15px;"><?php echo e($c['class_name']); ?></div>
                    <div style="font-size:12.5px; color:#6B7280;"><?php echo $c['cnt']; ?> timetable entries</div>
                </div>
                <div class="btn btn-primary" style="color:#fff;"><i class="fa fa-plus"></i> Select</div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>