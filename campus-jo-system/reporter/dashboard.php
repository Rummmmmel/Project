<?php
$role = 'reporter'; $active = 'dashboard'; $title = 'My Dashboard';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/header.php';

$uid = (int) ($_SESSION['user_id'] ?? 0);
$c = array_column(db_all('SELECT status, COUNT(*) n FROM reports WHERE user_id=? GROUP BY status', [$uid]), 'n', 'status');
$total = array_sum($c);
$stats = [[$total, 'Total reports', '#94a3b8', ''], [($c['Pending'] ?? 0) + ($c['Assigned'] ?? 0), 'Pending', '#94a3b8', '#475569'],
          [$c['In-Progress'] ?? 0, 'In progress', '#1a5cff', '#1a5cff'], [$c['Complete'] ?? 0, 'Completed', '#16a34a', '#16a34a']];
$recent = db_all('SELECT * FROM reports WHERE user_id=? ORDER BY created_at DESC LIMIT 5', [$uid]);

head_eyebrow('Reporter Portal', 'My Dashboard', 'Overview of your facility reports and job orders',
    '<span class="email-pill">'.h($userEmail).'</span><a href="notifications.php" class="bell"><i class="bi bi-bell"></i>'
    .(($badges['notifications'] ?? 0) ? '<span class="bell-badge">'.(int)$badges['notifications'].'</span>' : '').'</a>');
?>
<div class="row g-3 mb-3">
    <?php foreach ($stats as [$n, $l, $col, $nc]): ?>
        <div class="col-6 col-lg-3"><div class="stat" style="--c:<?= $col ?>;<?= $nc ? '--n:'.$nc : '' ?>"><div class="n"><?= (int)$n ?></div><div class="l"><?= $l ?></div></div></div>
    <?php endforeach; ?>
</div>
<div class="card-x mb-3"><div class="chead">
    <div><h2>Report a facility issue</h2><p style="font-size:14px">Submit a maintenance concern and receive a tracking number.</p></div>
    <a href="new-report.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Report an Issue</a>
</div></div>
<div class="card-x">
    <div class="chead"><div><h2>Recent activity</h2><p>Your latest submitted reports</p></div><a href="reporting.php" class="link-sm">View all reports</a></div>
    <?php if (!$recent): ?><div class="empty" style="padding:40px">No reports yet. Submit your first one above.</div><?php else: ?>
    <div class="twrap"><table class="tbl">
        <thead><tr><th>Control No.</th><th>Issue</th><th>Status</th></tr></thead>
        <tbody><?php foreach ($recent as $r): ?>
            <tr><td class="mono"><?= h($r['control_no']) ?></td><td><?= h(subject($r)) ?></td><td><?= pill($r['status']) ?></td></tr>
        <?php endforeach; ?></tbody>
    </table></div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
