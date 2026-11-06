<?php
require __DIR__ . '/includes/init.php';

$q = get('q');
$targets = ['services' => 'services.php', 'juakali' => 'juakali.php', 'uae' => 'uae-jobs.php'];
$target = $targets[get('in')] ?? 'services.php';
redirect($target, $q !== '' ? ['q' => $q] : []);
