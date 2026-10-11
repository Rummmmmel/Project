<?php
/* =====================================================
   GOOGLE SIGN-IN (server side)
   The browser sends the Google token here. PHP checks it
   with Google, checks the @rtu.edu.ph domain, then logs
   the user in (creating the account on first sign in).
   ===================================================== */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

function fail(string $message): void
{
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

// 1. CSRF check
if (!hash_equals($_SESSION['csrf'] ?? '', $input['csrf'] ?? '')) {
    fail('Invalid request. Please refresh the page and try again.');
}

$credential = $input['credential'] ?? '';
if ($credential === '') {
    fail('No Google credential received.');
}

// 2. Ask Google to verify the token
$ch = curl_init('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
]);
$result = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error  = curl_error($ch);
curl_close($ch);

if ($result === false) {
    fail('Could not reach Google to verify your sign in. (' . $error . ')');
}
if ($status !== 200) {
    fail('Google sign in could not be verified.');
}

$payload = json_decode($result, true);

// 3. Make sure the token is really for OUR app and is valid
if (($payload['aud'] ?? '') !== GOOGLE_CLIENT_ID) {
    fail('This sign in is not meant for this system.');
}
if (!in_array($payload['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true)) {
    fail('Invalid token issuer.');
}
if (($payload['exp'] ?? 0) < time()) {
    fail('Sign in expired. Please try again.');
}
if (($payload['email_verified'] ?? 'false') !== 'true') {
    fail('Your Google email is not verified.');
}

$email = strtolower($payload['email'] ?? '');
$name  = $payload['name'] ?? $email;

// 4. Institutional email only (this is the real check, done on the server)
if (substr(strrchr($email, '@'), 1) !== ALLOWED_DOMAIN) {
    fail('Access restricted: please use your official @' . ALLOWED_DOMAIN . ' account.');
}

// 5. Find the user, or create the account on first sign in
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    // Google users have no password of their own, so store a random unusable one
    $stmt = $pdo->prepare(
        'INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([
        $name,
        $email,
        password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
        'student',   // default role; an admin can change it in the database
    ]);

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
}

// 6. Log them in
session_regenerate_id(true);
$_SESSION['user_id']   = $user['id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['email']     = $user['email'];
$_SESSION['role']      = $user['role'];

// TODO (2FA): send the 6-digit code here and redirect to verify-otp.php instead

echo json_encode([
    'success'  => true,
    'redirect' => $user['role'] === 'admin' ? 'admin/dashboard.php' : 'reporter/dashboard.php',
]);
