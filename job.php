<?php
require __DIR__ . '/includes/init.php';

$me = current_user();
$job = db_one(JOB_SELECT . ' WHERE j.id = ?', [(int) (get('id') ?: post('job_id'))]);
$isOwner = $me && $job && (int) $me['id'] === (int) $job['employer_id'];
$myApp = ($me && $job) ? db_one('SELECT * FROM applications WHERE job_id = ? AND worker_id = ?', [$job['id'], $me['id']]) : null;

$canView = $job && ($job['status'] === 'open' || $isOwner || is_admin() || ($job['status'] === 'closed' && $myApp));
if (!$canView) {
    http_response_code(404);
    $pageTitle = 'Job not found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container py-5">' . empty_state('bi-briefcase', 'Job not found', 'This job may have been filled or removed.', '<a class="btn btn-brand" href="' . e(url('juakali.php')) . '">Browse jobs</a>') . '</div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
$deadlinePassed = $job['deadline'] && $job['deadline'] < date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $me = require_login();
    $action = post('action');
    $back = ['id' => $job['id']];

    if ($action === 'apply') {
        if ($me['role'] !== 'worker') {
            flash('warning', 'Only worker accounts can apply for jobs.');
        } elseif ($job['status'] !== 'open' || $deadlinePassed) {
            flash('warning', 'This job is no longer accepting applications.');
        } elseif ($myApp) {
            flash('info', 'You have already applied for this job.');
        } else {
            $cover = post('cover_letter');
            $pay = post('expected_pay');
            if (mb_strlen($cover) < 20) {
                flash('danger', 'Tell the employer why you are a good fit (at least 20 characters).');
                redirect('job.php', $back);
            }
            db_insert('applications', [
                'job_id' => $job['id'],
                'worker_id' => $me['id'],
                'cover_letter' => mb_substr($cover, 0, 3000),
                'expected_pay' => is_numeric($pay) && $pay >= 0 ? (float) $pay : null,
                'status' => 'pending',
                'created_at' => now(),
            ]);
            db_insert('messages', [
                'sender_id' => $me['id'],
                'receiver_id' => $job['employer_id'],
                'body' => 'I just applied for your job "' . $job['title'] . "\".\n\n" . $cover,
                'created_at' => now(),
            ]);
            flash('success', 'Application sent! The employer will contact you here on KaziHub.');
        }
    } elseif ($action === 'withdraw' && $myApp && in_array($myApp['status'], ['pending', 'shortlisted'], true)) {
        db_exec("UPDATE applications SET status = 'withdrawn' WHERE id = ?", [$myApp['id']]);
        flash('info', 'Application withdrawn.');
    } elseif ($action === 'app_status' && ($isOwner || is_admin())) {
        $app = db_one('SELECT * FROM applications WHERE id = ? AND job_id = ?', [(int) post('application_id'), $job['id']]);
        $status = post('status');
        if ($app && $app['status'] !== 'withdrawn' && in_array($status, ['shortlisted', 'accepted', 'rejected', 'pending'], true)) {
            db_exec('UPDATE applications SET status = ? WHERE id = ?', [$status, $app['id']]);
            $notes = [
                'shortlisted' => 'Good news! You have been shortlisted for "%s".',
                'accepted' => 'Congratulations! Your application for "%s" has been accepted. Let\'s agree on the next steps here.',
                'rejected' => 'Thank you for applying for "%s". The employer has chosen other candidates this time.',
            ];
            if (isset($notes[$status])) {
                db_insert('messages', ['sender_id' => $me['id'], 'receiver_id' => $app['worker_id'], 'body' => sprintf($notes[$status], $job['title']), 'created_at' => now()]);
            }
            flash('success', 'Applicant marked as ' . $status . '.');
        }
        $back['tab'] = 'applicants';
    } elseif ($action === 'review_worker' && $isOwner) {
        $app = db_one("SELECT * FROM applications WHERE id = ? AND job_id = ? AND status = 'accepted'", [(int) post('application_id'), $job['id']]);
        $rating = (int) post('rating');
        if (!$app) {
            flash('danger', 'You can only review workers you accepted.');
        } elseif (db_value('SELECT id FROM reviews WHERE application_id = ?', [$app['id']])) {
            flash('info', 'You already reviewed this worker for this job.');
        } elseif ($rating < 1 || $rating > 5) {
            flash('danger', 'Please choose a star rating.');
        } else {
            db_insert('reviews', [
                'reviewer_id' => $me['id'], 'reviewee_id' => $app['worker_id'], 'application_id' => $app['id'],
                'rating' => $rating, 'comment' => mb_substr(post('comment'), 0, 1000) ?: null, 'created_at' => now(),
            ]);
            flash('success', 'Review saved. Thank you!');
        }
        $back['tab'] = 'applicants';
    } elseif ($action === 'report') {
        $reason = post('reason');
        if (mb_strlen($reason) < 10) {
            flash('danger', 'Please tell us what is wrong (at least 10 characters).');
        } else {
            db_insert('reports', ['reporter_id' => $me['id'], 'job_id' => $job['id'], 'reason' => mb_substr($reason, 0, 2000), 'created_at' => now()]);
            flash('success', 'Thank you. Our team will review this listing.');
        }
    }
    redirect('job.php', $back);
}

$applicants = [];
if ($isOwner || is_admin()) {
    $applicants = db_all('SELECT a.*, u.name, u.avatar, u.is_verified, u.skills, u.county, u.town, u.phone,
            (SELECT AVG(r.rating) FROM reviews r WHERE r.reviewee_id = u.id) AS rating,
            (SELECT COUNT(*) FROM reviews r WHERE r.reviewee_id = u.id) AS review_count,
            (SELECT COUNT(*) FROM reviews r WHERE r.application_id = a.id) AS reviewed
        FROM applications a JOIN users u ON u.id = a.worker_id WHERE a.job_id = ?
        ORDER BY CASE a.status WHEN \'accepted\' THEN 0 WHEN \'shortlisted\' THEN 1 WHEN \'pending\' THEN 2 ELSE 3 END, a.created_at', [$job['id']]);
}
$km = $me ? distance_km($me['latitude'], $me['longitude'], $job['latitude'], $job['longitude']) : null;
$isUae = $job['type'] === 'uae';
$tab = get('tab') === 'applicants' && ($isOwner || is_admin()) ? 'applicants' : 'details';

$pageTitle = $job['title'];
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= e(url($isUae ? 'uae-jobs.php' : 'juakali.php')) ?>"><?= $isUae ? 'UAE jobs' : 'Juakali jobs' ?></a></li>
            <li class="breadcrumb-item"><a href="<?= e(url($isUae ? 'uae-jobs.php' : 'juakali.php', ['category' => $job['category']])) ?>"><?= e($job['category']) ?></a></li>
        </ol>
    </nav>

    <?php if ($job['status'] === 'pending'): ?>
        <div class="alert alert-warning"><i class="bi bi-hourglass-split"></i> This job is waiting for admin approval and is not public yet.</div>
    <?php elseif ($job['status'] === 'rejected'): ?>
        <div class="alert alert-danger"><i class="bi bi-x-circle"></i> This listing was not approved. Edit it with the correct company details to submit it again, or <a href="<?= e(url('contact.php')) ?>">contact us</a>.</div>
    <?php elseif ($job['status'] === 'closed'): ?>
        <div class="alert alert-secondary">This job is closed and no longer accepts applications.</div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body p-4">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="feature-icon flex-shrink-0 <?= $isUae ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-warning-emphasis' ?>"><i class="bi <?= category_icon($job['category']) ?>"></i></div>
                        <div class="flex-grow-1">
                            <h1 class="h4 fw-bold mb-1"><?= e($job['title']) ?></h1>
                            <div class="text-body-secondary small">
                                <?= e($isUae ? $job['company'] : $job['employer_name']) ?>
                                <?php if ($job['employer_verified']): ?><i class="bi bi-patch-check-fill text-brand" title="Verified employer"></i><?php endif; ?>
                                · Posted <?= e(time_ago($job['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mt-2 small">
                        <div class="col-6 col-md-3"><div class="text-body-secondary">Pay</div><strong class="text-brand"><?= e(pay_range($job)) ?></strong></div>
                        <div class="col-6 col-md-3"><div class="text-body-secondary">Location</div><strong><?= e(trim(($job['town'] ? $job['town'] . ', ' : '') . $job['county'], ', ')) ?></strong><?php if ($km !== null): ?><div><?= e(format_km($km)) ?></div><?php endif; ?></div>
                        <div class="col-6 col-md-3"><div class="text-body-secondary">Workers needed</div><strong><?= (int) $job['positions'] ?></strong></div>
                        <div class="col-6 col-md-3"><div class="text-body-secondary">Apply by</div><strong><?= $job['deadline'] ? e(date('j M Y', strtotime($job['deadline']))) : 'Open' ?></strong></div>
                    </div>
                    <div class="mt-3">
                        <span class="chip"><?= e($job['category']) ?></span>
                        <?php if ($job['urgency'] === 'urgent'): ?><span class="chip bg-danger-subtle text-danger-emphasis"><i class="bi bi-lightning-fill"></i> Urgent</span><?php endif; ?>
                        <?php if ($isUae): ?>
                            <span class="chip <?= $job['visa_sponsored'] ? 'bg-primary-subtle text-primary-emphasis' : '' ?>"><i class="bi bi-passport"></i> Visa <?= $job['visa_sponsored'] ? 'sponsored' : 'not sponsored' ?></span>
                            <span class="chip <?= $job['accommodation'] ? 'bg-info-subtle text-info-emphasis' : '' ?>"><i class="bi bi-house"></i> Accommodation <?= $job['accommodation'] ? 'provided' : 'not provided' ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($isOwner || is_admin()): ?>
                <ul class="nav nav-tabs">
                    <li class="nav-item"><a class="nav-link <?= $tab === 'details' ? 'active' : '' ?>" href="<?= e(url('job.php', ['id' => $job['id']])) ?>">Details</a></li>
                    <li class="nav-item"><a class="nav-link <?= $tab === 'applicants' ? 'active' : '' ?>" href="<?= e(url('job.php', ['id' => $job['id'], 'tab' => 'applicants'])) ?>">Applicants <span class="badge text-bg-secondary"><?= count($applicants) ?></span></a></li>
                </ul>
            <?php endif; ?>

            <?php if ($tab === 'details'): ?>
                <div class="card <?= ($isOwner || is_admin()) ? 'border-top-0 rounded-top-0' : '' ?>">
                    <div class="card-body p-4">
                        <h5 class="fw-bold">Job description</h5>
                        <p class="mb-0" style="white-space:pre-line"><?= e($job['description']) ?></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="card border-top-0 rounded-top-0">
                    <?php if (!$applicants): ?>
                        <?= empty_state('bi-people', 'No applications yet', 'Share your job link to reach more workers.') ?>
                    <?php endif; ?>
                    <?php foreach ($applicants as $a): ?>
                        <div class="p-4 border-bottom">
                            <div class="d-flex flex-wrap gap-3 justify-content-between">
                                <div class="d-flex gap-3">
                                    <?= avatar($a, 52) ?>
                                    <div>
                                        <a href="<?= e(url('user.php', ['id' => $a['worker_id']])) ?>" class="fw-bold text-body text-decoration-none"><?= e($a['name']) ?></a>
                                        <?php if ($a['is_verified']): ?><i class="bi bi-patch-check-fill text-brand"></i><?php endif; ?>
                                        <?= status_badge($a['status']) ?>
                                        <div class="small text-body-secondary">
                                            <?= e(trim(($a['town'] ? $a['town'] . ', ' : '') . $a['county'], ', ')) ?> · applied <?= e(time_ago($a['created_at'])) ?>
                                            <?php if ($a['review_count']): ?> · <?= stars((float) $a['rating']) ?><?php endif; ?>
                                        </div>
                                        <?php if ($a['expected_pay']): ?><div class="small">Expected pay: <strong><?= e(money($a['expected_pay'], $job['currency'])) ?></strong></div><?php endif; ?>
                                        <?php if ($a['status'] === 'accepted' && $a['phone']): ?><div class="small"><i class="bi bi-telephone"></i> <?= e($a['phone']) ?></div><?php endif; ?>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-1 align-items-start">
                                    <a href="<?= e(url('messages.php', ['with' => $a['worker_id']])) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chat-dots"></i></a>
                                    <?php if ($a['status'] !== 'withdrawn'): ?>
                                        <?php foreach (['shortlisted' => ['Shortlist', 'btn-outline-info'], 'accepted' => ['Accept', 'btn-brand'], 'rejected' => ['Reject', 'btn-outline-danger']] as $st => $btn): if ($a['status'] === $st) continue; ?>
                                            <form method="post"><?= csrf_field() ?>
                                                <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>"><input type="hidden" name="action" value="app_status">
                                                <input type="hidden" name="application_id" value="<?= (int) $a['id'] ?>"><input type="hidden" name="status" value="<?= $st ?>">
                                                <button class="btn btn-sm <?= $btn[1] ?>"><?= $btn[0] ?></button>
                                            </form>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($a['cover_letter']): ?><p class="small mt-3 mb-0 bg-body-tertiary rounded p-3" style="white-space:pre-line"><?= e($a['cover_letter']) ?></p><?php endif; ?>
                            <?php if ($isOwner && $a['status'] === 'accepted' && !$a['reviewed']): ?>
                                <form method="post" class="mt-3 border rounded p-3">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>"><input type="hidden" name="action" value="review_worker">
                                    <input type="hidden" name="application_id" value="<?= (int) $a['id'] ?>">
                                    <div class="small fw-semibold mb-1">Job done? Rate <?= e(explode(' ', $a['name'])[0]) ?>'s work</div>
                                    <?= rating_picker() ?>
                                    <textarea class="form-control form-control-sm my-2" name="comment" rows="2" maxlength="1000" placeholder="Optional comment"></textarea>
                                    <button class="btn btn-sm btn-brand">Save review</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card sidebar-filter shadow-sm">
                <div class="card-body p-4">
                    <?php if ($isOwner): ?>
                        <h6 class="fw-bold">Manage this job</h6>
                        <p class="small text-body-secondary"><?= (int) $job['applicant_count'] ?> application<?= $job['applicant_count'] == 1 ? '' : 's' ?> so far.</p>
                        <div class="d-grid gap-2">
                            <a href="<?= e(url('job.php', ['id' => $job['id'], 'tab' => 'applicants'])) ?>" class="btn btn-brand">View applicants</a>
                            <a href="<?= e(url('job-post.php', ['id' => $job['id']])) ?>" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i> Edit job</a>
                            <?php if ($job['status'] === 'open' || $job['status'] === 'pending'): ?>
                                <form method="post" action="<?= e(url('job-post.php')) ?>" class="d-grid"><?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $job['id'] ?>"><input type="hidden" name="action" value="close">
                                    <button class="btn btn-outline-danger" data-confirm="Close this job? It will stop accepting applications.">Close job</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php elseif (!$me): ?>
                        <h6 class="fw-bold">Interested?</h6>
                        <p class="small text-body-secondary">Create a free worker account to apply.</p>
                        <a href="<?= e(url('register.php', ['role' => 'worker'])) ?>" class="btn btn-brand w-100 mb-2">Join to apply</a>
                        <a href="<?= e(url('login.php')) ?>" class="btn btn-outline-secondary w-100">Sign in</a>
                    <?php elseif ($myApp): ?>
                        <h6 class="fw-bold">Your application</h6>
                        <p class="mb-2"><?= status_badge($myApp['status']) ?> <span class="small text-body-secondary">sent <?= e(time_ago($myApp['created_at'])) ?></span></p>
                        <a href="<?= e(url('messages.php', ['with' => $job['employer_id']])) ?>" class="btn btn-outline-brand w-100 mb-2"><i class="bi bi-chat-dots"></i> Message employer</a>
                        <?php if (in_array($myApp['status'], ['pending', 'shortlisted'], true)): ?>
                            <form method="post" class="d-grid"><?= csrf_field() ?>
                                <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>"><input type="hidden" name="action" value="withdraw">
                                <button class="btn btn-link text-danger btn-sm" data-confirm="Withdraw your application?">Withdraw application</button>
                            </form>
                        <?php endif; ?>
                    <?php elseif ($me['role'] !== 'worker'): ?>
                        <p class="small text-body-secondary mb-0">Only worker accounts can apply for jobs.</p>
                    <?php elseif ($job['status'] !== 'open' || $deadlinePassed): ?>
                        <p class="small text-body-secondary mb-0">This job is no longer accepting applications.</p>
                    <?php else: ?>
                        <h6 class="fw-bold">Apply for this job</h6>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>"><input type="hidden" name="action" value="apply">
                            <div class="mb-2">
                                <label class="form-label small" for="cover_letter">Why are you a good fit?</label>
                                <textarea class="form-control" id="cover_letter" name="cover_letter" rows="5" minlength="20" required placeholder="Your experience, when you can start…"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small" for="expected_pay">Your price (<?= e($job['currency']) ?>) <span class="text-body-secondary fw-normal">(optional)</span></label>
                                <input type="number" min="0" class="form-control" id="expected_pay" name="expected_pay">
                            </div>
                            <button class="btn btn-brand w-100">Send application</button>
                        </form>
                    <?php endif; ?>

                    <?php if (!$isOwner): ?>
                        <hr>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <?= avatar(['name' => $job['employer_name'], 'avatar' => $job['employer_avatar']], 36) ?>
                            <div class="small">
                                <a href="<?= e(url('user.php', ['id' => $job['employer_id']])) ?>" class="fw-semibold text-body text-decoration-none"><?= e($job['employer_name']) ?></a>
                                <div class="text-body-secondary">Posted by</div>
                            </div>
                        </div>
                        <?php if ($me): ?>
                            <button class="btn btn-link btn-sm text-danger p-0" data-bs-toggle="collapse" data-bs-target="#reportForm"><i class="bi bi-flag"></i> Report this listing</button>
                            <form method="post" id="reportForm" class="collapse mt-2"><?= csrf_field() ?>
                                <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>"><input type="hidden" name="action" value="report">
                                <textarea class="form-control form-control-sm mb-2" name="reason" rows="3" minlength="10" required placeholder="e.g. They asked me to pay a fee"></textarea>
                                <button class="btn btn-sm btn-outline-danger">Send report</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($isUae): ?>
                <div class="warning-box p-3 mt-3 small">
                    <strong><i class="bi bi-shield-exclamation text-accent"></i> Never pay to apply.</strong>
                    Do not send money for visas, medicals or “placement” before you receive a written contract. Report anyone who asks.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
