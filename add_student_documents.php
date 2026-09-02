<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Student Documents';

$search = trim($_GET['search'] ?? '');
$where = '';
$params = [];
$types = '';

if ($search !== '') {
    $like = '%' . $search . '%';
    $where = " WHERE CONCAT(s.first_name, ' ', s.last_name) LIKE ? OR s.gr_no LIKE ? OR s.form_b_no LIKE ? OR s.father_cnic LIKE ? OR c.class_name LIKE ?";
    $params = [$like, $like, $like, $like, $like];
    $types = 'sssss';
}

$sql = "SELECT s.student_id, s.gr_no, CONCAT(s.first_name, ' ', s.last_name) AS student_name, s.form_b_no, s.father_cnic, s.photo, s.section_id, c.class_name, sec.section_name
        FROM students s
        LEFT JOIN classes c ON c.class_id = s.class_id
        LEFT JOIN sections sec ON sec.section_id = s.section_id" . $where . " ORDER BY s.first_name ASC";

if ($types !== '') {
    $st = db_prepare($sql);
    $st->bind_param($types, ...$params);
    $st->execute();
    $result = $st->get_result();
} else {
    $result = db_query($sql);
}

$students = [];
if ($result) { while ($row = $result->fetch_assoc()) { $students[] = $row; } }

include __DIR__ . '/includes/header.php';
?>
<style>
.doc-table th { font-size: 12px; text-transform: uppercase; letter-spacing: .4px; color: #8A99A8; font-weight: 700; border-bottom: 1px solid #E6E9ED; padding: 10px 14px; white-space: nowrap; }
.doc-table td { padding: 9px 14px; font-size: 13.5px; vertical-align: middle; border-bottom: 1px solid #EEF1F4; }
.doc-table tbody tr:hover { background: #F7FAFC; }
.doc-photo { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 1px solid #E6E9ED; background: #F2F6F9; display: block; }
.doc-photo-none { width: 38px; height: 38px; border-radius: 50%; background: #EAF0F4; color: #9AA7B4; display: flex; align-items: center; justify-content: center; font-size: 14px; }
.doc-batch { display: inline-block; background: #EAF2F8; color: #3E7CB1; font-size: 11px; font-weight: 600; border-radius: 6px; padding: 3px 8px; }
.chip-num { font-family: Consolas, monospace; font-size: 12.5px; color: #2A3F54; background: #F4F6F8; display: inline-block; padding: 2px 8px; border-radius: 6px; }
.search-bar { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
.search-bar .form-control { max-width: 360px; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px; flex-wrap:wrap; gap:10px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-file-text-o"></i> Student Documents</h3>
            <span style="font-size:12.5px; color:#8A99A8;">Manage B-Form Nos &amp; Father CNICs</span>
        </div>

        <form method="get" action="add_student_documents.php" class="search-bar">
            <input type="text" name="search" class="form-control" placeholder="Search by name, GR no, B-Form no or father CNIC..." value="<?php echo e($search); ?>">
            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Search</button>
            <?php if ($search !== ''): ?>
                <a href="add_student_documents.php" class="btn btn-default"><i class="fa fa-times"></i> Clear</a>
            <?php endif; ?>
        </form>

        <div style="background:#fff; border:1px solid #E6E9ED; border-radius:14px; overflow:hidden;">
            <?php if (count($students) > 0): ?>
                <div class="table-responsive">
                    <table class="table doc-table" style="margin:0;">
                        <thead>
                            <tr>
                                <th>GR No</th>
                                <th>Student</th>
                                <th>Class</th>
                                <th>B-Form No</th>
                                <th>Father CNIC</th>
                                <th>Photo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $std): ?>
                            <tr>
                                <td><span class="chip-num"><?php echo e($std['gr_no']); ?></span></td>
                                <td style="font-weight:600; color:#2A3F54;"><?php echo e($std['student_name']); ?></td>
                                <td>
                                    <?php if (!empty($std['class_name'])): ?>
                                        <span class="doc-batch"><?php echo e($std['class_name']); ?> <span style="opacity:.65;"><?php echo e($std['section_name'] ?? ''); ?></span></span>
                                    <?php else: ?>
                                        <span style="color:#B6C0CB;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color:#5A6B7B;"><?php echo e($std['form_b_no'] ?? ''); ?></td>
                                <td style="color:#5A6B7B;"><?php echo e($std['father_cnic'] ?? ''); ?></td>
                                <td>
                                    <?php if (!empty($std['photo'])): ?>
                                        <img class="doc-photo" src="<?php echo BASE_URL; ?>uploads/students/<?php echo e($std['photo']); ?>" alt="">
                                    <?php else: ?>
                                        <span class="doc-photo-none"><i class="fa fa-user"></i></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align:center; padding:56px 20px; color:#95A5A6;">
                    <i class="fa fa-file-text-o" style="font-size:44px; color:#D5DBDB; display:block; margin-bottom:12px;"></i>
                    <?php echo $search !== '' ? 'No students match your search.' : 'Student documents will appear here once students are added.'; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>