<?php
$role = 'admin'; $active = 'history'; $title = 'Reports History';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/header.php';

$rows = db_all("SELECT * FROM reports WHERE status='Complete' ORDER BY updated_at DESC");
head_eyebrow('Maintenance Office', 'Reports History', 'Completed job orders and maintenance records');
?>
<div class="card-x">
<?php if (!$rows): ?><div class="empty" style="padding:50px">No completed job orders yet.</div><?php else: ?>
<div class="twrap"><table class="tbl">
    <thead><tr><th>Control No.</th><th>Date</th><th>Issue</th><th>Category</th><th>Handled By</th><th>Status</th></tr></thead>
    <tbody><?php foreach ($rows as $r): ?>
        <tr><td class="mono"><?= h($r['control_no']) ?></td><td class="sm"><?= date('M j', strtotime($r['updated_at'])) ?></td><td><?= h(subject($r)) ?></td>
            <td class="sm"><?= h($r['final_category'] ?: $r['category']) ?></td><td class="sm"><?= h($r['assigned_team'] ?: '—') ?></td><td><?= pill('Completed') ?></td></tr>
    <?php endforeach; ?></tbody>
</table></div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
