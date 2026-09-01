<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Class Promotion';

$message = '';
$error = '';

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'Promote') {
    $from_class = (int) ($_POST['from_class'] ?? 0);
    $to_class   = (int) ($_POST['to_class'] ?? 0);
    $sid_list   = $_POST['student_ids'] ?? [];

    if ($from_class <= 0 || $to_class <= 0 || $from_class === $to_class) {
        $error = 'Please select different from/to classes.';
    } elseif (count($sid_list) === 0) {
        $error = 'No students selected for promotion.';
    } else {
        $count = 0;
        foreach ($sid_list as $sid) {
            $sid = (int) $sid;
            if ($sid <= 0) continue;
            $up = db_prepare("UPDATE students SET class_id=? WHERE student_id=?");
            $up->bind_param('ii', $to_class, $sid);
            $up->execute();
            $count++;
        }
        $message = "$count students promoted successfully!";
    }
}

$sel_from = (int) ($_GET['from_class'] ?? 0);
$selected = [];
if ($sel_from > 0) {
    $res = db_query("SELECT s.student_id, s.first_name, s.father_name, c.class_name
                     FROM students s LEFT JOIN classes c ON s.class_id = c.class_id
                     WHERE s.class_id = $sel_from AND s.status = 1 ORDER BY s.first_name");
    while ($row = $res->fetch_assoc()) { $selected[] = $row; }
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
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-arrow-up"></i> Class Promotion</h3>
        </div>

        <form method="get" action="class_promotion.php" class="search-bar-student">
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>From Class</label>
                <select name="from_class" class="form-control" required="">
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?php echo $c['class_id']; ?>" <?php echo $sel_from == $c['class_id'] ? 'selected' : ''; ?>><?php echo e($c['class_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-search"></i> Load Students</button>
            </div>
        </form>

        <?php if ($sel_from > 0): ?>
        <form method="post" action="class_promotion.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px;">
            <input type="hidden" name="action" value="Promote">
            <input type="hidden" name="from_class" value="<?php echo $sel_from; ?>">
            <div class="form-group col-md-4" style="margin-bottom:14px;">
                <label class="required">Promote To Class</label>
                <select name="to_class" class="form-control" required="">
                    <option value="">Select Target Class</option>
                    <?php foreach ($classes as $c): if ($c['class_id'] === $sel_from) continue; ?>
                        <option value="<?php echo $c['class_id']; ?>"><?php echo e($c['class_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="overflow-x:auto; clear:both; margin-top:10px;">
                <table class="table table-striped table-bordered" style="width:100%; background:#fff;">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>GR. No</th>
                            <th>Student</th>
                            <th>Father Name</th>
                            <th>Current Class</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($selected) === 0): ?>
                            <tr><td colspan="5" style="text-align:center; color:#6B7280; padding:30px;">No students in this class.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($selected as $st): ?>
                            <tr>
                                <td><input type="checkbox" name="student_ids[]" value="<?php echo $st['student_id']; ?>" class="stu-check"></td>
                                <td><?php echo $st['student_id']; ?></td>
                                <td><strong><?php echo e($st['first_name']); ?></strong></td>
                                <td><?php echo e($st['father_name']); ?></td>
                                <td><?php echo e($st['class_name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-success" style="padding:10px 30px;"><i class="fa fa-arrow-up"></i> Promote Selected Students</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('selectAll').addEventListener('change', function(){
    document.querySelectorAll('.stu-check').forEach(function(c){ c.checked = this.checked; }.bind(this));
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>