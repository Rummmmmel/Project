<?php
$role = 'reporter'; $active = 'notifications'; $title = 'Notifications';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
if (REQUIRE_LOGIN) require_login();

$uid = (int) ($_SESSION['user_id'] ?? 0);
$items = db_all('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 30', [$uid]);
$new = count(array_filter($items, fn($n) => !$n['is_read']));
db_run('UPDATE notifications SET is_read=1 WHERE user_id=?', [$uid]);   // opening the page marks them read
$badges = [];                                                          // so the badge disappears immediately

require __DIR__ . '/../includes/header.php';
head_icon('bell', 'Notifications', 'Updates about your reports and job orders');
?>
<div class="card-x">
    <div class="chead"><h2>Recent updates</h2><?php if ($new): ?><span class="pill pill-blue"><?= $new ?> new</span><?php endif; ?></div>
    <?php if (!$items): ?><div class="empty" style="padding:50px">No notifications yet.</div><?php endif; ?>
    <?php foreach ($items as $n): ?>
        <div class="notif"><span class="ndot" style="<?= $n['is_read'] ? 'background:#cbd5e1' : '' ?>"></span>
            <div><div class="nwhen"><?= h(when_label($n['created_at'])) ?></div>
                 <div><?= preg_replace('/(JO-\d+)/', '<b>$1</b>', h($n['message'])) ?></div></div></div>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
