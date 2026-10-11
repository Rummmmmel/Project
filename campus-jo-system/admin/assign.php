<?php
$role = 'admin'; $active = 'assign'; $title = 'Assign Job Order';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
if (REQUIRE_LOGIN) require_login('admin');

$jo = $_GET['jo'] ?? $_POST['jo'] ?? '';
$sel = 'SELECT r.*, u.email FROM reports r JOIN users u ON u.id=r.user_id ';
$r = $jo ? db_one($sel . 'WHERE r.control_no=?', [$jo]) : db_one($sel . "WHERE r.status='Pending' ORDER BY r.created_at LIMIT 1");
$errors = [];

if ($r && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $cat = $_POST['category'] ?? ''; $pri = $_POST['priority'] ?? ''; $team = $_POST['team'] ?? ''; $date = $_POST['target_date'] ?? '';
    if (!in_array($cat, CATEGORIES, true) || !in_array($pri, PRIORITIES, true) || !in_array($team, TEAMS, true)) $errors[] = 'Please choose valid options.';
    if (!DateTime::createFromFormat('Y-m-d', $date)) $errors[] = 'Please choose a target date.';

    if (!$errors) {
        db_run("UPDATE reports SET final_category=?, final_priority=?, assigned_team=?, target_date=?, status='Assigned' WHERE id=?", [$cat, $pri, $team, $date, $r['id']]);
        $changed = ($cat !== $r['ai_category'] || $pri !== $r['ai_priority']) ? ' (AI suggestion changed by admin)' : '';
        audit((int) $_SESSION['user_id'], 'Assigned job order', $r['control_no'], "Assigned to $team$changed");
        notify((int) $r['user_id'], "Your report {$r['control_no']} was assigned to the $team.");
        header('Location: incoming.php?assigned=' . urlencode($r['control_no']));
        exit;
    }
}

require __DIR__ . '/../includes/header.php';
head_eyebrow('Maintenance Office', 'Assign Job Order', $r ? "Review and assign {$r['control_no']} to a maintenance team" : 'Nothing to assign');
if (!$r): ?><div class="card-x"><div class="empty">No pending reports to assign.</div></div>
<?php else:
    $dCat = $r['final_category'] ?: $r['ai_category']; $dPri = prio($r); $dTeam = $r['assigned_team'] ?: $r['ai_team'];
    $dDate = $r['target_date'] ?: date('Y-m-d', strtotime('+3 days')); ?>
<div class="card-x" style="max-width:760px"><div class="p-4">
    <?php if ($errors): ?><div class="alert alert-danger py-2 small"><?= h(implode(' ', $errors)) ?></div><?php endif; ?>
    <div class="repbox d-flex justify-content-between align-items-start gap-3">
        <div><div class="k">Report</div><b><?= h($r['description']) ?></b>
            <span class="text-secondary" style="font-size:12.5px"><?= h($r['location']) ?> · Submitted by <?= h($r['email']) ?></span>
            <?php if ($r['ai_reason']): ?><div class="text-secondary mt-1" style="font-size:12.5px"><i class="bi bi-stars"></i> <?= h($r['ai_reason']) ?></div><?php endif; ?></div>
        <span class="mono"><?= h($r['control_no']) ?></span>
    </div>
    <?php if ($r['photo']): ?><p><a href="../uploads/<?= h($r['photo']) ?>" target="_blank" class="link-sm"><i class="bi bi-image"></i> View attached photo</a></p><?php endif; ?>
    <form action="assign.php" method="POST">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="jo" value="<?= h($r['control_no']) ?>">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Category</label><select class="form-select" name="category">
                <?php foreach (CATEGORIES as $c): ?><option value="<?= h($c) ?>" <?= $c === $dCat ? 'selected' : '' ?>><?= h($c) ?><?= $c === $r['ai_category'] ? ' (AI-suggested)' : '' ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6"><label class="form-label">Priority</label><select class="form-select" name="priority">
                <?php foreach (PRIORITIES as $p): ?><option value="<?= $p ?>" <?= $p === $dPri ? 'selected' : '' ?>><?= $p ?><?= $p === $r['ai_priority'] ? ' (AI-suggested)' : '' ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6"><label class="form-label">Assign to</label><select class="form-select" name="team">
                <?php foreach (TEAMS as $t): ?><option <?= $t === $dTeam ? 'selected' : '' ?>><?= h($t) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6"><label class="form-label">Target date</label><input type="date" class="form-control" name="target_date" value="<?= h($dDate) ?>" required></div>
            <div class="col-12"><button type="submit" class="btn btn-primary w-100">Confirm &amp; Assign</button></div>
        </div>
    </form>
</div></div>
<?php endif; require __DIR__ . '/../includes/footer.php'; ?>
