<?php
$role = 'reporter'; $active = 'account'; $title = 'Account';
require __DIR__ . '/../includes/header.php';

head_icon('person', 'Account', 'Your profile information');
// Program, year level and contact are empty until the user fills them in (add these columns to your users table)
$rows = [['Name', $userName], ['Program', ''], ['Year level', ''], ['Contact', '']];
?>
<div class="card-x mb-3"><div class="d-flex align-items-center gap-3 p-4">
    <div class="avatar-lg"><?= h($initials) ?></div>
    <div><div style="font-size:18px;font-weight:500"><?= h($userName) ?></div>
        <div class="text-secondary" style="font-size:14px"><?= h($userEmail) ?></div>
        <span class="pill pill-blue mt-1"><?= h(ucfirst($_SESSION['role'] ?? 'student')) ?></span></div>
</div></div>

<div class="card-x">
    <?php foreach ($rows as [$k, $v]): ?>
        <div class="prow"><div class="k"><?= h($k) ?></div><div class="v"><?= $v !== '' ? h($v) : '<span class="text-secondary">Not set</span>' ?></div><a href="#" class="edit" title="Edit"><i class="bi bi-pencil-square"></i></a></div>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
