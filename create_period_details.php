<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Class Period Details';

$message = '';
$error = '';

$class_id = (int) ($_GET['class_id'] ?? $_POST['class_id'] ?? 0);
if ($class_id <= 0) { header('Location: ' . BASE_URL . 'class_period_selection.php'); exit; }

$class = db_query("SELECT * FROM classes WHERE class_id=$class_id")->fetch_assoc();
if (!$class) { header('Location: ' . BASE_URL . 'class_period_selection.php'); exit; }

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

$sections = [];
$res = db_query("SELECT * FROM sections WHERE class_id=$class_id ORDER BY section_name");
while ($row = $res->fetch_assoc()) { $sections[] = $row; }

$subjects = [];
$res = db_query("SELECT * FROM subjects WHERE class_id=$class_id ORDER BY subject_name");
while ($row = $res->fetch_assoc()) { $subjects[] = $row; }

$teachers = [];
$res = db_query("SELECT emp_id, first_name, last_name FROM employees WHERE status=1 ORDER BY first_name");
while ($row = $res->fetch_assoc()) { $teachers[] = $row; }

$periods = [];
$res = db_query("SELECT * FROM periods ORDER BY start_time");
while ($row = $res->fetch_assoc()) { $periods[] = $row; }

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'SaveTimetable') {
    $day = trim($_POST['day'] ?? '');
    $period_id = (int) ($_POST['period_id'] ?? 0);
    $subject_id = (int) ($_POST['subject_id'] ?? 0);
    $teacher_id = (int) ($_POST['teacher_id'] ?? 0);
    $section_id = isset($_POST['section_id']) && $_POST['section_id'] !== '' ? (int)$_POST['section_id'] : null;
    if ($day === '' || $period_id <= 0) {
        $error = 'Day aur period select karein.';
    } else {
        $st2 = db_prepare("INSERT INTO timetable (class_id, section_id, day, period_id, subject_id, teacher_id) VALUES (?, ?, ?, ?, ?, ?)");
        $st2->bind_param('iisiii', $class_id, $section_id, $day, $period_id, $subject_id, $teacher_id);
        $st2->execute();
        $message = "Timetable entry added for $day!";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'DeleteTimetable') {
    $tid = (int) ($_POST['timetable_id'] ?? 0);
    if ($tid > 0) {
        $st2 = db_prepare("DELETE FROM timetable WHERE timetable_id=?");
        $st2->bind_param('i', $tid);
        $st2->execute();
        $message = 'Timetable entry deleted!';
    }
}

// Build timetable grid: day => period_id => entry
$grid = [];
$res = db_query("SELECT t.*, s.subject_name, p.period_name, p.start_time, p.end_time, e.first_name teacher_name, sec.section_name
    FROM timetable t
    LEFT JOIN subjects s ON t.subject_id=s.subject_id
    LEFT JOIN periods p ON t.period_id=p.period_id
    LEFT JOIN employees e ON t.teacher_id=e.emp_id
    LEFT JOIN sections sec ON t.section_id=sec.section_id
    WHERE t.class_id=$class_id ORDER BY t.day");
while ($row = $res->fetch_assoc()) { $grid[$row['day']][$row['period_id']] = $row; }

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
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-calendar"></i> Class Period Details — <?php echo e($class['class_name']); ?></h3>
            <a href="<?php echo BASE_URL; ?>view_class_period_selection.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-eye"></i> View Time Table</a>
        </div>

        <div class="row">
            <div class="col-md-4">
                <form method="post" action="create_period_details.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px;">
                    <h4 style="font-size:15px; font-weight:800; margin:0 0 12px;">Add Period Entry</h4>
                    <input type="hidden" name="action" value="SaveTimetable">
                    <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                    <div class="form-group">
                        <label class="required">Day</label>
                        <select name="day" class="form-control" required>
                            <option value="">Select Day</option>
                            <?php foreach ($days as $d): ?><option value="<?php echo $d; ?>"><?php echo $d; ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="required">Period</label>
                        <select name="period_id" class="form-control" required>
                            <option value="">Select Period</option>
                            <?php foreach ($periods as $p): ?><option value="<?php echo $p['period_id']; ?>"><?php echo e($p['period_name']) . ' (' . date('h:i A', strtotime($p['start_time'])) . ')'; ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Section</label>
                        <select name="section_id" class="form-control">
                            <option value="">None</option>
                            <?php foreach ($sections as $sec): ?><option value="<?php echo $sec['section_id']; ?>"><?php echo e($sec['section_name']); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <select name="subject_id" class="form-control">
                            <option value="">None</option>
                            <?php foreach ($subjects as $su): ?><option value="<?php echo $su['subject_id']; ?>"><?php echo e($su['subject_name']); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Teacher</label>
                        <select name="teacher_id" class="form-control">
                            <option value="">None</option>
                            <?php foreach ($teachers as $t): ?><option value="<?php echo $t['emp_id']; ?>"><?php echo e($t['first_name'] . ' ' . $t['last_name']); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success" style="width:100%;"><i class="fa fa-plus"></i> Add Entry</button>
                </form>
            </div>

            <div class="col-md-8">
                <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
                    <table class="table table-bordered" style="width:100%; background:#fff; margin-bottom:0; font-size:12px;">
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
                                                <form method="post" action="create_period_details.php" style="display:inline;">
                                                    <input type="hidden" name="action" value="DeleteTimetable">
                                                    <input type="hidden" name="timetable_id" value="<?php echo $cell['timetable_id']; ?>">
                                                    <button class="btn btn-danger btn-xs" style="margin-top:3px;"><i class="fa fa-trash"></i></button>
                                                </form>
                                            <?php else: ?>—<?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
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