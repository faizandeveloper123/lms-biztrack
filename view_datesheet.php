<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Date Sheet';

$message = '';
$error = '';

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

$subjects_all = [];
$res = db_query("SELECT s.*, c.class_name FROM subjects s JOIN classes c ON s.class_id=c.class_id ORDER BY s.subject_name");
while ($row = $res->fetch_assoc()) { $subjects_all[] = $row; }

$sel_class = (int) ($_GET['class_id'] ?? 0);
$sel_exam = (int) ($_GET['exam_id'] ?? 0);

$exams = [];
$res = db_query("SELECT e.*, c.class_name FROM exams e LEFT JOIN classes c ON e.class_id=c.class_id ORDER BY e.exam_id DESC");
while ($row = $res->fetch_assoc()) { $exams[] = $row; }

$slots = [];
$messages2 = [];
$exams_by_id = [];
foreach ($exams as $x) { $exams_by_id[$x['exam_id']] = $x; }

// Store datesheet in a session-friendly array -> use settings table with prefix
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'SaveDatesheet') {
    $class_id = (int) ($_POST['class_id'] ?? 0);
    $exam_id = (int) ($_POST['exam_id'] ?? 0);
    $entries = $_POST['ds'] ?? []; // [subject_id] => {date, time}
    if ($class_id <= 0 || $exam_id <= 0) {
        $error = 'Select class and exam.';
    } else {
        $prefix = "datesheet_{$exam_id}_{$class_id}_";
        $st2 = db_prepare("DELETE FROM settings WHERE setting_key LIKE ?");
        $like = "$prefix%";
        $st2->bind_param('s', $like);
        $st2->execute();
        $saved = 0;
        foreach ($entries as $sub_id => $info) {
            $sub_id = (int) $sub_id;
            $date = trim($info['date'] ?? '');
            $time = trim($info['time'] ?? '');
            if ($sub_id <= 0 || $date === '') continue;
            $key = $prefix . $sub_id;
            $val = "$date|$time";
            $st3 = db_prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $st3->bind_param('ss', $key, $val);
            $st3->execute();
            $saved++;
        }
        $message = "Date sheet saved with $saved entries!";
    }
}

// Load existing slots
$cur_slots = [];
if ($sel_class > 0 && $sel_exam > 0) {
    $prefix = "datesheet_{$sel_exam}_{$sel_class}_";
    $res = db_query("SELECT * FROM settings WHERE setting_key LIKE 'datesheet_%'");
    while ($row = $res->fetch_assoc()) {
        if (strpos($row['setting_key'], $prefix) !== 0) continue;
        $sid = (int) substr($row['setting_key'], strlen($prefix));
        $parts = explode('|', $row['setting_value']);
        $cur_slots[$sid] = ['date' => $parts[0], 'time' => $parts[1] ?? ''];
    }
}

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
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-calendar"></i> Date Sheet</h3>
            <a href="<?php echo BASE_URL; ?>view_datesheet.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-eye"></i> View Date Sheet</a>
        </div>

        <form method="get" action="view_datesheet.php" class="search-bar-student">
            <div class="form-group col-md-4" style="margin-bottom:0;">
                <label>Exam</label>
                <select name="exam_id" class="form-control">
                    <option value="">Select Exam</option>
                    <?php foreach ($exams as $x): ?>
                        <option value="<?php echo $x['exam_id']; ?>" <?php echo $sel_exam == $x['exam_id'] ? 'selected' : ''; ?>><?php echo e($x['exam_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>Class</label>
                <select name="class_id" class="form-control">
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?php echo $c['class_id']; ?>" <?php echo $sel_class == $c['class_id'] ? 'selected' : ''; ?>><?php echo e($c['class_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-search"></i> Load</button>
            </div>
        </form>

        <?php if ($sel_class > 0): $these_subjects = array_values(array_filter($subjects_all, function($s) use ($sel_class) { return $s['class_id'] == $sel_class; })); ?>
        <form method="post" action="view_datesheet.php" style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
            <input type="hidden" name="action" value="SaveDatesheet">
            <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
            <input type="hidden" name="exam_id" value="<?php echo $sel_exam; ?>">
            <table class="table table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                <thead>
                    <tr><th>Subject</th><th>Date</th><th>Time</th></tr>
                </thead>
                <tbody>
                    <?php if (count($these_subjects) === 0): ?>
                        <tr><td colspan="3" style="text-align:center; color:#6B7280; padding:25px;">No subjects for this class. Pehle subjects add karein.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($these_subjects as $sub): $sl = $cur_slots[$sub['subject_id']] ?? null; ?>
                        <tr>
                            <td><strong><?php echo e($sub['subject_name']); ?> (<?php echo e($sub['subject_code']); ?>)</strong></td>
                            <td><input type="date" name="ds[<?php echo $sub['subject_id']; ?>][date]" class="form-control" value="<?php echo e($sl['date'] ?? ''); ?>"></td>
                            <td><input type="text" name="ds[<?php echo $sub['subject_id']; ?>][time]" class="form-control" placeholder="09:00 AM" value="<?php echo e($sl['time'] ?? ''); ?>"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="padding:14px; text-align:right;">
                <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Save Date Sheet</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>