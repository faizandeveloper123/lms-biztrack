<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Mark Attendance';

$message = '';
$error = '';

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

$sel_class = (int) ($_GET['class_id'] ?? 0);
$sel_section = (int) ($_GET['section'] ?? 0);
$sel_date = $_GET['date'] ?? date('Y-m-d');

$students = [];
if ($sel_class > 0) {
    $sql = "SELECT s.* FROM students s WHERE s.status=1 AND s.class_id=?";
    $params = [$sel_class]; $types = 'i';
    if ($sel_section > 0) {
        $sql .= " AND s.section_id=?";
        $params[] = $sel_section; $types .= 'i';
    }
    $sql .= " ORDER BY s.roll_no, s.first_name";
    $stmt = db_prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $students[] = $row; }

    // Get existing attendance
    foreach ($students as &$st) {
        $st['att_status'] = '';
        $as = db_prepare("SELECT status FROM attendance WHERE student_id=? AND date=?");
        $as->bind_param('is', $st['student_id'], $sel_date);
        $as->execute();
        $ar = $as->get_result()->fetch_assoc();
        if ($ar) $st['att_status'] = $ar['status'];
    }
    unset($st);
}

// Save attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'MarkAttendance') {
    $date = $_POST['date'] ?? date('Y-m-d');
    $att = $_POST['att'] ?? [];
    $count = 0;
    foreach ($att as $sid => $status) {
        $sid = (int) $sid;
        if ($sid <= 0 || !in_array($status, ['present','absent','late','leave'])) continue;
        $existing = db_prepare("SELECT attendance_id FROM attendance WHERE student_id=? AND date=?");
        $existing->bind_param('is', $sid, $date);
        $existing->execute();
        if ($existing->get_result()->num_rows > 0) {
            $up = db_prepare("UPDATE attendance SET status=?, marked_by=? WHERE student_id=? AND date=?");
            $uid = $_SESSION['user_id']; $up->bind_param('siis', $status, $uid, $sid, $date); $up->execute();
        } else {
            $ins = db_prepare("INSERT INTO attendance (student_id, date, status, marked_by) VALUES (?,?,?,?)");
            $uid = $_SESSION['user_id']; $ins->bind_param('issi', $sid, $date, $status, $uid); $ins->execute();
        }
        $count++;
    }
    $message = "$count attendance records saved for $date!";
    // Refresh
    header('Location: mark_attend.php?class_id=' . $sel_class . '&section=' . $sel_section . '&date=' . $date . '&saved=1');
    exit;
}

// Section options for selected class
$sections = [];
if ($sel_class > 0) {
    $res = db_query("SELECT section_id, section_name FROM sections WHERE class_id=$sel_class");
    while ($row = $res->fetch_assoc()) { $sections[] = $row; }
}

include __DIR__ . '/includes/header.php';
?>
<style>
.attendance-radio { display:flex; gap:8px; flex-wrap:wrap; }
.att-option { padding: 6px 12px; border-radius: 8px; border: 1px solid #E5E7EB; cursor: pointer; font-size: 12px; font-weight: 600; background: #fff; color: #6B7280; transition: all .15s; }
.att-option.active, .att-option input:checked + .att-option { background: #22C55E; border-color: #22C55E; color: #fff; }
.att-option input { display: none; }
.att-option.absent { color:#DC2626; } .att-option.absent.active { background:#DC2626; border-color:#DC2626; color:#fff; }
.att-option.late { color:#377DFF; } .att-option.late.active { background:#377DFF; border-color:#377DFF; color:#fff; }
.att-option.leave { color:#F59E0B; } .att-option.leave.active { background:#F59E0B; border-color:#F59E0B; color:#fff; }
.att-option.selected { color:#fff; }
</style>

<div class="main-content">
    <div class="container-fluid">

        <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Attendance saved successfully!</div><?php endif; ?>
        <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>

        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-clipboard-check"></i> Mark Attendance <span style="font-size:14px; color:#6B7280;">(<?php echo date('d M, Y', strtotime($sel_date)); ?>)</span></h3>
            <a href="<?php echo BASE_URL; ?>mark_attendanceReport_list.php" class="btn btn-info" style="color:#fff;"><i class="fa fa-bar-chart"></i> Attendance Analytics</a>
        </div>

        <form method="get" action="mark_attend.php" class="search-bar-student" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px;">
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label class="required">Class</label>
                <select name="class_id" id="mk_class" class="form-control" required="">
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?php echo $c['class_id']; ?>" <?php echo $sel_class == $c['class_id'] ? 'selected' : ''; ?>><?php echo e($c['class_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>Section</label>
                <select name="section" id="mk_section" class="form-control">
                    <option value="">All Sections</option>
                    <?php foreach ($sections as $sec): ?>
                        <option value="<?php echo $sec['section_id']; ?>" <?php echo $sel_section == $sec['section_id'] ? 'selected' : ''; ?>><?php echo e($sec['section_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>Date</label>
                <input type="date" name="date" class="form-control" value="<?php echo e($sel_date); ?>">
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-search"></i> Load</button>
            </div>
        </form>

        <?php if ($sel_class > 0): ?>
        <form method="post" action="mark_attend.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px;">
            <input type="hidden" name="action" value="MarkAttendance">
            <input type="hidden" name="date" value="<?php echo e($sel_date); ?>">
            <div style="overflow-x:auto;">
                <table id="listofstudents" class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:10px;">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>GR. No</th>
                            <th>Student / Father Name</th>
                            <th>Section</th>
                            <th>Attendance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($students) === 0): ?>
                            <tr><td colspan="5" style="text-align:center; color:#6B7280; padding:30px;">No students found in selected class/section.</td></tr>
                        <?php endif; ?>
                        <?php $i = 1; foreach ($students as $st): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo e($st['roll_no'] ?? $st['student_id']); ?></td>
                                <td>
                                    <strong><?php echo e($st['first_name']); ?></strong>
                                    <div style="font-size:11px; color:#6B7280;"><?php echo e($st['father_name'] ?? $st['last_name']); ?></div>
                                </td>
                                <td>-</td>
                                <td>
                                    <div class="attendance-radio">
                                        <?php $cur = $st['att_status']; ?>
                                        <label class="att-option <?php echo $cur === 'present' ? 'active selected' : ''; ?>" style="<?php echo $cur === 'present' ? 'background:#22C55E;border-color:#22C55E;' : ''; ?>">
                                            <input type="radio" name="att[<?php echo $st['student_id']; ?>]" value="present" <?php echo $cur === 'present' ? 'checked' : ''; ?>> P
                                        </label>
                                        <label class="att-option absent <?php echo $cur === 'absent' ? 'active selected' : ''; ?>" style="<?php echo $cur === 'absent' ? 'background:#DC2626;border-color:#DC2626;color:#fff;' : ''; ?>">
                                            <input type="radio" name="att[<?php echo $st['student_id']; ?>]" value="absent" <?php echo $cur === 'absent' ? 'checked' : ''; ?>> A
                                        </label>
                                        <label class="att-option late <?php echo $cur === 'late' ? 'active selected' : ''; ?>" style="<?php echo $cur === 'late' ? 'background:#377DFF;border-color:#377DFF;color:#fff;' : ''; ?>">
                                            <input type="radio" name="att[<?php echo $st['student_id']; ?>]" value="late" <?php echo $cur === 'late' ? 'checked' : ''; ?>> L
                                        </label>
                                        <label class="att-option leave <?php echo $cur === 'leave' ? 'active selected' : ''; ?>" style="<?php echo $cur === 'leave' ? 'background:#F59E0B;border-color:#F59E0B;color:#fff;' : ''; ?>">
                                            <input type="radio" name="att[<?php echo $st['student_id']; ?>]" value="leave" <?php echo $cur === 'leave' ? 'checked' : ''; ?>> Leave
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" name="submit" class="btn btn-success" style="padding:10px 30px;"><i class="fa fa-check"></i> Save Attendance</button>
        </form>
        <?php endif; ?>

    </div>
</div>

<script>
document.getElementById('mk_class').addEventListener('change', function(){
    var cid = this.value;
    var sel = document.getElementById('mk_section');
    sel.innerHTML = '<option value="">All Sections</option>';
    if (!cid) return;
    fetch('ajax_get_sections.php?class_id=' + cid)
        .then(function(r){ return r.json(); })
        .then(function(data){
            data.forEach(function(s){
                var o = document.createElement('option');
                o.value = s.section_id; o.textContent = s.section_name;
                sel.appendChild(o);
            });
        });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>