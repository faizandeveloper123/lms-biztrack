<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Attendance Analytics';

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

$sel_class = (int) ($_GET['class_id'] ?? 0);
$sel_month = (int) ($_GET['month'] ?? (int) date('m'));
$sel_year  = (int) ($_GET['year'] ?? (int) date('Y'));

$report = [];
$summary = ['present' => 0, 'absent' => 0, 'late' => 0, 'leave' => 0, 'total' => 0];

$base = "SELECT a.status, s.first_name, s.student_id, s.roll_no, c.class_name FROM attendance a
         JOIN students s ON a.student_id = s.student_id
         LEFT JOIN classes c ON s.class_id = c.class_id
         WHERE MONTH(a.date) = $sel_month AND YEAR(a.date) = $sel_year";

$rows = [];
if ($sel_class > 0) {
    $res = db_query("$base AND s.class_id = $sel_class ORDER BY s.first_name");
    while ($r = $res->fetch_assoc()) { $rows[] = $r; $summary[$r['status']]++; $summary['total']++; }
}

// Group by student
$byStudent = [];
foreach ($rows as $r) {
    if (!isset($byStudent[$r['student_id']])) {
        $byStudent[$r['student_id']] = [
            'name' => $r['first_name'],
            'roll' => $r['roll_no'],
            'class' => $r['class_name'],
            'present' => 0, 'absent' => 0, 'late' => 0, 'leave' => 0, 'total' => 0
        ];
    }
    $byStudent[$r['student_id']][$r['status']]++;
    $byStudent[$r['student_id']]['total']++;
}

$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $sel_month, $sel_year);

include __DIR__ . '/includes/header.php';
?>
<style>
.search-bar-student { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px; }
.analytics-cards { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:16px; }
.analytics-cards .ac { background:#fff; border:1px solid #E5E7EB; border-radius:12px; padding:14px; text-align:center; }
.analytics-cards .ac .n { font-size:22px; font-weight:800; }
.analytics-cards .ac .l { font-size:11.5px; color:#6B7280; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-bar-chart"></i> Attendance Analytics <span style="font-size:14px; color:#6B7280;">(<?php echo date('F Y', mktime(0,0,0,$sel_month,1,$sel_year)); ?>)</span></h3>
        </div>

        <form method="get" action="mark_attendanceReport_list.php" class="search-bar-student">
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>Class</label>
                <select name="class_id" class="form-control">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?php echo $c['class_id']; ?>" <?php echo $sel_class == $c['class_id'] ? 'selected' : ''; ?>><?php echo e($c['class_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <label>Month</label>
                <select name="month" class="form-control">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $sel_month == $m ? 'selected' : ''; ?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
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

        <div class="analytics-cards">
            <div class="ac"><div class="n" style="color:#16A34A;"><?php echo $summary['present']; ?></div><div class="l">Present</div></div>
            <div class="ac"><div class="n" style="color:#DC2626;"><?php echo $summary['absent']; ?></div><div class="l">Absent</div></div>
            <div class="ac"><div class="n" style="color:#377DFF;"><?php echo $summary['late']; ?></div><div class="l">Late</div></div>
            <div class="ac"><div class="n" style="color:#F59E0B;"><?php echo $summary['leave']; ?></div><div class="l">Leave</div></div>
            <div class="ac"><div class="n" style="color:#374151;"><?php echo $summary['total']; ?></div><div class="l">Total Records</div></div>
        </div>

        <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
            <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                <thead>
                    <tr>
                        <th>GR. No</th>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Late</th>
                        <th>Leave</th>
                        <th>Total</th>
                        <th>Attendance %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($byStudent) === 0): ?>
                        <tr><td colspan="9" style="text-align:center; color:#6B7280; padding:30px;">No attendance records found for selected filters.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($byStudent as $sid => $st): $pct = $st['total'] > 0 ? round(($st['present'] / $st['total']) * 100) : 0; ?>
                        <tr>
                            <td><?php echo e($st['roll'] ?? $sid); ?></td>
                            <td><strong><?php echo e($st['name']); ?></strong></td>
                            <td><?php echo e($st['class']); ?></td>
                            <td style="color:#16A34A; font-weight:700;"><?php echo $st['present']; ?></td>
                            <td style="color:#DC2626; font-weight:700;"><?php echo $st['absent']; ?></td>
                            <td style="color:#377DFF; font-weight:700;"><?php echo $st['late']; ?></td>
                            <td style="color:#F59E0B; font-weight:700;"><?php echo $st['leave']; ?></td>
                            <td><?php echo $st['total']; ?></td>
                            <td>
                                <span style="color:<?php echo $pct >= 75 ? '#16A34A' : '#DC2626'; ?>; font-weight:800;"><?php echo $pct; ?>%</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>