<?php
/* Shared layout: sidebar + page frame.
   Each page sets $role ('reporter' or 'admin'), $active and $title BEFORE including this file. */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function head_eyebrow(string $eyebrow, string $title, string $sub, string $right = ''): void {
    echo '<div class="phead"><div><div class="eyebrow">'.h($eyebrow).'</div><h1 class="ptitle">'.h($title).'</h1><p class="psub">'.h($sub).'</p></div>'
        .($right ? '<div class="phead-right">'.$right.'</div>' : '').'</div>';
}
function head_icon(string $icon, string $title, string $sub, string $right = ''): void {
    echo '<div class="phead"><div class="phead-icon"><div class="ibox"><i class="bi bi-'.h($icon).'"></i></div><div><h1>'.h($title).'</h1><p>'.h($sub).'</p></div></div>'
        .($right ? '<div class="phead-right">'.$right.'</div>' : '').'</div>';
}
function status_key(string $s): string {
    return ['Pending'=>'pending','Assigned'=>'assigned','In-Progress'=>'progress','In Progress'=>'progress','Complete'=>'complete','Completed'=>'complete'][$s] ?? 'pending';
}
function pill(string $s): string { return '<span class="pill pill-'.status_key($s).'">'.h($s).'</span>'; }
function prio_pill(string $p): string { return '<span class="pill pill-'.strtolower($p).'">'.h($p).'</span>'; }
function prio_text(string $p): string { return '<span class="pt-'.strtolower($p).'">'.h($p).'</span>'; }
function tracking_table(array $rows, string $id = 'trk'): void {
    $icons = ['pending'=>'<span class="text-secondary">—</span>','assigned'=>'<i class="bi bi-x-lg" style="color:#f0505c"></i>',
              'progress'=>'<i class="bi bi-clock" style="color:#f5b301"></i>','complete'=>'<i class="bi bi-check-lg" style="color:#16a34a"></i>'];
    echo '<div class="twrap"><table class="tbl" id="'.$id.'"><thead><tr><th>Status</th><th>Report ID</th><th>Details</th><th class="text-center">Action</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $k = status_key($r[0]);
        echo '<tr><td><span class="sdot '.$k.'"></span>'.h($r[0]).'</td><td class="mono gray">'.h($r[1]).'</td><td>'.h($r[2]).'</td><td class="text-center">'.$icons[$k].'</td></tr>';
    }
    echo '</tbody></table></div>';
}
function tracking_legend(): void {
    echo '<div class="legend"><span><span class="sdot"></span>Pending</span><span><span class="sdot assigned"></span>Assigned</span><span><span class="sdot progress"></span>In-Progress</span><span><span class="sdot complete"></span>Complete</span></div>';
}

if (REQUIRE_LOGIN) require_login($role === 'admin' ? 'admin' : null);

$userName  = $_SESSION['full_name'] ?? ($role === 'admin' ? 'BFMU Administrator' : 'Juan Dela Cruz');
$userEmail = $_SESSION['email'] ?? 'juan.delacruz@rtu.edu.ph';
$userSub   = $role === 'admin' ? 'Maintenance Office' : $userEmail;
$initials  = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(preg_split('/\s+/', trim($userName)), 0, 2))));

$badges = [];
if (!empty($_SESSION['user_id'])) {
    require_once __DIR__ . '/functions.php';
    try {
        if ($role === 'admin') {
            $badges['incoming'] = (int) db_val("SELECT COUNT(*) FROM reports WHERE status='Pending'");
        } else {
            $badges['notifications'] = (int) db_val('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0', [$_SESSION['user_id']]);
            $badges['reporting']     = (int) db_val("SELECT COUNT(*) FROM reports WHERE user_id=? AND status<>'Complete'", [$_SESSION['user_id']]);
        }
    } catch (Throwable $e) { /* badges are optional */ }
}

$menus = [
    'reporter' => [
        ['dashboard',    'Dashboard',        'grid',                   'dashboard.php',    0],
        ['notifications','Notifications',    'bell',                   'notifications.php',3],
        ['reporting',    'Reporting',        'file-earmark-text',      'reporting.php',    7],
        ['tracking',     'Tracking',         'clock',                  'tracking.php',     0],
        ['history',      'History',          'arrow-counterclockwise', 'history.php',      0],
    ],
    'admin' => [
        ['dashboard', 'Dashboard',         'grid',                   'dashboard.php', 0],
        ['incoming',  'Incoming Reports',  'file-earmark-text',      'incoming.php',  3],
        ['assign',    'Assign Job Order',  'clipboard-check',        'assign.php',    0],
        ['tracking',  'Tracking / Status', 'clock',                  'tracking.php',  0],
        ['history',   'Reports History',   'arrow-counterclockwise', 'history.php',   0],
        ['audit',     'Audit Logs',        'folder',                 'audit.php',     0],
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> - RTU BFMU</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/app.css">
    <script>try{var r=document.documentElement,s=localStorage.getItem('side');if(s==='1'||(s===null&&innerWidth<768))r.classList.add('collapsed');if(localStorage.getItem('theme')==='dark')r.dataset.theme='dark';}catch(e){}</script>
</head>
<body>
<div class="shell">
    <aside class="side">
        <div class="side-top">
            <button class="burger" id="burger" type="button" aria-label="Toggle menu"><i class="bi bi-list"></i></button>
            <div class="brand"><b>RTU – BFMU</b><small>Management System</small></div>
        </div>

        <nav class="nav-list">
            <?php foreach ($menus[$role] as [$key, $label, $icon, $href, $unused]): $badge = $badges[$key] ?? 0; ?>
                <a href="<?= $href ?>" class="side-link <?= $active === $key ? 'active' : '' ?>" title="<?= h($label) ?>">
                    <i class="bi bi-<?= $icon ?>"></i><span class="nlabel"><?= h($label) ?></span>
                    <?php if ($badge): ?><span class="nbadge"><?= $badge ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="side-bottom">
            <button class="side-link" id="darkBtn" type="button">
                <i class="bi bi-moon dm-dark"></i><i class="bi bi-sun dm-light"></i>
                <span class="nlabel"><span class="dm-dark">Dark mode</span><span class="dm-light">Light mode</span></span>
            </button>
            <?php $ut = $role === 'reporter' ? 'a href="account.php"' : 'div'; ?>
            <<?= $ut ?> class="user <?= $active === 'account' ? 'active' : '' ?>">
                <span class="avatar"><?= h($initials) ?></span>
                <span class="uinfo"><?= h($userName) ?><small><?= h($userSub) ?></small></span>
            </<?= $role === 'reporter' ? 'a' : 'div' ?>>
            <a href="../logout.php" class="side-link logout" title="Log Out"><i class="bi bi-box-arrow-right"></i><span class="nlabel">Log Out</span></a>
        </div>
    </aside>

    <main class="main"><div class="page">
