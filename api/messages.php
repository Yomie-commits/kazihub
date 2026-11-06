<?php
// Returns new messages in a conversation (used for live chat updates).
require __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    exit(json_encode(['messages' => []]));
}
$me = current_user_id();
$with = get_int('with');
$after = get_int('after');

$rows = db_all(
    'SELECT id, sender_id, body, created_at FROM messages
     WHERE id > ? AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) ORDER BY id ASC LIMIT 50',
    [$after, $with, $me, $me, $with]
);
db_exec('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND id > ?', [$with, $me, $after]);

echo json_encode(['messages' => array_map(fn($m) => [
    'id'   => (int)$m['id'],
    'mine' => (int)$m['sender_id'] === $me,
    'body' => $m['body'],
    'time' => date('j M, H:i', strtotime($m['created_at'])),
], $rows)]);
