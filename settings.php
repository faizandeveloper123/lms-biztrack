<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'System Settings';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'SaveSettings') {
    $keys = ['school_name', 'school_tagline', 'session_year', 'currency_symbol', 'school_address', 'school_phone', 'school_email', 'school_logo'];
    $saved = 0;
    foreach ($keys as $k) {
        $v = trim($_POST[$k] ?? '');
        $st2 = db_prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
        $st2->bind_param('ss', $k, $v);
        $st2->execute();
        $saved++;
    }
    $message = "Settings saved successfully! ($saved keys)";
}

include __DIR__ . '/includes/header.php';
?>
<div class="main-content">
    <div class="container-fluid">
        <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-cog"></i> System Settings</h3>
            <a href="<?php echo BASE_URL; ?>update_profile.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-user"></i> Update Profile</a>
            <a href="<?php echo BASE_URL; ?>update_pswd.php" class="btn btn-warning" style="color:#fff; margin-left:8px;"><i class="fa fa-key"></i> Change Password</a>
        </div>

        <form method="post" action="settings.php" style="max-width:720px; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:20px;">
            <input type="hidden" name="action" value="SaveSettings">
            <div class="row">
                <div class="form-group col-md-6">
                    <label>School Name</label>
                    <input type="text" name="school_name" class="form-control" value="<?php echo e(get_setting('school_name') ?: 'HIIFI LMS'); ?>">
                </div>
                <div class="form-group col-md-6">
                    <label>Tagline</label>
                    <input type="text" name="school_tagline" class="form-control" value="<?php echo e(get_setting('school_tagline') ?: 'Test Portal'); ?>">
                </div>
                <div class="form-group col-md-6">
                    <label>Session Year</label>
                    <input type="text" name="session_year" class="form-control" value="<?php echo e(get_setting('session_year') ?: '2026-2027'); ?>">
                </div>
                <div class="form-group col-md-6">
                    <label>Currency Symbol</label>
                    <input type="text" name="currency_symbol" class="form-control" value="<?php echo e(get_setting('currency_symbol') ?: 'Rs.'); ?>">
                </div>
                <div class="form-group col-md-6">
                    <label>School Phone</label>
                    <input type="text" name="school_phone" class="form-control" value="<?php echo e(get_setting('school_phone') ?: ''); ?>">
                </div>
                <div class="form-group col-md-6">
                    <label>School Email</label>
                    <input type="text" name="school_email" class="form-control" value="<?php echo e(get_setting('school_email') ?: ''); ?>">
                </div>
                <div class="form-group col-md-12">
                    <label>School Address</label>
                    <textarea name="school_address" class="form-control" rows="2"><?php echo e(get_setting('school_address') ?: ''); ?></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-success" style="padding:10px 30px;"><i class="fa fa-save"></i> Save Settings</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>