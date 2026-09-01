<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'PayRoll Settings';

$message = '';
$error = '';

// Simple settings keyed in settings table
$keys = ['payroll_deduction_percent', 'payroll_allowance_percent'];
$labels = [
    'payroll_deduction_percent' => 'Default Deduction (%)',
    'payroll_allowance_percent' => 'Default Allowance (%)'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'SavePayrollSettings') {
    foreach ($keys as $k) {
        $v = trim($_POST[$k] ?? '0');
        $st2 = db_prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
        $st2->bind_param('ss', $k, $v);
        $st2->execute();
    }
    $message = 'Payroll settings saved!';
}

include __DIR__ . '/includes/header.php';
?>
<div class="main-content">
    <div class="container-fluid">
        <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-cog"></i> PayRoll Settings</h3>
        </div>

        <form method="post" action="payroll_setting.php" style="max-width:520px; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:20px;">
            <input type="hidden" name="action" value="SavePayrollSettings">
            <?php foreach ($keys as $k): ?>
                <div class="form-group">
                    <label><?php echo $labels[$k]; ?> <small style="color:#6B7280;">(banked salary ke % par)</small></label>
                    <input type="number" step="0.01" name="<?php echo $k; ?>" class="form-control" value="<?php echo e(get_setting($k, '0')); ?>">
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-success" style="padding:10px 30px;"><i class="fa fa-save"></i> Save Settings</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>