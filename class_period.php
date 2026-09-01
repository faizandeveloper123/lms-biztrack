<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Class Timetable';

$message = '';
$error = '';

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

$sel_class = (int) ($_GET['class_id'] ?? 0);

$periods = [];
$res = db_query("SELECT * FROM periods ORDER BY start_time, period_id");
while ($row = $res->fetch_assoc()) { $periods[] = $row; }

$subjects = [];
if ($sel_class > 0) {
    $res = db_query("SELECT * FROM subjects WHERE class_id=$sel_class ORDER BY subject_name");
    while ($row = $res->fetch_assoc()) { $subjects[] = $row; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'SavePeriod') {
    $name = trim($_POST['period_name'] ?? '');
    $start = trim($_POST['start_time'] ?? '');
    $end = trim($_POST['end_time'] ?? '');
    if ($name === '') {
        $error = 'Period name is required.';
    } else {
        $st2 = db_prepare("INSERT INTO periods (period_name, start_time, end_time) VALUES (?, ?, ?)");
        $st2->bind_param('sss', $name, $start, $end);
        $st2->execute();
        $message = 'Period added successfully!';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'DeletePeriod') {
    $pid = (int) ($_POST['period_id'] ?? 0);
    if ($pid > 0) {
        $st2 = db_prepare("DELETE FROM periods WHERE period_id=?");
        $st2->bind_param('i', $pid);
        $st2->execute();
        $message = 'Period deleted.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'SaveTimetable') {
    $class_id = (int) ($_POST['class_id'] ?? 0);
    if ($class_id <= 0) {
        $error = 'Please select a class.';
    } else {
        $items = $_POST['tt'] ?? []; // [day][period_id] => subject_id
        $st2 = db_prepare("DELETE FROM timetable WHERE class_id=?");
        $st2->bind_param('i', $class_id);
        $st2->execute();
        $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        $inserted = 0;
        $tstmt = db_prepare("INSERT INTO timetable (class_id, day, period_id, subject_id) VALUES (?, ?, ?, ?)");
        foreach ($items as $day => $periods_arr) {
            if (!in_array($day, $days)) continue;
            foreach ($periods_arr as $pid => $sub_id) {
                $pid = (int) $pid; $sub_id = (int) $sub_id;
                if ($pid <= 0 || $sub_id <= 0) continue;
                $tstmt->bind_param('isii', $class_id, $day, $pid, $sub_id);
                $tstmt->execute();
                $inserted++;
            }
        }
        $message = "Timetable saved with $inserted entries!";
    }
}

// Build grid
$grid = [];
if ($sel_class > 0) {
    $res = db_query("SELECT * FROM timetable WHERE class_id=$sel_class");
    while ($row = $res->fetch_assoc()) { $grid[$row['day']][$row['period_id']] = $row['subject_id']; }
}

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

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
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-calendar-check-o"></i> Class Timetable</h3>
            <a href="<?php echo BASE_URL; ?>view_teachers_timetable.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-eye"></i> View Timetable</a>
        </div>

        <form method="get" action="class_period.php" class="search-bar-student">
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
                <form method="post" action="class_period.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px;">
                    <h4 style="font-size:15px; font-weight:800; margin:0 0 14px;">Add Period</h4>
                    <input type="hidden" name="action" value="SavePeriod">
                    <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
                    <div class="form-group">
                        <label class="required">Period Name</label>
                        <input type="text" name="period_name" class="form-control" placeholder="e.g. Period 1" required>
                    </div>
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" name="start_time" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="time" name="end_time" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-success" style="width:100%;">Add Period</button>
                </form>

                <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px; margin-top:14px;">
                    <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                        <thead><tr><th>Period</th><th>Time</th><th></th></tr></thead>
                        <tbody>
                            <?php if (count($periods) === 0): ?><tr><td colspan="3" style="text-align:center; color:#6B7280;">No periods yet.</td></tr><?php endif; ?>
                            <?php foreach ($periods as $p): ?>
                                <tr>
                                    <td><strong><?php echo e($p['period_name']); ?></strong></td>
                                    <td><?php echo $p['start_time'] ? date('h:i A', strtotime($p['start_time'])) : '-'; ?> - <?php echo $p['end_time'] ? date('h:i A', strtotime($p['end_time'])) : '-'; ?></td>
                                    <td>
                                        <form method="post" action="class_period.php" style="display:inline;" onsubmit="return confirm('Delete period?');">
                                            <input type="hidden" name="action" value="DeletePeriod">
                                            <input type="hidden" name="period_id" value="<?php echo $p['period_id']; ?>">
                                            <button class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-8">
                <?php if ($sel_class > 0): ?>
                <form method="post" action="class_period.php" style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
                    <input type="hidden" name="action" value="SaveTimetable">
                    <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
                    <table class="table table-bordered" style="width:100%; background:#fff; margin-bottom:0; font-size:12.5px;">
                        <thead>
                            <tr>
                                <th style="min-width:90px;">Day</th>
                                <?php foreach ($periods as $p): ?>
                                    <th style="min-width:110px;"><?php echo e($p['period_name']); ?><br><small style="color:#6B7280; font-weight:400;"><?php echo $p['start_time'] ? date('h:iA', strtotime($p['start_time'])) : ''; ?></small></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($days as $day): ?>
                                <tr>
                                    <td><strong><?php echo $day; ?></strong></td>
                                    <?php foreach ($periods as $p): ?>
                                        <td>
                                            <select class="form-control" name="tt[<?php echo $day; ?>][<?php echo $p['period_id']; ?>]" style="padding:4px; font-size:12px;">
                                                <option value="0">—</option>
                                                <?php foreach ($subjects as $sub): ?>
                                                    <option value="<?php echo $sub['subject_id']; ?>" <?php echo ($grid[$day][$p['period_id']] ?? 0) == $sub['subject_id'] ? 'selected' : ''; ?>><?php echo e($sub['subject_name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div style="padding:14px; text-align:right;">
                        <button type="submit" class="btn btn-success" style="padding:10px 30px;"><i class="fa fa-save"></i> Save Timetable</button>
                    </div>
                </form>
                <?php else: ?>
                    <div class="alert alert-info" style="margin-top:0;">Pehle class select karein timetable banane ke liye.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>