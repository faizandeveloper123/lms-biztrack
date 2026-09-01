<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Manage Students';

$message = '';
$error = '';

// Edit / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'UpdateStudent') {
    $id = (int) ($_POST['student_id'] ?? 0);
    $first_name  = trim($_POST['first_name'] ?? '');
    $father_name = trim($_POST['lname'] ?? '');
    $cellno      = trim($_POST['cellno'] ?? '');
    $class_id    = (int) ($_POST['class'] ?? 0);
    $gender      = $_POST['gender'] ?? 'male';
    $status      = (int) ($_POST['status'] ?? 1);
    $email       = trim($_POST['email'] ?? '');
    $address     = trim($_POST['address'] ?? '');

    if ($id > 0 && $first_name !== '') {
        $stmt = db_prepare("UPDATE students SET first_name=?, last_name=?, father_name=?, phone=?, email=?, gender=?, class_id=?, address=?, status=? WHERE student_id=?");
        $stmt->bind_param('sssssssisi', $first_name, $father_name, $father_name, $cellno, $email, $gender, $class_id, $address, $status, $id);
        try {
            $stmt->execute();
            $message = 'Student updated successfully!';
        } catch (Exception $ex) {
            $error = 'Error: ' . $ex->getMessage();
        }
    }
}

// Filters
$search = trim($_GET['search'] ?? '');
$class_id = (int) ($_GET['class_id'] ?? 0);
$status_f = $_GET['status'] ?? '';

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = '(s.first_name LIKE ? OR s.last_name LIKE ? OR s.father_name LIKE ? OR s.phone LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like; $types .= 's';
    $params[] = $like; $types .= 's';
    $params[] = $like; $types .= 's';
    $params[] = $like; $types .= 's';
}
if ($class_id > 0) {
    $where[] = 's.class_id = ?';
    $params[] = $class_id; $types .= 'i';
}
if ($status_f !== '') {
    $where[] = 's.status = ?';
    $params[] = ($status_f === 'active') ? 1 : 0; $types .= 'i';
}

$sql = "SELECT s.*, c.class_name, sec.section_name,
        (SELECT SUM(total_amount - paid_amount) FROM fee_challans fc WHERE fc.student_id = s.student_id AND fc.status != 'paid') AS balance
        FROM students s
        LEFT JOIN classes c ON s.class_id = c.class_id
        LEFT JOIN sections sec ON s.section_id = sec.section_id";

if (count($where) > 0) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY s.student_id DESC LIMIT 500';

$students = [];
$stmt = null;
if (count($params) > 0) {
    $stmt = db_prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = db_query($sql);
}
while ($row = $res->fetch_assoc()) { $students[] = $row; }

$classes = [];
$res2 = db_query("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
while ($row = $res2->fetch_assoc()) { $classes[] = $row; }

include __DIR__ . '/includes/header.php';
?>
<style>
.search-bar-student { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px; }
.student-head { display:flex; align-items:center; justify-content:space-between; padding:14px 4px; }
.student-head h3 { font-size:18px; font-weight:800; color:#111827; margin:0; }
.table-actions .btn { padding: 5px 12px; font-size: 12px; }
.photo-cell img { width: 38px; height:38px; border-radius:50%; object-fit:cover; }
</style>

<div class="main-content">
    <div class="container-fluid">

        <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

        <div class="student-head">
            <h3><i class="fa fa-users"></i> Manage Students <span style="font-size:14px; color:#6B7280;">(<?php echo count($students); ?> records)</span></h3>
            <a href="<?php echo BASE_URL; ?>add_student.php" class="btn btn-success" style="color:#fff;"><i class="fa fa-plus"></i> Add New Student</a>
        </div>

        <form method="get" action="manage_students.php" class="search-bar-student">
            <div class="form-group col-md-4" style="margin-bottom:0;">
                <label>Search</label>
                <input type="text" name="search" class="form-control" value="<?php echo e($search); ?>" placeholder="Name / GR No / Cell No">
            </div>
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>Class</label>
                <select name="class_id" class="form-control">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?php echo $c['class_id']; ?>" <?php echo $class_id == $c['class_id'] ? 'selected' : ''; ?>><?php echo e($c['class_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-3" style="margin-bottom:0;">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="">All</option>
                    <option value="active" <?php echo $status_f === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_f === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="form-group col-md-1" style="margin-bottom:0;">
                <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-search"></i></button>
            </div>
        </form>

        <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
            <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Father Name</th>
                        <th>Class</th>
                        <th>Section</th>
                        <th>Phone</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($students) === 0): ?>
                        <tr><td colspan="9" style="text-align:center; color:#6B7280; padding:30px;">No students found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($students as $s): ?>
                        <tr>
                            <td class="photo-cell">
                                <?php if (!empty($s['photo'])): ?>
                                    <img src="<?php echo BASE_URL; ?>uploads/students/<?php echo e($s['photo']); ?>" alt="">
                                <?php else: ?>
                                    <div style="width:38px;height:38px;border-radius:50%;background:#FFE0EC;color:#EC4899;display:flex;align-items:center;justify-content:center;"><i class="fa fa-user"></i></div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo e($s['first_name']); ?></strong></td>
                            <td><?php echo e($s['father_name'] ?? $s['last_name']); ?></td>
                            <td><?php echo e($s['class_name'] ?? '-'); ?></td>
                            <td><?php echo e($s['section_name'] ?? '-'); ?></td>
                            <td><?php echo e($s['phone']); ?></td>
                            <td style="color:<?php echo ($s['balance'] ?? 0) > 0 ? '#DC2626' : '#16A34A'; ?>; font-weight:700;">
                                <?php echo e(get_setting('currency_symbol', 'Rs.')) . number_format($s['balance'] ?? 0); ?>
                            </td>
                            <td>
                                <?php if ($s['status'] == 1): ?>
                                    <span class="label label-success">Active</span>
                                <?php else: ?>
                                    <span class="label label-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="table-actions">
                                <button class="btn btn-primary btn-sm" onclick="openEdit(<?php echo $s['student_id']; ?>)"><i class="fa fa-pencil"></i> Edit</button>
                                <a href="student_birthday.php" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="post" action="manage_students.php">
                <input type="hidden" name="action" value="UpdateStudent">
                <input type="hidden" name="student_id" id="m_id">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Edit Student</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Student Name</label>
                            <input type="text" class="form-control" name="first_name" id="m_fname" required="">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Father Name</label>
                            <input type="text" class="form-control" name="lname" id="m_father">
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Phone</label>
                            <input type="text" class="form-control" name="cellno" id="m_phone">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Email</label>
                            <input type="text" class="form-control" name="email" id="m_email">
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label>Class</label>
                            <select name="class" id="m_class" class="form-control">
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?php echo $c['class_id']; ?>"><?php echo e($c['class_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Gender</label>
                            <select name="gender" id="m_gender" class="form-control">
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Status</label>
                            <select name="status" id="m_status" class="form-control">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" id="m_address" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var studentData = <?php echo json_encode($students, JSON_UNESCAPED_UNICODE); ?>;
function openEdit(id) {
    var s = studentData.find(function(x){ return x.student_id == id; });
    if (!s) return;
    document.getElementById('m_id').value = s.student_id;
    document.getElementById('m_fname').value = s.first_name || '';
    document.getElementById('m_father').value = s.father_name || '';
    document.getElementById('m_phone').value = s.phone || '';
    document.getElementById('m_email').value = s.email || '';
    document.getElementById('m_class').value = s.class_id || '';
    document.getElementById('m_gender').value = s.gender || 'male';
    document.getElementById('m_status').value = s.status || 1;
    document.getElementById('m_address').value = s.address || '';
    $('#editModal').modal('show');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>