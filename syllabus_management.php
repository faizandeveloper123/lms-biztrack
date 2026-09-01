<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Syllabus Management';

$message = '';
$error = '';

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

$sel_class = (int) ($_GET['class_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'AddSubject') {
        $name = trim($_POST['subject_name'] ?? '');
        $code = trim($_POST['subject_code'] ?? '');
        $class_id = (int) ($_POST['class_id'] ?? 0);
        if ($name === '' || $class_id <= 0) {
            $error = 'Subject name and class are required.';
        } else {
            $st2 = db_prepare("INSERT INTO subjects (class_id, subject_name, subject_code) VALUES (?, ?, ?)");
            $st2->bind_param('iss', $class_id, $name, $code);
            $st2->execute();
            $message = 'Subject added successfully!';
        }
    }

    if ($action === 'DeleteSubject') {
        $sid = (int) ($_POST['subject_id'] ?? 0);
        if ($sid > 0) {
            $st2 = db_prepare("DELETE FROM subjects WHERE subject_id=?");
            $st2->bind_param('i', $sid);
            $st2->execute();
            $message = 'Subject deleted successfully!';
        }
    }
}

$subjects = [];
if ($sel_class > 0) {
    $res = db_query("SELECT * FROM subjects WHERE class_id=$sel_class ORDER BY subject_name");
    while ($row = $res->fetch_assoc()) { $subjects[] = $row; }
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
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-book"></i> Syllabus Management / Subjects</h3>
        </div>

        <form method="get" action="syllabus_management.php" class="search-bar-student">
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

        <div class="row">
            <div class="col-md-4">
                <form method="post" action="syllabus_management.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px;">
                    <h4 style="font-size:15px; font-weight:800; margin:0 0 14px;">Add Subject</h4>
                    <input type="hidden" name="action" value="AddSubject">
                    <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
                    <div class="form-group">
                        <label class="required">Subject Name</label>
                        <input type="text" name="subject_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Subject Code</label>
                        <input type="text" name="subject_code" class="form-control" placeholder="e.g. MTH">
                    </div>
                    <div class="form-group">
                        <label>Class</label>
                        <select name="class_id" class="form-control" required>
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo $c['class_id']; ?>" <?php echo $sel_class == $c['class_id'] ? 'selected' : ''; ?>><?php echo e($c['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success" style="width:100%;">Add Subject</button>
                </form>
            </div>

            <div class="col-md-8">
                <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
                    <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                        <thead>
                            <tr><th>#</th><th>Subject</th><th>Code</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php if ($sel_class > 0 && count($subjects) === 0): ?>
                                <tr><td colspan="4" style="text-align:center; color:#6B7280; padding:25px;">No subjects for this class.</td></tr>
                            <?php elseif ($sel_class === 0): ?>
                                <tr><td colspan="4" style="text-align:center; color:#6B7280; padding:25px;">Pehle class select karein.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($subjects as $s): ?>
                                <tr>
                                    <td><?php echo $s['subject_id']; ?></td>
                                    <td><strong><?php echo e($s['subject_name']); ?></strong></td>
                                    <td><span class="status-badge" style="background:#E0E7FF; color:#4338CA;"><?php echo e($s['subject_code'] ?? '-'); ?></span></td>
                                    <td>
                                        <form method="post" action="syllabus_management.php" style="display:inline;" onsubmit="return confirm('Delete this subject?');">
                                            <input type="hidden" name="action" value="DeleteSubject">
                                            <input type="hidden" name="subject_id" value="<?php echo $s['subject_id']; ?>">
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

<?php include __DIR__ . '/includes/footer.php'; ?>