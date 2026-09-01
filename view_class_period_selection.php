<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'View Time Table';

$class_id = (int) ($_GET['class_id'] ?? 0);

$classes = [];
$res = db_query("SELECT c.class_id, c.class_name, (SELECT COUNT(*) FROM timetable t WHERE t.class_id=c.class_id) cnt FROM classes c WHERE c.status=1 ORDER BY c.class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

$periods = [];
$res = db_query("SELECT * FROM periods ORDER BY start_time");
while ($row = $res->fetch_assoc()) { $periods[] = $row; }

$grid = [];
if ($class_id > 0) {
    $res = db_query("SELECT t.*, s.subject_name, p.period_name, p.start_time, e.first_name teacher_name, sec.section_name
        FROM timetable t
        LEFT JOIN subjects s ON t.subject_id=s.subject_id
        LEFT JOIN periods p ON t.period_id=p.period_id
        LEFT JOIN employees e ON t.teacher_id=e.emp_id
        LEFT JOIN sections sec ON t.section_id=sec.section_id
        WHERE t.class_id=$class_id ORDER BY t.day");
    while ($row = $res->fetch_assoc()) { $grid[$row['day']][$row['period_id']] = $row; }
    $class = db_query("SELECT class_name FROM classes WHERE class_id=$class_id")->fetch_assoc();
}

include __DIR__ . '/includes/header.php';
?>
<style>
.search-bar-student { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-calendar"></i> View Time Table</h3>
            <a href="<?php echo BASE_URL; ?>class_period_selection.php" class="btn btn-success" style="color:#fff;"><i class="fa fa-plus"></i> Create Time Table</a>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:10px; margin-bottom:16px;">
            <?php foreach ($classes as $c): ?>
                <a href="<?php echo BASE_URL; ?>view_class_period_selection.php?class_id=<?php echo $c['class_id']; ?>"
                   style="background:#fff; border:2px solid <?php echo $class_id == $c['class_id'] ? '#FF7A1B' : '#E5E7EB'; ?>; border-radius:12px; padding:12px 14px; text-decoration:none; color:#111827; display:block;">
                    <div style="font-weight:800; font-size:14px;"><?php echo e($c['class_name']); ?></div>
                    <div style="font-size:12px; color:#6B7280;"><?php echo $c['cnt']; ?> entries</div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($class_id > 0): ?>
            <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:4px;">
                <div style="padding:12px 14px; font-weight:800; color:#111827; font-size:15px;"><?php echo e($class['class_name'] ?? ''); ?> — Class Time Table</div>
                <table class="table table-bordered" style="width:100%; background:#fff; margin-bottom:0; font-size:12.5px;">
                    <thead><tr><th>Day / Period</th><?php foreach ($periods as $p): ?><th><?php echo e($p['period_name']); ?><br><small style="color:#6B7280; font-weight:400;"><?php echo date('h:i A', strtotime($p['start_time'])); ?></small></th><?php endforeach; ?></tr></thead>
                    <tbody>
                        <?php foreach ($days as $d): ?>
                            <tr>
                                <th style="background:#F9FAFB;"><?php echo $d; ?></th>
                                <?php foreach ($periods as $p): $cell = $grid[$d][$p['period_id']] ?? null; ?>
                                    <td style="color:#6B7280;">
                                        <?php if ($cell): ?>
                                            <strong style="color:#111827;"><?php echo e($cell['subject_name'] ?: '-'); ?></strong><br>
                                            <small><?php echo e($cell['teacher_name'] ?: ''); ?> <?php echo $cell['section_name'] ? '(' . e($cell['section_name']) . ')' : ''; ?></small>
                                        <?php else: ?>—<?php endif; ?>
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