<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Manage Tickets';

// Use complaints as tickets + messages as support tickets
$tickets = [];
$res = db_query("SELECT * FROM complaints ORDER BY created_at DESC");
while ($row = $res->fetch_assoc()) {
    $row['src'] = 'complaint';
    $tickets[] = $row;
}

$open = count(array_filter($tickets, function($t){ return in_array($t['status'], ['open','in_progress']); }));
$resolved = count(array_filter($tickets, function($t){ return in_array($t['status'], ['resolved','closed']); }));

include __DIR__ . '/includes/header.php';
?>
<style>
.kpi-row { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:16px; }
.kpi-card { background:#fff; border:1px solid #E5E7EB; border-radius:16px; padding:18px; }
.kpi-label { font-size:12.5px; color:#6B7280; font-weight:600; }
.kpi-value { font-size:23px; font-weight:800; color:#111827; margin-top:8px; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-ticket"></i> Tickets / Complaints</h3>
        </div>

        <div class="kpi-row">
            <div class="kpi-card"><div class="kpi-label">Total Tickets</div><div class="kpi-value"><?php echo count($tickets); ?></div></div>
            <div class="kpi-card"><div class="kpi-label">Open</div><div class="kpi-value" style="color:#DC2626;"><?php echo $open; ?></div></div>
            <div class="kpi-card"><div class="kpi-label">Resolved / Closed</div><div class="kpi-value" style="color:#16A34A;"><?php echo $resolved; ?></div></div>
        </div>

        <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
            <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                <thead><tr><th>#</th><th>Subject</th><th>Type</th><th>Description</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                    <?php if (count($tickets) === 0): ?><tr><td colspan="6" style="text-align:center; color:#6B7280; padding:25px;">Koi ticket nahi.</td></tr><?php endif; ?>
                    <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td><?php echo $t['complaint_id']; ?></td>
                            <td><strong><?php echo e($t['subject']); ?></strong></td>
                            <td><span class="status-badge" style="background:#E0E7FF; color:#4338CA;"><?php echo e($t['complaint_type'] ?? 'general'); ?></span></td>
                            <td><?php echo e(mb_strimwidth($t['description'] ?? '', 0, 70, '...')); ?></td>
                            <td><span class="status-badge status-<?php echo in_array($t['status'], ['resolved','closed']) ? 'present' : 'pending'; ?>"><?php echo ucfirst($t['status']); ?></span></td>
                            <td><?php echo date('d M Y', strtotime($t['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>