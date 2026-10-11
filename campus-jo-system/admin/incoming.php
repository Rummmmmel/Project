<?php
$role = 'admin'; $active = 'incoming'; $title = 'Incoming Reports';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/header.php';

$rows = db_all("SELECT r.*, u.email FROM reports r JOIN users u ON u.id=r.user_id WHERE r.status='Pending'
                ORDER BY FIELD(COALESCE(r.final_priority, r.ai_priority),'High','Medium','Low'), r.created_at");
head_eyebrow('Maintenance Office', 'Incoming Reports', 'Review AI suggestions before creating a job order');
if (!empty($_GET['assigned'])): ?><div class="alert alert-success py-2 small"><?= h($_GET['assigned']) ?> was assigned.</div><?php endif; ?>
<div class="card-x">
<?php if (!$rows): ?><div class="empty" style="padding:50px">No pending reports.</div><?php else: ?>
<div class="twrap"><table class="tbl">
    <thead><tr><th>Control No.</th><th>Reporter</th><th>Issue</th><th>AI Category</th><th>AI Priority</th><th></th></tr></thead>
    <tbody><?php foreach ($rows as $r): ?>
        <tr><td class="mono"><?= h($r['control_no']) ?></td><td class="sm"><?= h($r['email']) ?></td><td><?= h(subject($r)) ?></td><td class="sm"><?= h($r['ai_category']) ?></td><td><?= prio_pill($r['ai_priority'] ?: 'Medium') ?></td><td><a href="assign.php?jo=<?= h($r['control_no']) ?>" class="btn-line">Review</a></td></tr>
    <?php endforeach; ?></tbody>
</table></div><?php endif; ?>
</div>
<p class="note">Category and priority are AI-suggested. Confirm or change both before assigning.</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
