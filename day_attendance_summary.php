<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Daily Attendance Summary';

$sel_class = (int) ($_GET['class_id'] ?? 0);
$date = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { $date = date('Y-m-d'); }

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

$stats = ['present' => 0, 'absent' => 0, 'late' => 0, 'leave' => 0, 'total' => 0];
$classRows = [];

foreach ($classes as $c) {
    $row = ['class' => $c, 'present' => 0, 'absent' => 0, 'late' => 0, 'leave' => 0, 'total' => 0, 'pct' => 0];
    $res = db_query("SELECT a.status, COUNT(*) cnt FROM attendance a JOIN students s ON a.student_id=s.student_id
                     WHERE a.date='$date' AND s.class_id={$c['class_id']} GROUP BY a.status");
    while ($r = $res->fetch_assoc()) {
        $row[$r['status']] = (int) $r['cnt'];
        $row['total'] += (int) $r['cnt'];
        $stats[$r['status']] += (int) $r['cnt'];
        $stats['total'] += (int) $r['cnt'];
    }
    $row['pct'] = $row['total'] > 0 ? round(($row['present'] / $row['total']) * 100) : 0;
    $classRows[] = $row;
}

include __DIR__ . '/includes/header.php';
?>
<style>
.search-bar-student { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px; }
.analytics-cards { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:16px; }
.analytics-cards .ac { background:#fff; border:1px solid #E5E7EB; border-radius:12px; padding:14px; text-align:center; }
.analytics-cards .ac .n { font-size:22px; font-weight:800; }
.analytics-cards .ac .l { font-size:11.5px; color:#6B7280; }
@media (max-width:900px){ .analytics-cards{ grid-template-columns:repeat(2,1fr);} }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-bar-chart"></i> Daily Attendance Summary <span style="font-size:14px; color:#6B7280;">(<?php echo date('d M Y', strtotime($date)); ?>)</span></h3>
        </div>

        <form method="get" action="day_attendance_summary.php" class="search-bar-student">
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>Date</label>
                <input type="date" name="date" class="form-control" value="<?php echo e($date); ?>">
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-search"></i> Load</button>
            </div>
        </form>

        <div class="analytics-cards">
            <div class="ac"><div class="n" style="color:#16A34A;"><?php echo $stats['present']; ?></div><div class="l">Present</div></div>
            <div class="ac"><div class="n" style="color:#DC2626;"><?php echo $stats['absent']; ?></div><div class="l">Absent</div></div>
            <div class="ac"><div class="n" style="color:#377DFF;"><?php echo $stats['late']; ?></div><div class="l">Late</div></div>
            <div class="ac"><div class="n" style="color:#F59E0B;"><?php echo $stats['leave']; ?></div><div class="l">Leave</div></div>
            <div class="ac"><div class="n" style="color:#374151;"><?php echo $stats['total']; ?></div><div class="l">Total Records</div></div>
        </div>

        <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
            <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                <thead>
                    <tr><th>Class</th><th>Present</th><th>Absent</th><th>Late</th><th>Leave</th><th>Total</th><th>Attendance %</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($classRows as $r): ?>
                        <tr>
                            <td><strong><?php echo e($r['class']['class_name']); ?></strong></td>
                            <td style="color:#16A34A; font-weight:700;"><?php echo $r['present']; ?></td>
                            <td style="color:#DC2626; font-weight:700;"><?php echo $r['absent']; ?></td>
                            <td style="color:#377DFF; font-weight:700;"><?php echo $r['late']; ?></td>
                            <td style="color:#F59E0B; font-weight:700;"><?php echo $r['leave']; ?></td>
                            <td><?php echo $r['total']; ?></td>
                            <td><span style="color:<?php echo $r['pct'] >= 75 ? '#16A34A' : '#DC2626'; ?>; font-weight:800;"><?php echo $r['pct']; ?>%</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>