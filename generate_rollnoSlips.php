<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Generate Roll No Slips';

$sel_exam = (int) ($_GET['exam_id'] ?? 0);
$sel_class = (int) ($_GET['class_id'] ?? 0);

$exams = [];
$res = db_query("SELECT e.*, c.class_name FROM exams e LEFT JOIN classes c ON e.class_id=c.class_id ORDER BY e.exam_id DESC");
while ($row = $res->fetch_assoc()) { $exams[] = $row; }

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

$students = [];
if ($sel_class > 0) {
    $res = db_query("SELECT * FROM students WHERE class_id=$sel_class AND status=1 ORDER BY roll_no, first_name");
    $i = 0;
    while ($row = $res->fetch_assoc()) { $row['_slip_no'] = ++$i; $students[] = $row; }
}

$exam = null;
if ($sel_exam > 0) $exam = db_query("SELECT e.*, c.class_name FROM exams e LEFT JOIN classes c ON e.class_id=c.class_id WHERE e.exam_id=$sel_exam")->fetch_assoc();

include __DIR__ . '/includes/header.php';
?>
<style>
.search-bar-student { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px; }
.slip-sheet { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
.slip-card { background:#fff; border:1px solid #E5E7EB; border-radius:12px; padding:14px; break-inside: avoid; page-break-inside: avoid; margin-bottom:14px; position:relative; }
.slip-card .top { display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #FF7A1B; padding-bottom:8px; margin-bottom:8px; }
.slip-card .stripe { position:absolute; right:0; top:0; bottom:0; width:10px; background:linear-gradient(180deg,#FF7A1B,#ffa35c); border-radius:12px 0 0 12px; }
@media print { .no-print { display:none!important; } body { background:#fff; } }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-sticky-note"></i> Generate Roll No Slips</h3>
        </div>

        <form method="get" action="generate_rollnoSlips.php" class="search-bar-student no-print">
            <div class="form-group col-md-4" style="margin-bottom:0;">
                <label>Exam</label>
                <select name="exam_id" class="form-control">
                    <option value="">Select Exam</option>
                    <?php foreach ($exams as $x): ?>
                        <option value="<?php echo $x['exam_id']; ?>" <?php echo $sel_exam == $x['exam_id'] ? 'selected' : ''; ?>><?php echo e($x['exam_name']); ?> — <?php echo e($x['class_name'] ?? ''); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>Class</label>
                <select name="class_id" class="form-control">
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?php echo $c['class_id']; ?>" <?php echo $sel_class == $c['class_id'] ? 'selected' : ''; ?>><?php echo e($c['class_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-search"></i> Generate</button>
            </div>
        </form>

        <?php if ($sel_class > 0 && $exam): ?>
            <div class="no-print" style="margin-bottom:14px;">
                <button onclick="window.print()" class="btn btn-success"><i class="fa fa-print"></i> Print All Slips</button>
            </div>
            <div class="slip-sheet" style="display:grid; grid-template-columns:repeat(2,1fr); gap:10px;">
                <?php if (count($students) === 0): ?>
                    <div style="grid-column:1/-1; text-align:center; color:#6B7280; padding:40px;">No students in this class.</div>
                <?php endif; ?>
                <?php foreach ($students as $st): ?>
                    <div class="slip-card">
                        <div class="stripe"></div>
                        <div class="top">
                            <div>
                                <div style="font-weight:800; color:#111827;"><?php echo e($exam['exam_name']); ?></div>
                                <div style="font-size:12px; color:#6B7280;">Roll No Slip</div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-weight:800; color:#FF7A1B; font-size:20px;"><?php echo $st['_slip_no']; ?></div>
                                <div style="font-size:11px; color:#6B7280;">Roll #</div>
                            </div>
                        </div>
                        <table style="width:100%; font-size:12.5px;">
                            <tr>
                                <td style="color:#6B7280; width:80px;">Student</td>
                                <td><strong><?php echo e($st['first_name']); ?> <?php echo e($st['father_name'] ?? ''); ?></strong></td>
                            </tr>
                            <tr>
                                <td style="color:#6B7280;">Class</td>
                                <td><?php echo e($exam['class_name']); ?></td>
                            </tr>
                            <tr>
                                <td style="color:#6B7280;">Date</td>
                                <td><?php echo $exam['exam_date'] ? date('d M Y', strtotime($exam['exam_date'])) : date('d M Y'); ?></td>
                            </tr>
                            <tr>
                                <td style="color:#6B7280;">GR No</td>
                                <td><?php echo e($st['roll_no'] ?? $st['student_id']); ?></td>
                            </tr>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>