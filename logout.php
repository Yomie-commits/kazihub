<?php
require __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Wipe the session data and issue a brand-new session id
    $_SESSION = [];
    session_regenerate_id(true);
    flash('info', 'You have been signed out.');
}
redirect('index.php');
