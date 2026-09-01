<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Manage Exams';

$message = '';
$error = '';

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'AddExam') {
        $name = trim($_POST['exam_name'] ?? '');
        $class_id = (int) ($_POST['class_id'] ?? 0);
        $exam_date = trim($_POST['exam_date'] ?? '');
        if ($name === '' || $class_id <= 0) {
            $error = 'Exam name and class are required.';
        } else {
            $ed = $exam_date ?: null;
            $uid = $_SESSION['user_id'];
            $stmt = db_prepare("INSERT INTO exams (exam_name, class_id, exam_date, status) VALUES (?, ?, ?, 1)");
            $stmt->bind_param('sis', $name, $class_id, $ed);
            $stmt->execute();
            $message = 'Exam added successfully!';
        }
    }

    if ($action === 'DeleteExam') {
        $exam_id = (int) ($_POST['exam_id'] ?? 0);
        if ($exam_id > 0) {
            $stmt = db_prepare("DELETE FROM exams WHERE exam_id=?");
            $stmt->bind_param('i', $exam_id);
            $stmt->execute();
            $message = 'Exam deleted successfully!';
        }
    }
}

$exams = [];
$res = db_query("SELECT e.*, c.class_name FROM exams e LEFT JOIN classes c ON e.class_id=c.class_id ORDER BY e.exam_date DESC, e.exam_id DESC");
while ($row = $res->fetch_assoc()) { $exams[] = $row; }

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
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-pencil-square"></i> Manage Exams <span style="font-size:14px; color:#6B7280;">(<?php echo count($exams); ?> exams)</span></h3>
            <a href="<?php echo BASE_URL; ?>add_marks.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-edit"></i> Enter Marks</a>
        </div>

        <div class="row">
            <div class="col-md-4">
                <form method="post" action="manage_exams.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px;">
                    <h4 style="font-size:15px; font-weight:800; margin:0 0 14px;">Create New Exam</h4>
                    <input type="hidden" name="action" value="AddExam">
                    <div class="form-group">
                        <label class="required">Exam Name</label>
                        <input type="text" name="exam_name" class="form-control" placeholder="e.g. Mid Term" required>
                    </div>
                    <div class="form-group">
                        <label class="required">Class</label>
                        <select name="class_id" class="form-control" required>
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo $c['class_id']; ?>"><?php echo e($c['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Exam Date</label>
                        <input type="date" name="exam_date" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-success" style="width:100%;">Add Exam</button>
                </form>
            </div>

            <div class="col-md-8">
                <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
                    <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                        <thead>
                            <tr><th>#</th><th>Exam Name</th><th>Class</th><th>Date</th><th>Status</th><th style="width:150px;">Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if (count($exams) === 0): ?>
                                <tr><td colspan="6" style="text-align:center; color:#6B7280; padding:30px;">No exams created yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($exams as $x): ?>
                                <tr>
                                    <td><?php echo $x['exam_id']; ?></td>
                                    <td><strong><?php echo e($x['exam_name']); ?></strong></td>
                                    <td><?php echo e($x['class_name'] ?? '-'); ?></td>
                                    <td><?php echo $x['exam_date'] ? date('d M Y', strtotime($x['exam_date'])) : '-'; ?></td>
                                    <td><span class="status-badge status-paid">Active</span></td>
                                    <td>
                                        <a href="add_marks.php?exam_id=<?php echo $x['exam_id']; ?>" class="btn btn-primary btn-xs"><i class="fa fa-edit"></i> Marks</a>
                                        <form method="post" action="manage_exams.php" style="display:inline;" onsubmit="return confirm('Delete this exam?');">
                                            <input type="hidden" name="action" value="DeleteExam">
                                            <input type="hidden" name="exam_id" value="<?php echo $x['exam_id']; ?>">
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