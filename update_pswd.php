<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Change Password';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ChangePassword') {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $user = db_query("SELECT * FROM users WHERE user_id=" . (int) $_SESSION['user_id'])->fetch_assoc();
    if (hash('sha256', $old) !== $user['password']) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New password and confirmation do not match.';
    } else {
        $hp = hash('sha256', $new);
        $st2 = db_prepare("UPDATE users SET password=? WHERE user_id=?");
        $st2->bind_param('si', $hp, $_SESSION['user_id']);
        $st2->execute();
        $message = 'Password changed successfully!';
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="main-content">
    <div class="container-fluid">
        <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-key"></i> Change Password</h3>
            <a href="<?php echo BASE_URL; ?>update_profile.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-user"></i> Update Profile</a>
        </div>

        <form method="post" action="update_pswd.php" style="max-width:520px; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:20px;">
            <input type="hidden" name="action" value="ChangePassword">
            <div class="form-group">
                <label class="required">Current Password</label>
                <input type="password" name="old_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="required">New Password</label>
                <input type="password" name="new_password" class="form-control" minlength="6" required>
            </div>
            <div class="form-group">
                <label class="required">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-success" style="padding:10px 30px;"><i class="fa fa-key"></i> Change Password</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>