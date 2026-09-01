<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Update Profile';

$message = '';
$error = '';

$user = db_query("SELECT * FROM users WHERE user_id=" . (int) $_SESSION['user_id'])->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'UpdateProfile') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if ($full_name === '' || $full_name === '') {
        $error = 'Name is required.';
    } else {
        $st2 = db_prepare("UPDATE users SET full_name=?, email=?, phone=? WHERE user_id=?");
        $st2->bind_param('sssi', $full_name, $email, $phone, $_SESSION['user_id']);
        $st2->execute();
        $_SESSION['full_name'] = $full_name;
        $message = 'Profile updated successfully!';
        $user = db_query("SELECT * FROM users WHERE user_id=" . (int) $_SESSION['user_id'])->fetch_assoc();
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="main-content">
    <div class="container-fluid">
        <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-user"></i> Update Profile</h3>
            <a href="<?php echo BASE_URL; ?>update_pswd.php" class="btn btn-warning" style="color:#fff;"><i class="fa fa-key"></i> Change Password</a>
        </div>

        <form method="post" action="update_profile.php" style="max-width:520px; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:20px;">
            <input type="hidden" name="action" value="UpdateProfile">
            <div class="form-group">
                <label class="required">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?php echo e($user['full_name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo e($user['email']); ?>">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control" value="<?php echo e($user['phone'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn btn-success" style="padding:10px 30px;"><i class="fa fa-save"></i> Update Profile</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>