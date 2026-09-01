<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Employee Attendance';

$message = '';
$error = '';

$date = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { $date = date('Y-m-d'); }

$emps = [];
$res = db_query("SELECT emp_id, first_name, last_name, designation FROM employees WHERE status=1 ORDER BY first_name");
while ($row = $res->fetch_assoc()) {
    $a = db_query("SELECT status FROM employee_attendance WHERE emp_id={$row['emp_id']} AND date='$date'")->fetch_assoc();
    $row['att_status'] = $a['status'] ?? '';
    $emps[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'MarkAttendance') {
    $date = trim($_POST['date'] ?? $date);
    $marks = $_POST['att'] ?? [];
    $saved = 0;
    $st2 = db_prepare("INSERT INTO employee_attendance (emp_id, date, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE status=VALUES(status)");
    foreach ($marks as $eid => $status) {
        $eid = (int) $eid;
        if ($status === '' || $eid <= 0) continue;
        $st2->bind_param('iss', $eid, $date, $status);
        $st2->execute();
        $saved++;
    }
    $message = "Attendance saved for $saved employees!";
}

include __DIR__ . '/includes/header.php';
?>
<style>
.search-bar-student { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px; }
.att-radio label { margin-right:14px; font-weight:600; cursor:pointer; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-calendar-check-o"></i> Employee Attendance</h3>
        </div>

        <form method="get" action="view_emp_attendance.php" class="search-bar-student">
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>Date</label>
                <input type="date" name="date" class="form-control" value="<?php echo e($date); ?>">
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-search"></i> Load</button>
            </div>
        </form>

        <form method="post" action="view_emp_attendance.php" style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
            <input type="hidden" name="action" value="MarkAttendance">
            <input type="hidden" name="date" value="<?php echo e($date); ?>">
            <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                <thead>
                    <tr><th>#</th><th>Employee</th><th>Designation</th><th style="width:320px; text-align:center;">Attendance</th></tr>
                </thead>
                <tbody>
                    <?php if (count($emps) === 0): ?>
                        <tr><td colspan="4" style="text-align:center; color:#6B7280; padding:30px;">No employees found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($emps as $em): $cur = $em['att_status']; ?>
                        <tr>
                            <td><?php echo $em['emp_id']; ?></td>
                            <td><strong><?php echo e($em['first_name']); ?> <?php echo e($em['last_name']); ?></strong></td>
                            <td><?php echo e($em['designation'] ?? '-'); ?></td>
                            <td class="att-radio" style="text-align:center;">
                                <?php foreach (['present' => 'P', 'absent' => 'A', 'late' => 'L', 'leave' => 'Lv'] as $key => $lbl): ?>
                                    <label><input type="radio" name="att[<?php echo $em['emp_id']; ?>]" value="<?php echo $key; ?>" <?php echo $cur === $key ? 'checked' : ''; ?>> <?php echo $lbl; ?></label>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="padding:14px; text-align:right;">
                <button type="submit" class="btn btn-success" style="padding:10px 30px;"><i class="fa fa-save"></i> Save Attendance</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>