<?php
$role = 'reporter'; $active = 'tracking'; $title = 'Tracking';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/header.php';

$rows = array_map(fn($r) => [$r['status'], $r['control_no'], subject($r)],
    db_all('SELECT * FROM reports WHERE user_id=? ORDER BY created_at DESC', [(int) ($_SESSION['user_id'] ?? 0)]));

head_icon('clock', 'Tracking', 'Monitor the status of all items',
    '<div class="search"><i class="bi bi-search"></i><input type="text" id="q" class="form-control" placeholder="Search Report ID"></div>');
?>
<div class="card-x"><?php if ($rows) tracking_table($rows); else echo '<div class="empty" style="padding:50px">Nothing to track yet.</div>'; ?></div>
<?php tracking_legend(); ?>
<script>document.getElementById('q').addEventListener('input',function(){var v=this.value.toLowerCase();document.querySelectorAll('#trk tbody tr').forEach(function(r){r.style.display=r.textContent.toLowerCase().indexOf(v)>-1?'':'none'})});</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
