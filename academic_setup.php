<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Manage Academics';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'AddClass') {
        $name = trim($_POST['class_name'] ?? '');
        if ($name === '') {
            $error = 'Class name is required.';
        } else {
            $st2 = db_prepare("INSERT INTO classes (class_name) VALUES (?)");
            $st2->bind_param('s', $name);
            $st2->execute();
            $message = "Class '$name' added!";
        }
    }

    if ($action === 'AddSection') {
        $class_id = (int) ($_POST['class_id'] ?? 0);
        $sec = trim($_POST['section_name'] ?? '');
        if ($class_id <= 0 || $sec === '') {
            $error = 'Class and section name are required.';
        } else {
            $st2 = db_prepare("INSERT INTO sections (class_id, section_name) VALUES (?, ?)");
            $st2->bind_param('is', $class_id, $sec);
            $st2->execute();
            $message = "Section '$sec' added!";
        }
    }
}

$classes = [];
$res = db_query("SELECT * FROM classes ORDER BY class_id");
while ($row = $res->fetch_assoc()) {
    $sections = [];
    $res2 = db_query("SELECT * FROM sections WHERE class_id={$row['class_id']} ORDER BY section_id");
    while ($s = $res2->fetch_assoc()) { $sections[] = $s; }
    $subjects_count = (int) (db_query("SELECT COUNT(*) c FROM subjects WHERE class_id={$row['class_id']}")->fetch_assoc()['c'] ?? 0);
    $students_count = (int) (db_query("SELECT COUNT(*) c FROM students WHERE class_id={$row['class_id']} AND status=1")->fetch_assoc()['c'] ?? 0);
    $row['sections'] = $sections;
    $row['subjects_count'] = $subjects_count;
    $row['students_count'] = $students_count;
    $classes[] = $row;
}

include __DIR__ . '/includes/header.php';
?>
<style>
.ac-card { background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:14px; }
.ac-head { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:10px; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-university"></i> Manage Academics</h3>
        </div>

        <div class="row" style="margin-bottom:16px;">
            <div class="col-md-5">
                <form method="post" action="academic_setup.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px;">
                    <h4 style="font-size:15px; font-weight:800; margin:0 0 12px;">Add Class</h4>
                    <input type="hidden" name="action" value="AddClass">
                    <div class="form-group"><input type="text" name="class_name" class="form-control" placeholder="e.g. MIT, BSCS, BBA" required></div>
                    <button class="btn btn-success" style="width:100%;"><i class="fa fa-plus"></i> Add Class</button>
                </form>
            </div>
            <div class="col-md-7">
                <form method="post" action="academic_setup.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px;">
                    <h4 style="font-size:15px; font-weight:800; margin:0 0 12px;">Add Section</h4>
                    <input type="hidden" name="action" value="AddSection">
                    <div class="row">
                        <div class="form-group col-md-5">
                            <select name="class_id" class="form-control" required>
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $c): ?><option value="<?php echo $c['class_id']; ?>"><?php echo e($c['class_name']); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-4"><input type="text" name="section_name" class="form-control" placeholder="e.g. A, B" required></div>
                        <div class="form-group col-md-3"><button class="btn btn-primary" style="width:100%;"><i class="fa fa-plus"></i> Add</button></div>
                    </div>
                </form>
            </div>
        </div>

        <?php foreach ($classes as $c): ?>
            <div class="ac-card">
                <div class="ac-head">
                    <div>
                        <strong style="font-size:15px; color:#111827;"><?php echo e($c['class_name']); ?></strong>
                        <span class="status-badge status-present" style="margin-left:8px;"><?php echo $c['students_count']; ?> students</span>
                        <span class="status-badge" style="background:#E0E7FF; color:#4338CA; margin-left:6px;"><?php echo $c['subjects_count']; ?> subjects</span>
                    </div>
                    <div style="color:#6B7280; font-size:12.5px;">
                        Sections:
                        <?php if (count($c['sections']) === 0): ?>-<?php endif; ?>
                        <?php foreach ($c['sections'] as $s): ?><span class="status-badge" style="background:#F3F4F6; color:#374151;"><?php echo e($s['section_name']); ?></span> <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (count($classes) === 0): ?>
            <div style="text-align:center; color:#6B7280; padding:40px;">No classes yet. Upar form se add karein.</div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>