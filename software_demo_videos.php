<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Training Videos';

$videos = [
    ['title' => 'Getting Started with HIIFI LMS', 'desc' => 'Overview of login, dashboard aur navigation.', 'youtube' => ''],
    ['title' => 'Manage Students & Attendance', 'desc' => 'Students add karna aur daily attendance mark karna.', 'youtube' => ''],
    ['title' => 'Fee Collection & Challans', 'desc' => 'Monthly challan banao aur fee record karo.', 'youtube' => ''],
    ['title' => 'Examination & Marks', 'desc' => 'Exams setup karo aur marks enter karo.', 'youtube' => ''],
    ['title' => 'HRM & Payroll', 'desc' => 'Employees manage karo aur payroll generate karo.', 'youtube' => ''],
];

include __DIR__ . '/includes/header.php';
?>
<style>
.video-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:14px; }
.video-card { background:#fff; border:1px solid #E5E7EB; border-radius:14px; overflow:hidden; }
.video-thumb { height:140px; background:linear-gradient(135deg,#111827,#374151); display:flex; align-items:center; justify-content:center; color:#fff; }
.video-body { padding:14px; }
.video-body .vt { font-weight:800; color:#111827; font-size:14px; }
.video-body .vd { font-size:12.5px; color:#6B7280; margin-top:4px; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-play-circle"></i> Training Videos / Guideline Videos</h3>
        </div>

        <div class="alert alert-info" style="border-radius:12px;">
            Video URLs system per add karne ke liye hai. Is local version mein yeh placeholder guideline topics hain — har card par YouTube link lagane ka option hai.
        </div>

        <div class="video-grid">
            <?php foreach ($videos as $v): ?>
                <div class="video-card">
                    <div class="video-thumb"><i class="fa fa-play-circle" style="font-size:40px;"></i></div>
                    <div class="video-body">
                        <div class="vt"><?php echo e($v['title']); ?></div>
                        <div class="vd"><?php echo e($v['desc']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>