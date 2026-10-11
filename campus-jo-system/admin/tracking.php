<?php
$role = 'admin'; $active = 'tracking'; $title = 'Tracking / Status';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
if (REQUIRE_LOGIN) require_login('admin');

// Status update (Pending / Assigned / In-Progress / Complete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $new = $_POST['status'] ?? '';
    $r = db_one('SELECT * FROM reports WHERE id=?', [(int) ($_POST['id'] ?? 0)]);
    if ($r && in_array($new, STATUSES, true) && $new !== $r['status']) {
        db_run('UPDATE reports SET status=? WHERE id=?', [$new, $r['id']]);
        audit((int) $_SESSION['user_id'], 'Updated status', $r['control_no'], $r['status'] . ' → ' . $new);
        notify((int) $r['user_id'], $new === 'Complete' ? "Your report {$r['control_no']} was marked Completed." : "Your report {$r['control_no']} is now $new.");
    }
    header('Location: tracking.php'); exit;
}

require __DIR__ . '/../includes/header.php';
$rows = db_all('SELECT * FROM reports ORDER BY created_at DESC');
head_icon('clock', 'Tracking', 'Monitor the status of all items',
    '<div class="search"><i class="bi bi-search"></i><input type="text" id="q" class="form-control" placeholder="Search Report ID"></div>');
?>
<div class="card-x">
    <ul class="nav tabs-x" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#t-report" type="button">REPORT</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-wo" type="button">WORK ORDER</button></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="t-report">
        <?php if (!$rows): ?><div class="empty" style="padding:50px">No reports yet.</div><?php else: ?>
            <div class="twrap"><table class="tbl" id="trk">
                <thead><tr><th>Status</th><th>Report ID</th><th>Details</th><th>Update status</th></tr></thead>
                <tbody><?php foreach ($rows as $r): ?>
                    <tr><td><span class="sdot <?= status_key($r['status']) ?>"></span><?= h($r['status']) ?></td><td class="mono gray"><?= h($r['control_no']) ?></td><td><?= h(subject($r)) ?></td>
                        <td><form method="POST" class="m-0"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <select name="status" class="form-select form-select-sm" style="width:140px" onchange="this.form.submit()">
                                <?php foreach (STATUSES as $s): ?><option <?= $s === $r['status'] ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
                            </select></form></td></tr>
                <?php endforeach; ?></tbody>
            </table></div><?php endif; ?>
        </div>
        <div class="tab-pane fade" id="t-wo"><div class="twrap"><table class="tbl">
            <thead><tr><th>Control No.</th><th>Assigned to</th><th>Target date</th><th>Status</th></tr></thead>
            <tbody><?php foreach (array_filter($rows, fn($r) => $r['assigned_team']) as $r): ?>
                <tr><td class="mono"><?= h($r['control_no']) ?></td><td><?= h($r['assigned_team']) ?></td><td class="sm"><?= $r['target_date'] ? date('M j, Y', strtotime($r['target_date'])) : '—' ?></td><td><?= pill($r['status']) ?></td></tr>
            <?php endforeach; ?></tbody>
        </table></div></div>
    </div>
</div>
<?php tracking_legend(); ?>
<script>document.getElementById('q').addEventListener('input',function(){var v=this.value.toLowerCase();document.querySelectorAll('#trk tbody tr').forEach(function(r){r.style.display=r.textContent.toLowerCase().indexOf(v)>-1?'':'none'})});</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
