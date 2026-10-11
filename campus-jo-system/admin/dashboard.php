<?php
$role = 'admin'; $active = 'dashboard'; $title = 'Admin Dashboard';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/header.php';

$c = array_column(db_all('SELECT status, COUNT(*) n FROM reports GROUP BY status'), 'n', 'status');
$month = (int) db_val("SELECT COUNT(*) FROM reports WHERE status='Complete' AND YEAR(updated_at)=YEAR(NOW()) AND MONTH(updated_at)=MONTH(NOW())");
$stats = [[$c['Pending'] ?? 0,'Pending','Awaiting review','#94a3b8'],[$c['Assigned'] ?? 0,'Assigned','Sent to teams','#2f6bff'],
          [$c['In-Progress'] ?? 0,'In progress','Currently active','#f5b301'],[$month,'Completed','This month','#16a34a']];
$highs    = db_all("SELECT * FROM reports WHERE status='Pending' AND COALESCE(final_priority, ai_priority)='High' ORDER BY created_at DESC LIMIT 5");
$incoming = db_all("SELECT r.*, u.email FROM reports r JOIN users u ON u.id=r.user_id WHERE r.status='Pending' ORDER BY r.created_at DESC LIMIT 5");

head_eyebrow('Maintenance Office', 'Admin Dashboard', 'Review, assign, and monitor campus job orders', '<span class="email-pill" style="color:var(--text);font-weight:500">'.h($userName).'</span>');
?>
<div class="row g-3 mb-3">
    <?php foreach ($stats as [$n, $l, $s, $col]): ?>
        <div class="col-6 col-xl-3"><div class="stat" style="--c:<?= $col ?>"><div class="n"><?= (int)$n ?></div><div class="l"><b><?= $l ?></b><small><?= $s ?></small></div></div></div>
    <?php endforeach; ?>
</div>
<div class="card-x mb-3">
    <div class="chead"><div><h2>High-priority reports</h2><p>Issues requiring immediate review</p></div><span class="pill pill-high">AI flagged</span></div>
    <?php if (!$highs): ?><div class="empty" style="padding:30px">No high-priority reports waiting.</div><?php endif; ?>
    <?php foreach ($highs as $r): ?>
        <div class="hrow"><div class="d-flex"><span class="rdot"></span><div><a class="mono" href="assign.php?jo=<?= h($r['control_no']) ?>"><?= h($r['control_no']) ?></a><div><?= h(subject($r)) ?></div></div></div>
            <span class="text-secondary" style="font-size:12.5px"><?= h($r['ai_category']) ?></span></div>
    <?php endforeach; ?>
</div>
<div class="card-x">
    <div class="chead"><div><h2>Incoming reports</h2><p>AI-suggested categories and priority levels</p></div><a href="incoming.php" class="link-sm">View all</a></div>
    <?php if (!$incoming): ?><div class="empty" style="padding:30px">No pending reports.</div><?php else: ?>
    <div class="twrap"><table class="tbl">
        <thead><tr><th>Control No.</th><th>Reporter</th><th>Issue</th><th>AI Category</th><th>Priority</th><th></th></tr></thead>
        <tbody><?php foreach ($incoming as $r): ?>
            <tr><td class="mono"><?= h($r['control_no']) ?></td><td class="sm"><?= h($r['email']) ?></td><td><?= h(subject($r)) ?></td><td class="sm"><?= h($r['ai_category']) ?></td><td><?= prio_pill(prio($r)) ?></td><td><a href="assign.php?jo=<?= h($r['control_no']) ?>" class="btn-line">Review</a></td></tr>
        <?php endforeach; ?></tbody>
    </table></div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
