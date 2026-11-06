<?php
require __DIR__ . '/includes/init.php';
$me = require_login();
if ($me['role'] === 'admin') {
    redirect('admin/index.php');
}
$uid = (int) $me['id'];

// Quick availability toggle for workers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'availability' && $me['role'] === 'worker') {
    db_exec('UPDATE users SET is_available = ? WHERE id = ?', [$me['is_available'] ? 0 : 1, $uid]);
    flash('success', $me['is_available'] ? 'You are now hidden from “available workers”.' : 'You are now shown as available for work.');
    redirect('dashboard.php');
}

$completion = profile_completion($me);
$rating = user_rating($uid);
$unread = unread_messages();

if ($me['role'] === 'worker') {
    $stats = [
        ['Active orders', (int) db_value("SELECT COUNT(*) FROM orders WHERE seller_id = ? AND status IN ('pending','in_progress','delivered')", [$uid]), 'bi-bag', 'primary', 'orders.php?tab=selling'],
        ['Applications', (int) db_value("SELECT COUNT(*) FROM applications WHERE worker_id = ? AND status IN ('pending','shortlisted')", [$uid]), 'bi-send', 'warning', 'applications.php'],
        ['Earned (KES)', number_format((float) db_value("SELECT COALESCE(SUM(price),0) FROM orders WHERE seller_id = ? AND status = 'completed'", [$uid])), 'bi-cash-stack', 'success', 'orders.php?tab=selling'],
        ['Rating', $rating['count'] ? number_format($rating['avg'], 1) . ' ★' : '–', 'bi-star', 'info', 'user.php?id=' . $uid],
    ];
    $recentOrders = db_all("SELECT o.*, u.name AS other_name FROM orders o JOIN users u ON u.id = o.buyer_id
        WHERE o.seller_id = ? ORDER BY o.updated_at DESC, o.id DESC LIMIT 5", [$uid]);
    $recentApps = db_all('SELECT a.*, j.title FROM applications a JOIN jobs j ON j.id = a.job_id WHERE a.worker_id = ? ORDER BY a.created_at DESC LIMIT 5', [$uid]);

    // Recommended juakali jobs: matching skills, nearest first when location is known
    $skills = skills_list($me['skills']);
    $params = [];
    $match = '';
    if ($skills) {
        $parts = [];
        foreach (array_slice($skills, 0, 6) as $s) {
            $parts[] = '(j.title LIKE ? OR j.category LIKE ? OR j.description LIKE ?)';
            array_push($params, "%$s%", "%$s%", "%$s%");
        }
        $match = ' AND (' . implode(' OR ', $parts) . ')';
    }
    $recommended = db_all(JOB_SELECT . " WHERE j.status = 'open' AND j.type = 'juakali'$match
        AND j.id NOT IN (SELECT job_id FROM applications WHERE worker_id = ?) ORDER BY j.created_at DESC LIMIT 50", array_merge($params, [$uid]));
    if (!$recommended) {
        $recommended = db_all(JOB_SELECT . " WHERE j.status = 'open' AND j.type = 'juakali' AND j.id NOT IN (SELECT job_id FROM applications WHERE worker_id = ?) ORDER BY j.created_at DESC LIMIT 50", [$uid]);
    }
    foreach ($recommended as &$j) $j['km'] = distance_km($me['latitude'], $me['longitude'], $j['latitude'], $j['longitude']);
    unset($j);
    if ($me['latitude']) usort($recommended, fn($a, $b) => ($a['km'] ?? PHP_FLOAT_MAX) <=> ($b['km'] ?? PHP_FLOAT_MAX));
    $recommended = array_slice($recommended, 0, 4);
} else {
    $stats = [
        ['Open jobs', (int) db_value("SELECT COUNT(*) FROM jobs WHERE employer_id = ? AND status = 'open'", [$uid]), 'bi-briefcase', 'primary', 'my-jobs.php'],
        ['New applicants', (int) db_value("SELECT COUNT(*) FROM applications a JOIN jobs j ON j.id = a.job_id WHERE j.employer_id = ? AND a.status = 'pending'", [$uid]), 'bi-people', 'warning', 'my-jobs.php'],
        ['Pending review', (int) db_value("SELECT COUNT(*) FROM jobs WHERE employer_id = ? AND status = 'pending'", [$uid]), 'bi-hourglass-split', 'info', 'my-jobs.php'],
        ['Active orders', (int) db_value("SELECT COUNT(*) FROM orders WHERE buyer_id = ? AND status IN ('pending','in_progress','delivered')", [$uid]), 'bi-bag', 'success', 'orders.php?tab=buying'],
    ];
    $myJobs = db_all("SELECT j.*, (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id AND a.status <> 'withdrawn') AS applicant_count,
        (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id AND a.status = 'pending') AS new_count
        FROM jobs j WHERE j.employer_id = ? ORDER BY j.created_at DESC LIMIT 5", [$uid]);
    $recentApplicants = db_all("SELECT a.*, u.name, u.avatar, j.title FROM applications a JOIN jobs j ON j.id = a.job_id
        JOIN users u ON u.id = a.worker_id WHERE j.employer_id = ? AND a.status <> 'withdrawn' ORDER BY a.created_at DESC LIMIT 6", [$uid]);
    $recentOrders = db_all('SELECT o.*, u.name AS other_name FROM orders o JOIN users u ON u.id = o.seller_id WHERE o.buyer_id = ? ORDER BY o.updated_at DESC, o.id DESC LIMIT 5', [$uid]);
}

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <?= avatar($me, 56) ?>
            <div>
                <h1 class="h4 fw-bold mb-0">Hi, <?= e(explode(' ', $me['name'])[0]) ?> 👋</h1>
                <span class="text-body-secondary small"><?= $me['role'] === 'worker' ? 'Worker / Freelancer' : 'Employer' ?> dashboard</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <?php if ($me['role'] === 'worker'): ?>
                <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="availability">
                    <button class="btn <?= $me['is_available'] ? 'btn-success' : 'btn-outline-secondary' ?>">
                        <i class="bi <?= $me['is_available'] ? 'bi-toggle-on' : 'bi-toggle-off' ?>"></i> <?= $me['is_available'] ? 'Available for work' : 'Not available' ?>
                    </button>
                </form>
                <a href="<?= e(url('service-edit.php')) ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> New service</a>
            <?php else: ?>
                <a href="<?= e(url('job-post.php')) ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Post a job</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($completion < 100): ?>
        <div class="card bg-brand-soft border-0 mb-4">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <i class="bi bi-person-check fs-3 text-brand"></i>
                <div class="flex-grow-1">
                    <strong>Your profile is <?= $completion ?>% complete.</strong>
                    <div class="small text-body-secondary">Add a photo, location<?= $me['role'] === 'worker' ? ', skills and rate' : '' ?> so people trust you and can find you nearby.</div>
                    <div class="progress mt-2" style="height:6px;max-width:360px"><div class="progress-bar bg-brand" style="width:<?= $completion ?>%"></div></div>
                </div>
                <a href="<?= e(url('profile.php')) ?>" class="btn btn-brand">Complete profile</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <?php foreach ($stats as [$label, $value, $icon, $color, $link]): ?>
            <div class="col-6 col-lg-3">
                <a href="<?= e(url($link)) ?>" class="card stat-card card-hover text-decoration-none h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-<?= $color ?>-subtle text-<?= $color ?>-emphasis"><i class="bi <?= $icon ?>"></i></div>
                        <div><div class="stat-value"><?= e($value) ?></div><div class="small text-body-secondary"><?= e($label) ?></div></div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($unread): ?>
        <div class="alert alert-info d-flex align-items-center justify-content-between">
            <span><i class="bi bi-envelope"></i> You have <?= $unread ?> unread message<?= $unread > 1 ? 's' : '' ?>.</span>
            <a href="<?= e(url('messages.php')) ?>" class="btn btn-sm btn-info">Open inbox</a>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php if ($me['role'] === 'worker'): ?>
            <div class="col-lg-7">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="fw-bold mb-0">Recommended jobs for you</h5>
                    <a href="<?= e(url('juakali.php')) ?>" class="small">Browse all</a>
                </div>
                <?php if (!$recommended): ?><div class="card"><?= empty_state('bi-tools', 'No new jobs right now', 'Check back soon, or add more skills to your profile.') ?></div><?php endif; ?>
                <?php foreach ($recommended as $j) echo job_card($j, $j['km']); ?>
            </div>
            <div class="col-lg-5">
                <div class="card mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center"><strong>Recent orders</strong><a href="<?= e(url('orders.php', ['tab' => 'selling'])) ?>" class="small">All</a></div>
                    <ul class="list-group list-group-flush">
                        <?php if (!$recentOrders): ?><li class="list-group-item small text-body-secondary">No orders yet. <a href="<?= e(url('my-services.php')) ?>">Manage your services</a></li><?php endif; ?>
                        <?php foreach ($recentOrders as $o): ?>
                            <a href="<?= e(url('orders.php', ['id' => $o['id']])) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div class="min-w-0"><div class="small fw-semibold text-truncate"><?= e($o['title']) ?></div><div class="small text-body-secondary"><?= e($o['other_name']) ?> · <?= e(money($o['price'])) ?></div></div>
                                <?= status_badge($o['status']) ?>
                            </a>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center"><strong>My applications</strong><a href="<?= e(url('applications.php')) ?>" class="small">All</a></div>
                    <ul class="list-group list-group-flush">
                        <?php if (!$recentApps): ?><li class="list-group-item small text-body-secondary">You haven't applied for any jobs yet.</li><?php endif; ?>
                        <?php foreach ($recentApps as $a): ?>
                            <a href="<?= e(url('job.php', ['id' => $a['job_id']])) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div class="min-w-0"><div class="small fw-semibold text-truncate"><?= e($a['title']) ?></div><div class="small text-body-secondary"><?= e(time_ago($a['created_at'])) ?></div></div>
                                <?= status_badge($a['status']) ?>
                            </a>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php else: ?>
            <div class="col-lg-7">
                <div class="card mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center"><strong>Your jobs</strong><a href="<?= e(url('my-jobs.php')) ?>" class="small">Manage</a></div>
                    <ul class="list-group list-group-flush">
                        <?php if (!$myJobs): ?><li class="list-group-item"><?= empty_state('bi-briefcase', 'No jobs posted yet', '', '<a class="btn btn-brand btn-sm" href="' . e(url('job-post.php')) . '">Post your first job</a>') ?></li><?php endif; ?>
                        <?php foreach ($myJobs as $j): ?>
                            <a href="<?= e(url('job.php', ['id' => $j['id'], 'tab' => 'applicants'])) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate"><?= e($j['title']) ?></div>
                                    <div class="small text-body-secondary"><?= $j['type'] === 'uae' ? 'UAE' : 'Juakali' ?> · <?= (int) $j['applicant_count'] ?> applicant<?= $j['applicant_count'] == 1 ? '' : 's' ?><?php if ($j['new_count']): ?> · <span class="text-danger fw-semibold"><?= (int) $j['new_count'] ?> new</span><?php endif; ?></div>
                                </div>
                                <?= status_badge($j['status']) ?>
                            </a>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center"><strong>Services you ordered</strong><a href="<?= e(url('orders.php')) ?>" class="small">All</a></div>
                    <ul class="list-group list-group-flush">
                        <?php if (!$recentOrders): ?><li class="list-group-item small text-body-secondary">Need something done online? <a href="<?= e(url('services.php')) ?>">Hire a freelancer</a>.</li><?php endif; ?>
                        <?php foreach ($recentOrders as $o): ?>
                            <a href="<?= e(url('orders.php', ['id' => $o['id']])) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div class="min-w-0"><div class="small fw-semibold text-truncate"><?= e($o['title']) ?></div><div class="small text-body-secondary"><?= e($o['other_name']) ?> · <?= e(money($o['price'])) ?></div></div>
                                <?= status_badge($o['status']) ?>
                            </a>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card mb-4">
                    <div class="card-header bg-white"><strong>Latest applicants</strong></div>
                    <ul class="list-group list-group-flush">
                        <?php if (!$recentApplicants): ?><li class="list-group-item small text-body-secondary">Applicants will appear here.</li><?php endif; ?>
                        <?php foreach ($recentApplicants as $a): ?>
                            <a href="<?= e(url('job.php', ['id' => $a['job_id'], 'tab' => 'applicants'])) ?>" class="list-group-item list-group-item-action d-flex gap-2 align-items-center">
                                <?= avatar($a, 36) ?>
                                <div class="flex-grow-1 min-w-0"><div class="small fw-semibold"><?= e($a['name']) ?></div><div class="small text-body-secondary text-truncate"><?= e($a['title']) ?></div></div>
                                <?= status_badge($a['status']) ?>
                            </a>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h6 class="fw-bold"><i class="bi bi-geo-alt text-brand"></i> Need someone today?</h6>
                        <p class="small text-body-secondary">See verified workers who are available near you right now.</p>
                        <a href="<?= e(url('juakali.php', ['view' => 'workers'])) ?>" class="btn btn-outline-brand btn-sm">Find available workers</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
