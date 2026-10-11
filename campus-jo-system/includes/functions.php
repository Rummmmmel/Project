<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';          // gives $pdo

function db_all(string $sql, array $p = []): array { global $pdo; $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchAll(); }
function db_one(string $sql, array $p = []) { global $pdo; $s = $pdo->prepare($sql); $s->execute($p); return $s->fetch(); }
function db_val(string $sql, array $p = []) { global $pdo; $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchColumn(); }
function db_run(string $sql, array $p = []): void { global $pdo; $pdo->prepare($sql)->execute($p); }

function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(400); die('Invalid request. Go back and try again.'); }
}
function audit(?int $userId, string $action, string $record, string $details, string $outcome = 'Success'): void {
    db_run('INSERT INTO audit_logs (user_id, action, record, details, outcome) VALUES (?,?,?,?,?)', [$userId, $action, $record, $details, $outcome]);
}
function notify(int $userId, string $message): void {
    db_run('INSERT INTO notifications (user_id, message) VALUES (?,?)', [$userId, $message]);
}
function team_for(string $category): string {
    return ['Electrical'=>'Electrical Team','Plumbing'=>'Plumbing Team','Carpentry'=>'Carpentry Team','Cleanliness'=>'Cleaning Team','IT Equipment'=>'IT Support'][$category] ?? 'General Maintenance';
}
function when_label(string $ts): string {
    $d = date('Y-m-d', strtotime($ts));
    if ($d === date('Y-m-d')) return 'Today';
    if ($d === date('Y-m-d', strtotime('-1 day'))) return 'Yesterday';
    return date('M j', strtotime($ts));
}
function prio(array $r): string { return $r['final_priority'] ?: ($r['ai_priority'] ?: 'Medium'); }
function subject(array $r): string {
    $d = trim(preg_replace('/\s+/', ' ', $r['description']));
    if (mb_strlen($d) > 60) $d = mb_substr($d, 0, 57) . '...';
    return $d . ' — ' . $r['location'];
}
