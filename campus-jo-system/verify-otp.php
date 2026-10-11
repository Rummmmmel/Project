<?php
require_once __DIR__ . '/includes/config.php';
session_start();

$email  = $_SESSION['email'] ?? 'juan.delacruz@rtu.edu.ph';
$masked = preg_replace('/^(.).*(@.*)$/', '$1***$2', $email);

// TODO (when connecting the database): on POST, compare $_POST['code'] with the saved code and its expiry,
// then log the user in and redirect by role. Add a "resend" action that sends a new code by email.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Two-Factor Authentication</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
<div class="otp-wrap">
    <h1>Job Order Management System</h1>
    <div class="otp-card">
        <div class="otp-lock"><i class="bi bi-lock"></i></div>
        <div style="font-family:'DM Sans',sans-serif;font-size:18px;font-weight:500">Two-Factor Authentication</div>
        <div class="text-secondary mt-1" style="font-size:14px">Sent to <span style="color:var(--text)"><?= h($masked) ?></span></div>
        <div class="text-secondary" style="font-size:12px">Your institutional email</div>

        <form action="verify-otp.php" method="POST" id="otpForm">
            <input type="hidden" name="code" id="code">
            <div class="otp-in">
                <?php for ($i = 0; $i < OTP_LENGTH; $i++): ?>
                    <input type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code" aria-label="Digit <?= $i + 1 ?>">
                <?php endfor; ?>
            </div>
            <button type="submit" id="verifyBtn" class="btn btn-primary w-100" disabled>Verify Code</button>
        </form>
        <a href="#" class="d-inline-block mt-3 text-secondary" style="font-size:12px">Resend code</a>
    </div>
</div>
<script>
const boxes = [...document.querySelectorAll('.otp-in input')], btn = document.getElementById('verifyBtn'), code = document.getElementById('code');
function sync(){ code.value = boxes.map(b => b.value).join(''); btn.disabled = code.value.length < boxes.length; }
boxes.forEach((b, i) => {
  b.addEventListener('input', () => { b.value = b.value.replace(/\D/g, ''); if (b.value && boxes[i + 1]) boxes[i + 1].focus(); sync(); });
  b.addEventListener('keydown', e => { if (e.key === 'Backspace' && !b.value && boxes[i - 1]) boxes[i - 1].focus(); });
  b.addEventListener('paste', e => {
    const t = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, boxes.length);
    if (!t) return; e.preventDefault(); [...t].forEach((c, k) => boxes[k].value = c); (boxes[t.length] || boxes[boxes.length - 1]).focus(); sync();
  });
});
boxes[0].focus();
</script>
</body>
</html>
