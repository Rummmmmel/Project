<?php
$role = 'reporter'; $active = 'history'; $title = 'History';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/header.php';

$rows = db_all("SELECT * FROM reports WHERE user_id=? AND status='Complete' ORDER BY updated_at DESC", [(int) ($_SESSION['user_id'] ?? 0)]);
head_icon('arrow-counterclockwise', 'History', 'Past activity and resolved items');
?>
<div class="card-x">
<?php if (!$rows): ?>
    <div class="empty"><div class="ibox"><i class="bi bi-arrow-counterclockwise"></i></div><b>No history yet</b>Your completed reports and actions will appear here</div>
<?php else: ?>
    <div class="twrap"><table class="tbl">
        <thead><tr><th>Control No.</th><th>Issue</th><th>Handled by</th><th>Completed</th></tr></thead>
        <tbody><?php foreach ($rows as $r): ?>
            <tr><td class="mono"><?= h($r['control_no']) ?></td><td><?= h(subject($r)) ?></td><td class="sm"><?= h($r['assigned_team'] ?: '—') ?></td><td class="sm"><?= date('M j, Y', strtotime($r['updated_at'])) ?></td></tr>
        <?php endforeach; ?></tbody>
    </table></div>
<?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
