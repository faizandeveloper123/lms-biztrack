<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Student Analytics';

$totalActive = (int) (db_query("SELECT COUNT(*) c FROM students WHERE status=1")->fetch_assoc()['c'] ?? 0);
$boys  = (int) (db_query("SELECT COUNT(*) c FROM students WHERE status=1 AND gender='male'")->fetch_assoc()['c'] ?? 0);
$girls = (int) (db_query("SELECT COUNT(*) c FROM students WHERE status=1 AND gender='female'")->fetch_assoc()['c'] ?? 0);

$classDist = [];
$res = db_query("SELECT c.class_name, COUNT(s.student_id) cnt FROM classes c
                 LEFT JOIN students s ON s.class_id = c.class_id AND s.status=1
                 GROUP BY c.class_id, c.class_name ORDER BY c.class_name");
while ($row = $res->fetch_assoc()) { $classDist[] = $row; }

$monthlyAdm = [];
for ($m = 1; $m <= 12; $m++) {
    $cnt = (int) (db_query("SELECT COUNT(*) c FROM students WHERE YEAR(admission_date)=YEAR(CURDATE()) AND MONTH(admission_date)=$m")->fetch_assoc()['c'] ?? 0);
    $monthlyAdm[] = ['month' => date('M', mktime(0,0,0,$m,1)), 'count' => $cnt];
}

include __DIR__ . '/includes/header.php';
?>
<style>
.ana-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:16px; }
.ana-grid .ac { background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:18px; }
.ana-grid .ac .lbl { font-size:12.5px; color:#6B7280; font-weight:600; }
.ana-grid .ac .val { font-size:26px; font-weight:800; color:#111827; margin-top:6px; }
.card-2col { grid-column: span 2; }
@media (max-width: 900px) { .ana-grid { grid-template-columns:1fr; } .card-2col { grid-column: span 1; } }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-chart-pie"></i> Student Analytics</h3>
        </div>

        <div class="ana-grid">
            <div class="ac"><div class="lbl">Total Active Students</div><div class="val"><?php echo $totalActive; ?></div></div>
            <div class="ac"><div class="lbl">Boys</div><div class="val" style="color:#377DFF;"><?php echo $boys; ?></div></div>
            <div class="ac"><div class="lbl">Girls</div><div class="val" style="color:#EC4899;"><?php echo $girls; ?></div></div>

            <div class="ac card-2col">
                <div class="lbl" style="margin-bottom:10px;">Students by Class</div>
                <canvas id="classChart" height="120"></canvas>
            </div>
            <div class="ac">
                <div class="lbl" style="margin-bottom:10px;">Admissions This Year</div>
                <canvas id="admChart" height="120"></canvas>
            </div>
        </div>

        <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px; margin-top:6px;">
            <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                <thead>
                    <tr><th>Class</th><th>Students</th><th>Share</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($classDist as $cd): ?>
                        <tr>
                            <td><strong><?php echo e($cd['class_name']); ?></strong></td>
                            <td><?php echo $cd['cnt']; ?></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="flex:1; height:8px; background:#F3F4F6; border-radius:999px; overflow:hidden; max-width:200px;">
                                        <div style="height:100%; width:<?php echo $totalActive > 0 ? round(($cd['cnt'] / $totalActive) * 100) : 0; ?>%; background:linear-gradient(90deg,#FF7A1B,#ffa35c); border-radius:999px;"></div>
                                    </div>
                                    <span style="font-size:12px; color:#6B7280;"><?php echo $totalActive > 0 ? round(($cd['cnt'] / $totalActive) * 100) : 0; ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('classChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($classDist, 'class_name')); ?>,
        datasets: [{ label: 'Students', data: <?php echo json_encode(array_column($classDist, 'cnt')); ?>, backgroundColor: '#FF7A1B', borderRadius: 6 }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#F3F4F6' } }, x: { grid: { display: false } } } }
});
new Chart(document.getElementById('admChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($monthlyAdm, 'month')); ?>,
        datasets: [{ label: 'Admissions', data: <?php echo json_encode(array_column($monthlyAdm, 'count')); ?>, borderColor: '#377DFF', backgroundColor: 'rgba(55,125,255,0.08)', fill: true, tension: 0.4 }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#F3F4F6' } }, x: { grid: { display: false } } } }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>