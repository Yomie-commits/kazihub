<?php
// Toggle "available for work now" for juakali workers.
require __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json');

$user = current_user();
if (!$user || !is_post()) {
    http_response_code(401);
    exit(json_encode(['ok' => false]));
}
verify_csrf();
$available = post('available') === '1' ? 1 : 0;
db_exec('UPDATE users SET is_available = ? WHERE id = ?', [$available, $user['id']]);
echo json_encode(['ok' => true, 'available' => (bool)$available]);
