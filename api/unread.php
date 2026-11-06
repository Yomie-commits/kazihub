<?php
require __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json');
echo json_encode(['unread' => is_logged_in() ? unread_count(current_user_id()) : 0]);
