<?php
$role = 'reporter'; $active = 'reporting'; $title = 'New Report';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/gemini.php';
require_once __DIR__ . '/../includes/auth.php';
if (REQUIRE_LOGIN) require_login();

$errors = [];
$old = ['location' => '', 'category' => '', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old = ['location' => trim($_POST['location'] ?? ''), 'category' => $_POST['category'] ?? '', 'description' => trim($_POST['description'] ?? '')];

    if ($old['location'] === '') $errors[] = 'Building / room is required.';
    if (!in_array($old['category'], CATEGORIES, true)) $errors[] = 'Please choose a category is required.';
    if (mb_strlen($old['description']) < 10) $errors[] = 'Please describe the issue (at least 10 characters).';

    // optional photo: images only, max 3 MB
    $photo = null;
    if (!$errors && !empty($_FILES['photo']['name'])) {
        $f = $_FILES['photo'];
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name'])] ?? null;
        if ($f['error'] !== UPLOAD_ERR_OK || !$ext || $f['size'] > 3 * 1024 * 1024) {
            $errors[] = 'Photo must be a JPG, PNG or WEBP image under 3 MB.';
        } else {
            $photo = bin2hex(random_bytes(12)) . '.' . $ext;
            move_uploaded_file($f['tmp_name'], __DIR__ . '/../uploads/' . $photo);
        }
    }

    if (!$errors) {
        $ai = classify_report($old['description']);            // AI priority + recommendation
        $uid = (int) $_SESSION['user_id'];

        $pdo->beginTransaction();
        db_run('INSERT INTO reports (control_no, user_id, location, description, category, ai_category, ai_priority, ai_team, ai_reason, photo)
                VALUES (?,?,?,?,?,?,?,?,?,?)',
            ['TMP-' . bin2hex(random_bytes(6)), $uid, $old['location'], $old['description'], $old['category'], $ai['category'], $ai['priority'], $ai['team'], $ai['reason'], $photo]);
        $id = (int) $pdo->lastInsertId();
        $control = 'JO-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
        db_run('UPDATE reports SET control_no=? WHERE id=?', [$control, $id]);
        $pdo->commit();

        audit($uid, 'Submitted report', $control, 'Priority suggested: ' . $ai['priority'] . ' (' . $ai['source'] . ')');
        notify($uid, "Your report $control was received and is pending review.");
        header('Location: reporting.php?submitted=' . urlencode($control));
        exit;
    }
}

require __DIR__ . '/../includes/header.php';
head_eyebrow('Reporter Portal', 'Report an Issue', 'Submit a maintenance concern and receive a tracking number');
?>
<div class="card-x" style="max-width:720px"><div class="p-4">
    <?php if ($errors): ?><div class="alert alert-danger py-2 small"><ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form action="new-report.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Building / Room</label><input type="text" class="form-control" name="location" value="<?= h($old['location']) ?>" placeholder="e.g. Bldg 3, Rm 204" maxlength="150" required></div>
            <div class="col-md-6"><label class="form-label">Category</label>
                <select class="form-select" name="category" required>
                    <?php foreach (CATEGORIES as $c): ?><option <?= $old['category'] === $c ? 'selected' : '' ?>><?= h($c) ?></option><?php endforeach; ?>
                </select></div>
            <div class="col-12"><label class="form-label">Description of the issue</label><textarea class="form-control" name="description" rows="4" placeholder="Describe what is wrong..." required><?= h($old['description']) ?></textarea></div>
            <div class="col-12"><label class="form-label">Attach photo (optional)</label><input type="file" class="form-control" name="photo" accept="image/*"></div>
            <div class="col-12"><button type="submit" class="btn btn-primary w-100">Submit report</button>
                <p class="note mb-0">Your description is analyzed by AI to suggest a priority level. The admin reviews it before assigning. Please do not include personal details in the description.</p></div>
        </div>
    </form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
