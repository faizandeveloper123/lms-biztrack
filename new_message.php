<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'New Message';

$message = '';
$error = '';

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'SendMessage') {
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['message'] ?? '');
    $recipient_type = trim($_POST['recipient_type'] ?? 'all');

    if ($title === '' || $body === '') {
        $error = 'Title and message are required.';
    } else {
        $uid = $_SESSION['user_id'];
        $st2 = db_prepare("INSERT INTO messages (title, message, recipient_type, created_by) VALUES (?, ?, ?, ?)");
        $st2->bind_param('sssi', $title, $body, $recipient_type, $uid);
        $st2->execute();

        // Save to SMS/SMS in real system; here just store template
        $st3 = db_prepare("INSERT INTO message_templates (title, body) VALUES (?, ?)");
        $st3->bind_param('ss', $title, $body);
        $st3->execute();

        $message = 'Message sent successfully!';
    }
}

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
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-envelope"></i> Compose Message</h3>
            <a href="<?php echo BASE_URL; ?>messages_history.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-clock-o"></i> History</a>
        </div>

        <form method="post" action="new_message.php" style="max-width:640px; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:20px;">
            <input type="hidden" name="action" value="SendMessage">
            <div class="form-group">
                <label class="required">Title</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Eid Holiday Announcement" required>
            </div>
            <div class="form-group">
                <label class="required">Message</label>
                <textarea name="message" class="form-control" rows="5" placeholder="Type your message here..." required></textarea>
            </div>
            <div class="form-group">
                <label>Recipient Type</label>
                <select name="recipient_type" class="form-control">
                    <option value="all">All</option>
                    <option value="students">Students</option>
                    <option value="parents">Parents</option>
                    <option value="teachers">Teachers</option>
                    <option value="staff">Staff</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success" style="padding:10px 30px;"><i class="fa fa-send"></i> Send Message</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>