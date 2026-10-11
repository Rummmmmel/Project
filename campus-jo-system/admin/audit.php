<?php
$role = 'admin'; $active = 'audit'; $title = 'Audit Logs';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/header.php';

$rows  = db_all('SELECT a.*, u.full_name FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.created_at DESC LIMIT 100');
$today = (int) db_val('SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at)=CURDATE()');
$ok    = (int) db_val("SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at)=CURDATE() AND outcome='Success'");
$stats = [[$today, 'Actions today', ''], [$ok, 'Successful', '#15803d'], [$today - $ok, 'Blocked action', '#dc2626']];

head_icon('folder', 'Audit Logs', 'Review administrative activity and system changes');
?>
<div class="row g-3 mb-3">
    <?php foreach ($stats as [$n, $l, $col]): ?>
        <div class="col-12 col-md-4"><div class="stat stat-plain" style="<?= $col ? '--n:'.$col : '' ?>"><div class="n"><?= $n ?></div><div class="l"><?= $l ?></div></div></div>
    <?php endforeach; ?>
</div>
<div class="card-x">
<?php if (!$rows): ?><div class="empty" style="padding:50px">No activity yet.</div><?php else: ?>
<div class="twrap"><table class="tbl">
    <thead><tr><th>Date &amp; Time</th><th>User</th><th>Action</th><th>Record</th><th>Details</th><th>Outcome</th></tr></thead>
    <tbody><?php foreach ($rows as $r): ?>
        <tr><td class="sm"><?= date('M j, Y · g:i A', strtotime($r['created_at'])) ?></td><td><?= h($r['full_name'] ?: 'System') ?></td><td><?= h($r['action']) ?></td>
            <td class="mono"><?= h($r['record']) ?></td><td class="sm"><?= h($r['details']) ?></td>
            <td><span class="pill pill-<?= $r['outcome'] === 'Success' ? 'complete' : 'high' ?>"><?= h($r['outcome']) ?></span></td></tr>
    <?php endforeach; ?></tbody>
</table></div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
