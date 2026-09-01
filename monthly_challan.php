<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Create Challan';

$message = '';
$error = '';

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

$feeHeads = [];
$res = db_query("SELECT head_id, head_name, amount FROM fee_heads WHERE status=1 ORDER BY head_id");
while ($row = $res->fetch_assoc()) { $feeHeads[] = $row; }

$sel_class = (int) ($_GET['class_id'] ?? 0);
$sel_section = (int) ($_GET['section'] ?? 0);
$sel_month = $_GET['month'] ?? date('m/Y');

$students = [];
if ($sel_class > 0) {
    $sql = "SELECT s.* FROM students s WHERE s.status=1 AND s.class_id=?";
    $params = [$sel_class]; $types = 'i';
    if ($sel_section > 0) { $sql .= " AND s.section_id=?"; $params[] = $sel_section; $types .= 'i'; }
    $sql .= " ORDER BY s.first_name";
    $stmt = db_prepare($sql); $stmt->bind_param($types, ...$params); $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $students[] = $row; }
}

// Create Challans
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'CreateMonthlyChallan') {
    $month = trim($_POST['month'] ?? date('m/Y'));
    $due_date = trim($_POST['duedate'] ?? '');
    $student_ids = $_POST['student_ids'] ?? [];
    if (!is_array($student_ids)) { $student_ids = [$student_ids]; }
    $fees = $_POST['fees'] ?? [];

    // Parse month "m/Y"
    $parts = explode('/', $month);
    $m = (int) ($parts[0] ?? 0);
    $y = (int) ($parts[1] ?? date('Y'));
    if ($m < 1 || $m > 12) { $y = date('Y'); $m = (int) date('m'); }

    if (count($student_ids) === 0) {
        $error = 'Please select at least one student to create a challan.';
    } else {
        $created = 0;
        foreach ($student_ids as $sid) {
            $sid = (int) $sid;
            if ($sid <= 0) continue;

            $student = db_query("SELECT class_id FROM students WHERE student_id=$sid")->fetch_assoc();
            if (!$student) continue;

            $total = 0;
            $items = [];
            foreach ($fees as $head_id => $amount) {
                $head_id = (int) $head_id;
                $amount = (float) trim($amount ?? '');
                if ($head_id > 0 && $amount > 0) {
                    $total += $amount;
                    $items[] = ['head_id' => $head_id, 'amount' => $amount];
                }
            }
            if ($total <= 0) {
                // Use default fee heads
                foreach ($feeHeads as $fh) { $total += $fh['amount']; $items[] = ['head_id' => $fh['head_id'], 'amount' => $fh['amount']]; }
            }

            $challan_no = 'CH-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6)) . '-' . $sid;
            $student_class = $student['class_id'];
            $month_str = "$m/$y";
            $uid = $_SESSION['user_id'];
            $stmt = db_prepare("INSERT INTO fee_challans (challan_no, student_id, class_id, month, year, total_amount, paid_amount, status, created_by)
                                VALUES (?, ?, ?, ?, ?, ?, 0, 'unpaid', ?)");
            $stmt->bind_param('sisidii', $challan_no, $sid, $student_class, $month_str, $y, $total, $uid);
            $stmt->execute();
            $challan_id = $stmt->insert_id;

            foreach ($items as $it) {
                $stmt2 = db_prepare("INSERT INTO fee_challan_items (challan_id, head_id, description, amount) VALUES (?, ?, ?, ?)");
                $desc = '';
                foreach ($feeHeads as $fh) { if ($fh['head_id'] == $it['head_id']) { $desc = $fh['head_name']; break; } }
                $it_head = $it['head_id'];
                $it_amount = $it['amount'];
                $stmt2->bind_param('iisd', $challan_id, $it_head, $desc, $it_amount);
                $stmt2->execute();
            }
            $created++;
        }
        $message = "$created challans created for month $m/$y!";
    }
}

include __DIR__ . '/includes/header.php';
?>
<style>
.search-bar-student { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px; }
.fee-grid-heads { display:grid; grid-template-columns:repeat(2,1fr); gap:12px; }
@media (max-width: 700px) { .fee-grid-heads { grid-template-columns:1fr; } }
</style>

<div class="main-content">
    <div class="container-fluid">
        <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-money"></i> Create Challan <span style="font-size:14px; color:#6B7280;">(Monthly)</span></h3>
            <a href="<?php echo BASE_URL; ?>view_challan_details.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-eye"></i> View Challans</a>
        </div>

        <form method="get" action="monthly_challan.php" class="search-bar-student">
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label class="required">Class</label>
                <select name="class_id" id="ch_class" class="form-control" required="">
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?php echo $c['class_id']; ?>" <?php echo $sel_class == $c['class_id'] ? 'selected' : ''; ?>><?php echo e($c['class_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <label>Section</label>
                <select name="section" id="ch_section" class="form-control">
                    <option value="">All Sections</option>
                </select>
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <label>Month</label>
                <input type="text" class="form-control" name="month" id="monthYear" placeholder="MM/YYYY" value="<?php echo e($sel_month); ?>">
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-search"></i> Load Students</button>
            </div>
        </form>

        <?php if ($sel_class > 0): ?>
        <form method="post" action="monthly_challan.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px;">
            <input type="hidden" name="action" value="CreateMonthlyChallan">
            <input type="hidden" name="month" value="<?php echo e($sel_month); ?>">

            <div class="row" style="margin-bottom:14px;">
                <div class="form-group col-md-3">
                    <label>Due Date</label>
                    <input type="date" name="duedate" class="form-control" value="<?php echo date('Y-m-t', strtotime('first day of this month')); ?>">
                </div>
            </div>

            <div class="pane-wrap" style="background:#F9FAFB; border:1px solid #E5E7EB; border-radius:12px; padding:14px; margin-bottom:14px;">
                <div class="pane-title" style="font-size:14px; font-weight:700; margin-bottom:10px;">Fee Details</div>
                <div class="fee-grid-heads">
                    <?php foreach ($feeHeads as $i => $fh): ?>
                        <div class="form-group" style="margin-bottom:8px;">
                            <label style="font-size:12px; color:#374151;"><?php echo e($fh['head_name']); ?></label>
                            <input type="number" class="form-control" name="fees[<?php echo $fh['head_id']; ?>]" value="<?php echo (int) $fh['amount']; ?>" placeholder="Enter Fee" style="padding:8px;">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="overflow-x:auto; margin-top:10px;">
                <table class="table table-striped table-bordered" style="width:100%; background:#fff;">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll" checked></th>
                            <th>GR. No</th>
                            <th>Student Name</th>
                            <th>Father Name</th>
                            <th>Class</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($students) === 0): ?>
                            <tr><td colspan="5" style="text-align:center; color:#6B7280; padding:30px;">No students found. Please select a class.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($students as $st): ?>
                            <tr>
                                <td><input type="checkbox" name="student_ids[]" value="<?php echo $st['student_id']; ?>" class="stu-check" checked></td>
                                <td><?php echo e($st['roll_no'] ?? $st['student_id']); ?></td>
                                <td><strong><?php echo e($st['first_name']); ?></strong></td>
                                <td><?php echo e($st['father_name'] ?? $st['last_name']); ?></td>
                                <td><?php $cn = db_query("SELECT class_name FROM classes WHERE class_id=" . (int)$st['class_id'])->fetch_assoc(); echo e($cn['class_name'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-success" style="padding:10px 30px;"><i class="fa fa-plus"></i> Create Challans</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('ch_class').addEventListener('change', function(){
    var cid = this.value;
    var sel = document.getElementById('ch_section');
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
document.getElementById('selectAll').addEventListener('change', function(){
    document.querySelectorAll('.stu-check').forEach(function(c){ c.checked = this.checked; }.bind(this));
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>