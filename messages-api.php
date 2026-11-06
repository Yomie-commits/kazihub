<?php
/**
 * Small JSON endpoint for the chat window.
 * GET  ?with=ID&after=LASTID   -> new messages in the conversation
 * POST to=ID, body=TEXT, after -> send a message, returns new messages
 */
define('SKIP_CSRF', true); // checked manually below so we can answer in JSON
require __DIR__ . '/includes/init.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function json_out($data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$me = current_user();
if (!$me) {
    json_out(['error' => 'Not signed in'], 401);
}
$uid = (int) $me['id'];
$partnerId = (int) ($_GET['with'] ?? $_POST['to'] ?? 0);
$partner = $partnerId && $partnerId !== $uid ? db_one('SELECT id, status FROM users WHERE id = ?', [$partnerId]) : null;
if (!$partner) {
    json_out(['error' => 'Unknown user'], 404);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        json_out(['error' => 'Session expired'], 419);
    }
    $body = trim((string) ($_POST['body'] ?? ''));
    if ($body === '' || $partner['status'] !== 'active') {
        json_out(['error' => 'Cannot send'], 422);
    }
    db_insert('messages', ['sender_id' => $uid, 'receiver_id' => $partner['id'], 'body' => mb_substr($body, 0, 4000), 'created_at' => now()]);
}

$after = (int) ($_GET['after'] ?? $_POST['after'] ?? 0);
$rows = db_all('SELECT id, sender_id, body, created_at FROM messages
    WHERE id > ? AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
    ORDER BY id ASC LIMIT 100', [$after, $uid, $partner['id'], $partner['id'], $uid]);
db_exec('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0 AND id > ?', [$partner['id'], $uid, $after]);

json_out(array_map(fn($m) => [
    'id' => (int) $m['id'],
    'mine' => (int) $m['sender_id'] === $uid,
    'body' => $m['body'],
    'time' => date('j M, H:i', strtotime($m['created_at'])),
], $rows));
