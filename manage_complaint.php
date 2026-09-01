<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Manage Complaints';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'AddComplaint') {
        $subject = trim($_POST['subject'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $type = trim($_POST['complaint_type'] ?? 'general');
        if ($subject === '') {
            $error = 'Subject is required.';
        } else {
            $uid = $_SESSION['user_id'];
            $st2 = db_prepare("INSERT INTO complaints (subject, description, complaint_type, status, created_by) VALUES (?, ?, ?, 'open', ?)");
            $st2->bind_param('sssi', $subject, $desc, $type, $uid);
            $st2->execute();
            $message = 'Complaint registered successfully!';
        }
    }

    if ($action === 'UpdateStatus') {
        $cid = (int) ($_POST['complaint_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'open');
        $st2 = db_prepare("UPDATE complaints SET status=? WHERE complaint_id=?");
        $st2->bind_param('si', $status, $cid);
        $st2->execute();
        $message = 'Complaint status updated!';
    }

    if ($action === 'DeleteComplaint') {
        $cid = (int) ($_POST['complaint_id'] ?? 0);
        $st2 = db_prepare("DELETE FROM complaints WHERE complaint_id=?");
        $st2->bind_param('i', $cid);
        $st2->execute();
        $message = 'Complaint deleted!';
    }
}

$complaints = [];
$res = db_query("SELECT c.*, u.full_name FROM complaints c LEFT JOIN users u ON c.created_by=u.user_id ORDER BY c.created_at DESC");
while ($row = $res->fetch_assoc()) { $complaints[] = $row; }

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
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-comments-o"></i> Manage Complaints <span style="font-size:14px; color:#6B7280;">(<?php echo count($complaints); ?> complaints)</span></h3>
            <a href="<?php echo BASE_URL; ?>student_inquiry.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-question-circle"></i> Inquiries</a>
        </div>

        <div class="row">
            <div class="col-md-4">
                <form method="post" action="manage_complaint.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px;">
                    <h4 style="font-size:15px; font-weight:800; margin:0 0 14px;">Register Complaint</h4>
                    <input type="hidden" name="action" value="AddComplaint">
                    <div class="form-group">
                        <label class="required">Subject</label>
                        <input type="text" name="subject" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="complaint_type" class="form-control">
                            <option value="general">General</option>
                            <option value="facility">Facility</option>
                            <option value="staff">Staff Related</option>
                            <option value="student">Student Related</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="4"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success" style="width:100%;">Register Complaint</button>
                </form>
            </div>

            <div class="col-md-8">
                <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
                    <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                        <thead>
                            <tr><th>#</th><th>Subject</th><th>Type</th><th>Status</th><th>By</th><th>Date</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php if (count($complaints) === 0): ?>
                                <tr><td colspan="7" style="text-align:center; color:#6B7280; padding:25px;">No complaints registered.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($complaints as $c): ?>
                                <tr>
                                    <td><?php echo $c['complaint_id']; ?></td>
                                    <td><strong><?php echo e($c['subject']); ?></strong><?php if ($c['description']) echo '<br><small style="color:#6B7280;">' . e(mb_substr($c['description'], 0, 60)) . '</small>'; ?></td>
                                    <td><span class="status-badge" style="background:#E0E7FF; color:#4338CA; text-transform:capitalize;"><?php echo e($c['complaint_type']); ?></span></td>
                                    <td>
                                        <form method="post" action="manage_complaint.php" style="display:inline;">
                                            <input type="hidden" name="action" value="UpdateStatus">
                                            <input type="hidden" name="complaint_id" value="<?php echo $c['complaint_id']; ?>">
                                            <select name="status" class="form-control" style="padding:3px; font-size:11.5px; width:auto;" onchange="this.form.submit()">
                                                <?php foreach (['open','in_progress','resolved','closed'] as $st): ?>
                                                    <option value="<?php echo $st; ?>" <?php echo $c['status'] === $st ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_',' ',$st)); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </td>
                                    <td><?php echo e($c['full_name'] ?? '-'); ?></td>
                                    <td><?php echo date('d M Y', strtotime($c['created_at'])); ?></td>
                                    <td>
                                        <form method="post" action="manage_complaint.php" style="display:inline;" onsubmit="return confirm('Delete this complaint?');">
                                            <input type="hidden" name="action" value="DeleteComplaint">
                                            <input type="hidden" name="complaint_id" value="<?php echo $c['complaint_id']; ?>">
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