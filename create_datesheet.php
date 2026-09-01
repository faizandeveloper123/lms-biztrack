<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Create Datesheet';

$message = '';
$error = '';

$exams = [];
$res = db_query("SELECT * FROM exams ORDER BY exam_id DESC");
while ($row = $res->fetch_assoc()) { $exams[] = $row; }

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

$sel_exam = (int) ($_GET['exam_id'] ?? 0);
$sel_class = (int) ($_GET['class_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'SaveDatesheet') {
    $class_id = (int)($_POST['class_id'] ?? 0);
    $exam_id = (int)($_POST['exam_id'] ?? 0);
    $entries = $_POST['ds'] ?? [];
    if ($class_id <= 0 || $exam_id <= 0) {
        $error = 'Exam aur class select karein.';
    } else {
        $prefix = "datesheet_{$exam_id}_{$class_id}_";
        $like = "datesheet_{$exam_id}_{$class_id}_%";
        $st2 = db_prepare("DELETE FROM settings WHERE setting_key LIKE ?");
        $st2->bind_param('s', $like);
        $st2->execute();
        $saved = 0;
        if (is_array($entries)) {
            foreach ($entries as $sub_id => $info) {
                $sub_id = (int)$sub_id;
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
        }
        $message = "Date sheet saved with $saved entries!";
    }
}

$subjects = [];
if ($sel_class > 0) {
    $res = db_query("SELECT * FROM subjects WHERE class_id=$sel_class ORDER BY subject_name");
    while ($row = $res->fetch_assoc()) { $subjects[] = $row; }
}

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
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-calendar"></i> Create Date Sheet</h3>
        </div>

        <form method="get" action="create_datesheet.php" class="search-bar-student">
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>Exam</label>
                <select name="exam_id" class="form-control" onchange="this.form.submit()">
                    <option value="">Select Exam</option>
                    <?php foreach ($exams as $ex): ?><option value="<?php echo $ex['exam_id']; ?>" <?php echo $sel_exam == $ex['exam_id'] ? 'selected' : ''; ?>><?php echo e($ex['exam_name']); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>Class</label>
                <select name="class_id" class="form-control" onchange="this.form.submit()">
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $cl): ?><option value="<?php echo $cl['class_id']; ?>" <?php echo $sel_class == $cl['class_id'] ? 'selected' : ''; ?>><?php echo e($cl['class_name']); ?></option><?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php if ($sel_class > 0): ?>
            <form method="post" action="create_datesheet.php" style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
                <input type="hidden" name="action" value="SaveDatesheet">
                <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
                <input type="hidden" name="exam_id" value="<?php echo $sel_exam; ?>">
                <table class="table table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                    <thead><tr><th>Subject</th><th>Date</th><th>Time</th></tr></thead>
                    <tbody>
                        <?php if (count($subjects) === 0): ?><tr><td colspan="3" style="text-align:center; color:#6B7280; padding:25px;">Is class ke liye koi subject nahi. Syllabus Management se add karein.</td></tr><?php endif; ?>
                        <?php foreach ($subjects as $sub): $sl = $cur_slots[$sub['subject_id']] ?? null; ?>
                            <tr>
                                <td><strong><?php echo e($sub['subject_name']); ?></strong> (<?php echo e($sub['subject_code']); ?>)</td>
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