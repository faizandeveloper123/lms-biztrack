<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'View Timetable';

$sel_class = (int) ($_GET['class_id'] ?? 0);

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

$periods = [];
$res = db_query("SELECT * FROM periods ORDER BY start_time, period_id");
while ($row = $res->fetch_assoc()) { $periods[] = $row; }

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

$grid = [];
if ($sel_class > 0) {
    $res = db_query("SELECT t.day, t.period_id, t.subject_id, sub.subject_name
                     FROM timetable t LEFT JOIN subjects sub ON t.subject_id=sub.subject_id
                     WHERE t.class_id=$sel_class");
    while ($row = $res->fetch_assoc()) { $grid[$row['day']][$row['period_id']] = $row; }
}

include __DIR__ . '/includes/header.php';
?>
<style>
.search-bar-student { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-eye"></i> View Timetable</h3>
        </div>

        <form method="get" action="view_teachers_timetable.php" class="search-bar-student">
            <div class="form-group col-md-4" style="margin-bottom:0;">
                <label>Class</label>
                <select name="class_id" class="form-control" required onchange="this.form.submit()">
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?php echo $c['class_id']; ?>" <?php echo $sel_class == $c['class_id'] ? 'selected' : ''; ?>><?php echo e($c['class_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php if ($sel_class > 0): ?>
        <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
            <table class="table table-bordered" style="width:100%; background:#fff; margin-bottom:0; font-size:12.5px;">
                <thead>
                    <tr>
                        <th style="min-width:90px;">Day</th>
                        <?php foreach ($periods as $p): ?>
                            <th style="min-width:120px;"><?php echo e($p['period_name']); ?><br><small style="color:#6B7280; font-weight:400;"><?php echo $p['start_time'] ? date('h:i A', strtotime($p['start_time'])) : ''; ?> - <?php echo $p['end_time'] ? date('h:i A', strtotime($p['end_time'])) : ''; ?></small></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($days as $day): ?>
                        <tr>
                            <td><strong><?php echo $day; ?></strong></td>
                            <?php foreach ($periods as $p): $cell = $grid[$day][$p['period_id']] ?? null; ?>
                                <td style="text-align:center;">
                                    <?php if ($cell): ?>
                                        <span style="background:#FFF7E0; color:#B45309; padding:6px 12px; border-radius:8px; font-weight:700; font-size:12px; display:inline-block;"><?php echo e($cell['subject_name'] ?? 'Subject'); ?></span>
                                    <?php else: ?>
                                        <span style="color:#D1D5DB;">—</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>