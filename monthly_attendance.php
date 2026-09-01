<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Monthly Attendance';

$sel_class = (int) ($_GET['class_id'] ?? 0);
$sel_month = (int) ($_GET['month'] ?? (int) date('m'));
$sel_year  = (int) ($_GET['year'] ?? (int) date('Y'));

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $sel_month, $sel_year);
$firstDay = "$sel_year-$sel_month-01";
$lastDay = "$sel_year-$sel_month-$daysInMonth";

$students = [];
if ($sel_class > 0) {
    $res = db_query("SELECT student_id, first_name, father_name, roll_no FROM students WHERE class_id=$sel_class AND status=1 ORDER BY first_name");
    while ($row = $res->fetch_assoc()) { $row['days'] = []; $students[$row['student_id']] = $row; }
} elseif (!$sel_class) {
    $res = db_query("SELECT student_id, first_name, father_name, roll_no, class_id FROM students WHERE status=1 ORDER BY class_id, first_name");
    while ($row = $res->fetch_assoc()) { $row['days'] = []; $students[$row['student_id']] = $row; }
}

$att_map = [];
if ($students) {
    $ids = implode(',', array_keys($students));
    $res = db_query("SELECT student_id, date, status FROM attendance WHERE date BETWEEN '$firstDay' AND '$lastDay' AND student_id IN ($ids)");
    while ($row = $res->fetch_assoc()) { $att_map[$row['student_id']][(int)date('j', strtotime($row['date']))] = $row['status']; }
}

$inClass = [];
if ($sel_class > 0) { $inClass[''] = 0; }

include __DIR__ . '/includes/header.php';
?>
<style>
.search-bar-student { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px; }
.att-cell { width:26px; height:26px; display:inline-flex; align-items:center; justify-content:center; font-size:10.5px; font-weight:700; border-radius:6px; }
.att-P { background:#DCFCE7; color:#16A34A; }
.att-A { background:#FEE2E2; color:#DC2626; }
.att-L { background:#DBEAFE; color:#377DFF; }
.att-Leave { background:#FFF7E0; color:#F59E0B; }
.att-- { background:#F3F4F6; color:#D1D5DB; }
.grade-banner { color:#F59E0B; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-calendar"></i> Monthly Attendance <span style="font-size:14px; color:#6B7280;">(<?php echo date('F Y', mktime(0,0,0,$sel_month,1,$sel_year)); ?>)</span></h3>
        </div>

        <form method="get" action="monthly_attendance.php" class="search-bar-student">
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>Class</label>
                <select name="class_id" class="form-control">
                    <option value="0">All Classes</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?php echo $c['class_id']; ?>" <?php echo $sel_class == $c['class_id'] ? 'selected' : ''; ?>><?php echo e($c['class_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <label>Month</label>
                <select name="month" class="form-control">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $sel_month == $m ? 'selected' : ''; ?>><?php echo date('M', mktime(0,0,0,$m,1)); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <label>Year</label>
                <select name="year" class="form-control">
                    <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $sel_year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-search"></i> Load</button>
            </div>
        </form>

        <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
            <table class="table table-bordered table-striped" style="width:100%; background:#fff; margin-bottom:0; font-size:11.5px;">
                <thead>
                    <tr>
                        <th style="min-width:40px;">GR</th>
                        <th style="min-width:150px; text-align:left;">Student</th>
                        <?php for ($d = 1; $d <= $daysInMonth; $d++): ?>
                            <th style="width:28px; text-align:center; padding:2px;"><?php echo $d; ?></th>
                        <?php endfor; ?>
                        <th style="min-width:130px; text-align:center;">Summary</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($students) === 0): ?>
                        <tr><td colspan="<?php echo $daysInMonth + 3; ?>" style="text-align:center; color:#6B7280; padding:30px;">No students found for the selected filters.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($students as $sid => $st): $pc = 0; $ac = 0; ?>
                        <tr>
                            <td><?php echo e($st['roll_no'] ?? $sid); ?></td>
                            <td style="text-align:left;"><strong><?php echo e($st['first_name']); ?></strong></td>
                            <?php for ($d = 1; $d <= $daysInMonth; $d++): $stt = $att_map[$sid][$d] ?? '-'; if ($stt === 'present') $pc++; if ($stt === 'absent') $ac++; ?>
                                <td style="text-align:center; padding:2px; border-color:#F3F4F6;">
                                    <span class="att-cell att-<?php echo $stt === 'present' ? 'P' : ($stt === 'absent' ? 'A' : ($stt === 'late' ? 'L' : ($stt === 'leave' ? 'Leave' : '-'))); ?>"><?php echo $stt === 'present' ? 'P' : ($stt === 'absent' ? 'A' : ($stt === 'late' ? 'L' : ($stt === 'leave' ? 'Lv' : ''))); ?></span>
                                </td>
                            <?php endfor; ?>
                            <td style="text-align:center;">
                                <span style="color:#16A34A; font-weight:800;"><?php echo $pc; ?>P</span> / <span style="color:#DC2626; font-weight:800;"><?php echo $ac; ?>A</span>
                                <span style="display:block; font-size:10.5px; color:#6B7280;"><?php echo $daysInMonth > 0 ? round(($pc / $daysInMonth) * 100) : 0; ?>%</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>