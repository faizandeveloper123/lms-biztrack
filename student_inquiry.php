<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Student Inquiries';

$message = '';
$error = '';

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'AddInquiry') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $class_id = (int) ($_POST['class_id'] ?? 0);
        $msg = trim($_POST['message'] ?? '');
        if ($name === '') {
            $error = 'Name is required.';
        } else {
            $st2 = db_prepare("INSERT INTO inquiries (name, phone, email, class_id, message, status) VALUES (?, ?, ?, ?, ?, 'new')");
            $cid = $class_id > 0 ? $class_id : null;
            $st2->bind_param('sssis', $name, $phone, $email, $cid, $msg);
            $st2->execute();
            $message = 'Inquiry added successfully!';
        }
    }

    if ($action === 'UpdateStatus') {
        $iid = (int) ($_POST['inquiry_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'new');
        $st2 = db_prepare("UPDATE inquiries SET status=? WHERE inquiry_id=?");
        $st2->bind_param('si', $status, $iid);
        $st2->execute();
        $message = 'Inquiry status updated!';
    }

    if ($action === 'DeleteInquiry') {
        $iid = (int) ($_POST['inquiry_id'] ?? 0);
        $st2 = db_prepare("DELETE FROM inquiries WHERE inquiry_id=?");
        $st2->bind_param('i', $iid);
        $st2->execute();
        $message = 'Inquiry deleted!';
    }
}

$inquiries = [];
$res = db_query("SELECT i.*, c.class_name FROM inquiries i LEFT JOIN classes c ON i.class_id=c.class_id ORDER BY i.created_at DESC");
while ($row = $res->fetch_assoc()) { $inquiries[] = $row; }

include __DIR__ . '/includes/header.php';
?>
<style>
.search-bar-student { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-question-circle"></i> Student Inquiries <span style="font-size:14px; color:#6B7280;">(<?php echo count($inquiries); ?> inquiries)</span></h3>
            <a href="<?php echo BASE_URL; ?>manage_complaint.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-comments-o"></i> Complaints</a>
        </div>

        <div class="row">
            <div class="col-md-4">
                <form method="post" action="student_inquiry.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px;">
                    <h4 style="font-size:15px; font-weight:800; margin:0 0 14px;">New Inquiry</h4>
                    <input type="hidden" name="action" value="AddInquiry">
                    <div class="form-group">
                        <label class="required">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Interested Class</label>
                        <select name="class_id" class="form-control">
                            <option value="0">Any</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo $c['class_id']; ?>"><?php echo e($c['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success" style="width:100%;">Save Inquiry</button>
                </form>
            </div>

            <div class="col-md-8">
                <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
                    <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                        <thead>
                            <tr><th>#</th><th>Name</th><th>Phone</th><th>Class</th><th>Status</th><th>Date</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php if (count($inquiries) === 0): ?>
                                <tr><td colspan="7" style="text-align:center; color:#6B7280; padding:25px;">No inquiries recorded.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($inquiries as $i): ?>
                                <tr>
                                    <td><?php echo $i['inquiry_id']; ?></td>
                                    <td><strong><?php echo e($i['name']); ?></strong><?php if ($i['email']) echo '<br><small style="color:#6B7280;">' . e($i['email']) . '</small>'; ?></td>
                                    <td><?php echo e($i['phone'] ?? '-'); ?></td>
                                    <td><?php echo e($i['class_name'] ?? '-'); ?></td>
                                    <td>
                                        <select class="form-control" style="padding:3px; font-size:11.5px; width:auto;" onchange="this.form.submit()" form="inq-form-<?php echo $i['inquiry_id']; ?>">
                                            <?php foreach (['new','contacted','admitted','lost'] as $st): ?>
                                                <option value="<?php echo $st; ?>" <?php echo $i['status'] === $st ? 'selected' : ''; ?>><?php echo ucfirst($st); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <form id="inq-form-<?php echo $i['inquiry_id']; ?>" method="post" action="student_inquiry.php" style="display:none;">
                                            <input type="hidden" name="action" value="UpdateStatus">
                                            <input type="hidden" name="inquiry_id" value="<?php echo $i['inquiry_id']; ?>">
                                            <input type="hidden" name="status" value="">
                                        </form>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($i['created_at'])); ?></td>
                                    <td>
                                        <form method="post" action="student_inquiry.php" style="display:inline;" onsubmit="return confirm('Delete this inquiry?');">
                                            <input type="hidden" name="action" value="DeleteInquiry">
                                            <input type="hidden" name="inquiry_id" value="<?php echo $i['inquiry_id']; ?>">
                                            <button class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>