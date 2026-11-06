<?php
require __DIR__ . '/includes/init.php';

$user = db_one("SELECT * FROM users WHERE id = ? AND role <> 'admin'", [(int) get('id')]);
if (!$user || ($user['status'] === 'suspended' && !is_admin())) {
    http_response_code(404);
    $pageTitle = 'Profile not found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container py-5">' . empty_state('bi-person-x', 'Profile not found', 'This account does not exist or is no longer active.') . '</div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$me = current_user();
$rating = user_rating((int) $user['id']);
$services = $user['role'] === 'worker'
    ? db_all(SERVICE_SELECT . ' WHERE s.user_id = ? AND s.is_active = 1 ORDER BY s.created_at DESC', [$user['id']])
    : [];
$jobs = $user['role'] === 'employer'
    ? db_all(JOB_SELECT . " WHERE j.employer_id = ? AND j.status = 'open' ORDER BY j.created_at DESC LIMIT 5", [$user['id']])
    : [];
$reviews = db_all('SELECT r.*, u.name, u.avatar FROM reviews r JOIN users u ON u.id = r.reviewer_id WHERE r.reviewee_id = ? ORDER BY r.created_at DESC LIMIT 20', [$user['id']]);
$completedJobs = (int) db_value("SELECT COUNT(*) FROM orders WHERE seller_id = ? AND status = 'completed'", [$user['id']])
    + (int) db_value("SELECT COUNT(*) FROM applications WHERE worker_id = ? AND status = 'accepted'", [$user['id']]);
$km = $me ? distance_km($me['latitude'], $me['longitude'], $user['latitude'], $user['longitude']) : null;

$pageTitle = $user['name'];
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card text-center sidebar-filter">
                <div class="card-body p-4">
                    <div class="position-relative d-inline-block mb-3">
                        <?= avatar($user, 112) ?>
                        <?php if (is_online($user['last_seen'])): ?><span class="online-dot position-absolute" style="bottom:8px;right:8px;width:16px;height:16px" title="Online"></span><?php endif; ?>
                    </div>
                    <h1 class="h4 fw-bold mb-1">
                        <?= e($user['name']) ?>
                        <?php if ($user['is_verified']): ?><i class="bi bi-patch-check-fill text-brand fs-5" title="Verified"></i><?php endif; ?>
                    </h1>
                    <div class="text-body-secondary small mb-2">
                        <?= e($user['role'] === 'employer' ? ($user['company_name'] ?: 'Employer') : 'Worker / Freelancer') ?>
                    </div>
                    <?php if ($rating['count']): ?>
                        <div class="mb-2"><?= stars($rating['avg']) ?> <span class="small text-body-secondary">(<?= $rating['count'] ?> review<?= $rating['count'] > 1 ? 's' : '' ?>)</span></div>
                    <?php endif; ?>
                    <?php if ($user['status'] === 'suspended'): ?><div class="mb-2"><?= status_badge('suspended') ?></div><?php endif; ?>
                    <?php if ($user['role'] === 'worker'): ?>
                        <span class="badge rounded-pill <?= $user['is_available'] ? 'text-bg-success' : 'text-bg-secondary' ?> mb-3">
                            <?= $user['is_available'] ? 'Available for work' : 'Not available right now' ?>
                        </span>
                    <?php endif; ?>

                    <ul class="list-unstyled text-start small border-top pt-3 mb-3">
                        <?php if ($user['county']): ?><li class="mb-2"><i class="bi bi-geo-alt me-2"></i><?= e(trim(($user['town'] ? $user['town'] . ', ' : '') . $user['county'])) ?><?= $km !== null ? ' · ' . e(format_km($km)) : '' ?></li><?php endif; ?>
                        <?php if ($user['hourly_rate']): ?><li class="mb-2"><i class="bi bi-cash me-2"></i><?= e(money($user['hourly_rate'])) ?> / hour</li><?php endif; ?>
                        <?php if ($user['experience_years'] !== null && $user['experience_years'] !== ''): ?><li class="mb-2"><i class="bi bi-award me-2"></i><?= (int) $user['experience_years'] ?> years experience</li><?php endif; ?>
                        <?php if ($user['role'] === 'worker'): ?><li class="mb-2"><i class="bi bi-check2-circle me-2"></i><?= $completedJobs ?> jobs completed</li><?php endif; ?>
                        <li class="mb-2"><i class="bi bi-calendar3 me-2"></i>Member since <?= e(date('M Y', strtotime($user['created_at']))) ?></li>
                        <li><i class="bi bi-clock-history me-2"></i><?= is_online($user['last_seen']) ? 'Online now' : 'Last seen ' . e(time_ago($user['last_seen']) ?: 'a while ago') ?></li>
                    </ul>

                    <?php if ($me && (int) $me['id'] !== (int) $user['id']): ?>
                        <a href="<?= e(url('messages.php', ['with' => $user['id']])) ?>" class="btn btn-brand w-100"><i class="bi bi-chat-dots"></i> Message <?= e(explode(' ', $user['name'])[0]) ?></a>
                    <?php elseif (!$me): ?>
                        <a href="<?= e(url('login.php')) ?>" class="btn btn-brand w-100">Sign in to contact</a>
                    <?php else: ?>
                        <a href="<?= e(url('profile.php')) ?>" class="btn btn-outline-brand w-100">Edit profile</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold">About</h5>
                    <p class="mb-3" style="white-space:pre-line"><?= $user['bio'] ? e($user['bio']) : '<span class="text-body-secondary">No bio yet.</span>' ?></p>
                    <?php if ($skills = skills_list($user['skills'])): ?>
                        <h6 class="fw-bold">Skills</h6>
                        <div><?php foreach ($skills as $s): ?><span class="chip"><?= e($s) ?></span><?php endforeach; ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($services): ?>
                <h5 class="fw-bold mb-3">Services by <?= e(explode(' ', $user['name'])[0]) ?></h5>
                <div class="row g-3 mb-4">
                    <?php foreach ($services as $s): ?><div class="col-md-6"><?= service_card($s) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($jobs): ?>
                <h5 class="fw-bold mb-3">Open jobs</h5>
                <?php foreach ($jobs as $j) echo job_card($j); ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Reviews <?php if ($rating['count']): ?><span class="text-body-secondary fw-normal fs-6">(<?= $rating['count'] ?>)</span><?php endif; ?></h5>
                    <?php if (!$reviews): ?>
                        <p class="text-body-secondary mb-0">No reviews yet.</p>
                    <?php endif; ?>
                    <?php foreach ($reviews as $r): ?>
                        <div class="d-flex gap-3 py-3 border-bottom">
                            <?= avatar($r, 40) ?>
                            <div>
                                <div class="fw-semibold"><?= e($r['name']) ?> <span class="small text-body-secondary fw-normal">· <?= e(time_ago($r['created_at'])) ?></span></div>
                                <?= stars((float) $r['rating'], false) ?>
                                <?php if ($r['comment']): ?><p class="mb-0 mt-1 small"><?= e($r['comment']) ?></p><?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
