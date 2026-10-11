<?php
$role = 'reporter'; $active = 'reporting'; $title = 'Reporting';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/header.php';

$rows = db_all('SELECT * FROM reports WHERE user_id=? ORDER BY created_at DESC', [(int) ($_SESSION['user_id'] ?? 0)]);
$open = count(array_filter($rows, fn($r) => $r['status'] !== 'Complete'));

head_icon('file-earmark-text', 'Reporting', "$open reports require attention", '<a href="new-report.php" class="btn btn-primary">+ New Report</a>');
if (!empty($_GET['submitted'])): ?>
    <div class="alert alert-success py-2 small">Report submitted. Your tracking number is <b><?= h($_GET['submitted']) ?></b>.</div>
<?php endif; ?>
<div class="card-x">
<?php if (!$rows): ?><div class="empty" style="padding:50px">No reports yet.</div><?php else: ?>
<div class="twrap"><table class="tbl">
    <thead><tr><th>Report ID</th><th>Subject</th><th>Status</th><th>Priority</th><th>Date</th></tr></thead>
    <tbody><?php foreach ($rows as $r): ?>
        <tr><td class="mono gray"><?= h($r['control_no']) ?></td><td><?= h(subject($r)) ?></td><td><?= pill($r['status']) ?></td>
            <td><?= prio_text(prio($r)) ?></td><td class="sm"><?= date('M j, Y', strtotime($r['created_at'])) ?></td></tr>
    <?php endforeach; ?></tbody>
</table></div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
