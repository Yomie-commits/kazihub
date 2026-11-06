<?php
/** @var string $pageTitle */
$pageTitle = $pageTitle ?? APP_NAME;
$me = current_user();
$unread = $me ? unread_messages() : 0;
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$inAdmin = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false;
function nav_active(string $file): string
{
    return basename($_SERVER['SCRIPT_NAME'] ?? '') === $file ? ' active' : '';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle === APP_NAME ? APP_NAME . ' – ' . APP_TAGLINE : $pageTitle . ' | ' . APP_NAME) ?></title>
    <meta name="description" content="KaziHub connects Kenyan talent with freelance gigs, local juakali jobs and verified UAE opportunities.">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <?php if (!empty($useMap)): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <?php endif; ?>
    <link href="<?= e(url('assets/css/style.css')) ?>?v=1" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(url('index.php')) ?>">
            <span class="brand-mark"><i class="bi bi-briefcase-fill"></i></span>
            <span class="fw-bold">Kazi<span class="text-brand">Hub</span></span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto ms-lg-4 gap-lg-1">
                <li class="nav-item"><a class="nav-link<?= nav_active('services.php') ?>" href="<?= e(url('services.php')) ?>"><i class="bi bi-laptop me-1 d-lg-none"></i>Freelance</a></li>
                <li class="nav-item"><a class="nav-link<?= nav_active('juakali.php') ?>" href="<?= e(url('juakali.php')) ?>"><i class="bi bi-tools me-1 d-lg-none"></i>Juakali Jobs</a></li>
                <li class="nav-item"><a class="nav-link<?= nav_active('uae-jobs.php') ?>" href="<?= e(url('uae-jobs.php')) ?>"><i class="bi bi-airplane me-1 d-lg-none"></i>UAE Jobs</a></li>
            </ul>
            <ul class="navbar-nav align-items-lg-center gap-lg-2">
            <?php if ($me): ?>
                <?php if ($me['role'] === 'employer'): ?>
                    <li class="nav-item"><a class="btn btn-brand btn-sm my-2 my-lg-0" href="<?= e(url('job-post.php')) ?>"><i class="bi bi-plus-lg"></i> Post a job</a></li>
                <?php elseif ($me['role'] === 'worker'): ?>
                    <li class="nav-item"><a class="btn btn-outline-brand btn-sm my-2 my-lg-0" href="<?= e(url('service-edit.php')) ?>"><i class="bi bi-plus-lg"></i> New service</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link<?= nav_active('dashboard.php') ?>" href="<?= e(url($me['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php')) ?>">Dashboard</a></li>
                <li class="nav-item">
                    <a class="nav-link position-relative<?= nav_active('messages.php') ?>" href="<?= e(url('messages.php')) ?>" aria-label="Messages">
                        <i class="bi bi-chat-dots fs-5 d-none d-lg-inline"></i><span class="d-lg-none">Messages</span>
                        <?php if ($unread): ?><span class="badge rounded-pill text-bg-danger nav-badge"><?= $unread ?></span><?php endif; ?>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?= avatar($me, 32) ?> <span class="d-lg-none"><?= e($me['name']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><h6 class="dropdown-header"><?= e($me['name']) ?><br><small class="text-body-secondary"><?= e(ucfirst($me['role'])) ?></small></h6></li>
                        <li><a class="dropdown-item" href="<?= e(url('user.php', ['id' => $me['id']])) ?>"><i class="bi bi-person me-2"></i>My public profile</a></li>
                        <li><a class="dropdown-item" href="<?= e(url('profile.php')) ?>"><i class="bi bi-gear me-2"></i>Edit profile</a></li>
                        <li><a class="dropdown-item" href="<?= e(url('orders.php')) ?>"><i class="bi bi-bag me-2"></i>Orders</a></li>
                        <?php if ($me['role'] === 'worker'): ?>
                            <li><a class="dropdown-item" href="<?= e(url('my-services.php')) ?>"><i class="bi bi-grid me-2"></i>My services</a></li>
                            <li><a class="dropdown-item" href="<?= e(url('applications.php')) ?>"><i class="bi bi-send me-2"></i>My applications</a></li>
                        <?php elseif ($me['role'] === 'employer'): ?>
                            <li><a class="dropdown-item" href="<?= e(url('my-jobs.php')) ?>"><i class="bi bi-briefcase me-2"></i>My job posts</a></li>
                        <?php endif; ?>
                        <?php if ($me['role'] === 'admin'): ?>
                            <li><a class="dropdown-item" href="<?= e(url('admin/index.php')) ?>"><i class="bi bi-shield-lock me-2"></i>Admin panel</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="post" action="<?= e(url('logout.php')) ?>"><?= csrf_field() ?>
                                <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button>
                            </form>
                        </li>
                    </ul>
                </li>
            <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="<?= e(url('login.php')) ?>">Sign in</a></li>
                <li class="nav-item"><a class="btn btn-brand my-2 my-lg-0" href="<?= e(url('register.php')) ?>">Join KaziHub</a></li>
            <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="flex-grow-1">
<?php $flashes = get_flashes(); if ($flashes): ?>
    <div class="container mt-3">
        <?php foreach ($flashes as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
                <?= e($f['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
