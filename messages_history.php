<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Message History';

$messages = [];
$res = db_query("SELECT m.*, u.full_name FROM messages m LEFT JOIN users u ON m.created_by=u.user_id ORDER BY m.created_at DESC LIMIT 100");
while ($row = $res->fetch_assoc()) { $messages[] = $row; }

$templates = [];
$res = db_query("SELECT * FROM message_templates ORDER BY created_at DESC LIMIT 20");
while ($row = $res->fetch_assoc()) { $templates[] = $row; }

include __DIR__ . '/includes/header.php';
?>
<div class="main-content">
    <div class="container-fluid">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-clock-o"></i> Message History</h3>
            <a href="<?php echo BASE_URL; ?>new_message.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-envelope"></i> Compose</a>
        </div>

        <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px; margin-bottom:14px;">
            <h4 style="font-size:15px; font-weight:800; padding:14px 16px; margin:0; border-bottom:1px solid #F3F4F6;">Sent Messages (<?php echo count($messages); ?>)</h4>
            <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                <thead>
                    <tr><th>#</th><th>Title</th><th>Message</th><th>Recipient</th><th>Sent By</th><th>Date</th></tr>
                </thead>
                <tbody>
                    <?php if (count($messages) === 0): ?>
                        <tr><td colspan="6" style="text-align:center; color:#6B7280; padding:25px;">No messages sent yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($messages as $m): ?>
                        <tr>
                            <td><?php echo $m['message_id']; ?></td>
                            <td><strong><?php echo e($m['title']); ?></strong></td>
                            <td style="max-width:350px;"><?php echo e(substr($m['message'], 0, 80)); ?><?php echo strlen($m['message']) > 80 ? '...' : ''; ?></td>
                            <td><span class="status-badge status-paid" style="background:#E0E7FF; color:#4338CA; text-transform:capitalize;"><?php echo e($m['recipient_type']); ?></span></td>
                            <td><?php echo e($m['full_name'] ?? '-'); ?></td>
                            <td><?php echo date('d M Y h:i A', strtotime($m['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
            <h4 style="font-size:15px; font-weight:800; padding:14px 16px; margin:0; border-bottom:1px solid #F3F4F6;">Message Templates</h4>
            <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                <thead><tr><th>#</th><th>Title</th><th>Body</th><th>Date</th></tr></thead>
                <tbody>
                    <?php if (count($templates) === 0): ?>
                        <tr><td colspan="4" style="text-align:center; color:#6B7280; padding:25px;">No templates saved yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($templates as $t): ?>
                        <tr>
                            <td><?php echo $t['template_id']; ?></td>
                            <td><strong><?php echo e($t['title']); ?></strong></td>
                            <td><?php echo e(substr($t['body'], 0, 80)); ?><?php echo strlen($t['body']) > 80 ? '...' : ''; ?></td>
                            <td><?php echo date('d M Y', strtotime($t['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>