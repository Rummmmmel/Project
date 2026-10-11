<?php
/* =====================================================
   PHP SECTION 1: runs BEFORE the page is shown.
   It handles the Log In and Sign Up form submissions.
   ===================================================== */
session_start();
require_once __DIR__ . '/includes/db.php';      // gives us $pdo (the database connection)

require_once __DIR__ . '/includes/config.php';  // gives us ALLOWED_DOMAIN and GOOGLE_CLIENT_ID

const ROLES = ['student', 'faculty', 'utility', 'guard'];

$errors    = [];
$success   = '';
$activeTab = 'login';                           // which tab is open: 'login' or 'signup'

// CSRF token (blocks forged form submissions)
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        die('Invalid request. Please go back and try again.');
    }

    $action = $_POST['action'] ?? '';

    /* ---------- LOG IN ---------- */
    if ($action === 'login') {
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        // Look up the user (prepared statement = safe from SQL injection)
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email']     = $user['email'];
            $_SESSION['role']      = $user['role'];

            // TODO (2FA): instead of redirecting right away, send a 6-digit code
            // and redirect to verify-otp.php first.

            header('Location: ' . ($user['role'] === 'admin'
                ? 'admin/dashboard.php'
                : 'reporter/dashboard.php'));
            exit;
        }

        $errors[] = 'Invalid email or password.';
    }

    /* ---------- SIGN UP ---------- */
    if ($action === 'register') {
        $activeTab = 'signup';

        $name     = trim($_POST['full_name'] ?? '');
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $role     = $_POST['role'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if ($name === '') {
            $errors[] = 'Full name is required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (substr(strrchr($email, '@'), 1) !== ALLOWED_DOMAIN) {
            // Institutional email only
            $errors[] = 'Only @' . ALLOWED_DOMAIN . ' email addresses can register.';
        }
        if (!in_array($role, ROLES, true)) {
            $errors[] = 'Please choose a valid role.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        // Is the email already registered?
        if (!$errors) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'That email is already registered.';
            }
        }

        // Save the new user (password is hashed, never stored as plain text)
        if (!$errors) {
            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);

            $success   = 'Account created. You can now log in.';
            $activeTab = 'login';
        }
    }
}

// Small helper: safely print a value back into a form field
function old(string $key): string
{
    return htmlspecialchars($_POST[$key] ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rizal Technological University - Job Order Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/app.css">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>

<div class="min-vh-100 d-flex flex-column align-items-center justify-content-center p-3">

    <h1 class="h3 fw-semibold text-center mb-1">Rizal Technological University</h1>
    <p class="text-secondary text-center mb-4">Job Order Management System</p>

    <div class="card border-0 shadow-sm w-100" style="max-width: 440px; border-radius: 16px; overflow: hidden;">

        <!-- Tabs -->
        <ul class="nav nav-tabs nav-fill" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold py-3 <?= $activeTab === 'login' ? 'active' : '' ?>"
                        data-bs-toggle="tab" data-bs-target="#login-pane" type="button">LOG IN</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold py-3 <?= $activeTab === 'signup' ? 'active' : '' ?>"
                        data-bs-toggle="tab" data-bs-target="#signup-pane" type="button">SIGN UP</button>
            </li>
        </ul>

        <div class="card-body p-4">

            <!-- PHP SECTION 2: show error / success messages -->
            <?php if ($success): ?>
                <div class="alert alert-success py-2 small"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="alert alert-danger py-2 small">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="tab-content">

                <!-- ============ LOG IN ============ -->
                <div class="tab-pane fade <?= $activeTab === 'login' ? 'show active' : '' ?>" id="login-pane">
                    <form action="login.php" method="POST">
                        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                        <input type="hidden" name="action" value="login">

                        <div class="mb-3">
                            <label for="login-email" class="form-label">Institutional Email</label>
                            <input type="email" class="form-control" id="login-email" name="email"
                                   placeholder="username@rtu.edu.ph" required
                                   value="<?= $activeTab === 'login' ? old('email') : '' ?>">
                            <div class="text-end mt-1">
                                <a href="#" class="small text-decoration-none">Forgot password?</a>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="login-password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="login-password" name="password"
                                   placeholder="Your password" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2">Log In</button>
                    </form>
                </div>

                <!-- ============ SIGN UP ============ -->
                <div class="tab-pane fade <?= $activeTab === 'signup' ? 'show active' : '' ?>" id="signup-pane">
                    <form action="login.php" method="POST">
                        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                        <input type="hidden" name="action" value="register">

                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                                   placeholder="Juan Dela Cruz" required
                                   value="<?= $activeTab === 'signup' ? old('full_name') : '' ?>">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Institutional Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   placeholder="username@rtu.edu.ph" required
                                   value="<?= $activeTab === 'signup' ? old('email') : '' ?>">
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">I am a</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="" disabled <?= old('role') === '' ? 'selected' : '' ?>>Select role</option>
                                <option value="student" <?= old('role') === 'student' ? 'selected' : '' ?>>Student</option>
                                <option value="faculty" <?= old('role') === 'faculty' ? 'selected' : '' ?>>Faculty / Professor</option>
                                <option value="utility" <?= old('role') === 'utility' ? 'selected' : '' ?>>Utility</option>
                                <option value="guard"   <?= old('role') === 'guard'   ? 'selected' : '' ?>>Guard</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="At least 8 characters" required>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                   placeholder="Repeat your password" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2">Sign Up</button>
                    </form>
                </div>

            </div>

            <!-- ============ GOOGLE SIGN-IN (works from either tab) ============ -->
            <div class="d-flex align-items-center my-3">
                <hr class="flex-grow-1 m-0">
                <span class="px-3 small fw-semibold text-secondary">OR</span>
                <hr class="flex-grow-1 m-0">
            </div>

            <div id="g_id_onload"
                 data-client_id="<?= GOOGLE_CLIENT_ID ?>"
                 data-context="signin"
                 data-ux_mode="popup"
                 data-callback="handleCredentialResponse"
                 data-auto_select="false"
                 data-hd="<?= ALLOWED_DOMAIN ?>">
            </div>

            <div class="d-flex justify-content-center">
                <div class="g_id_signin"
                     data-type="standard"
                     data-shape="rectangular"
                     data-theme="outline"
                     data-text="continue_with"
                     data-size="large"
                     data-logo_alignment="left"
                     data-width="340">
                </div>
            </div>

            <div id="google-error" class="alert alert-danger py-2 small mt-3 d-none"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Google calls this after the user picks an account.
    // We send the token to google-login.php, where PHP verifies it.
    function handleCredentialResponse(response) {
        const box = document.getElementById('google-error');
        box.classList.add('d-none');

        fetch('google-login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                credential: response.credential,
                csrf: '<?= $_SESSION['csrf'] ?>'
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                box.textContent = data.message;
                box.classList.remove('d-none');
            }
        })
        .catch(() => {
            box.textContent = 'Something went wrong. Please try again.';
            box.classList.remove('d-none');
        });
    }
</script>
</body>
</html>
