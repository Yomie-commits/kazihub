<?php
require __DIR__ . '/includes/bootstrap.php';
$user = require_role(['client']);

$jobId = get_int('job');
$job = db_one('SELECT * FROM jobs WHERE id = ?', [$jobId]);
if (!$job || ((int)$job['client_id'] !== (int)$user['id'] && $user['role'] !== 'admin')) {
    flash('danger', 'Job not found.');
    redirect('dashboard.php');
}

if (is_post()) {
    verify_csrf();
    $appId = (int)post('application_id');
    $status = post('status');
    $app = db_one('SELECT * FROM applications WHERE id = ? AND job_id = ?', [$appId, $jobId]);
    if ($app && in_array($status, ['pending', 'shortlisted', 'accepted', 'rejected'], true) && $status !== $app['status']) {
        db_exec('UPDATE applications SET status = ? WHERE id = ?', [$status, $appId]);
        $notes = [
            'shortlisted' => "Good news! You've been shortlisted for \"{$job['title']}\". We'll be in touch soon.",
            'accepted'    => "🎉 Congratulations! Your application for \"{$job['title']}\" has been accepted. Let's agree on the next steps here.",
            'rejected'    => "Thank you for applying for \"{$job['title']}\". We've decided to go with another candidate this time. All the best!",
        ];
        if (isset($notes[$status])) {
            db_exec('INSERT INTO messages (sender_id, receiver_id, body, created_at) VALUES (?, ?, ?, ?)', [$job['client_id'], $app['user_id'], $notes[$status], now()]);
        }
        if ($status === 'accepted' && !empty($_POST['mark_filled'])) {
            db_exec("UPDATE jobs SET status = 'filled' WHERE id = ?", [$jobId]);
        }
        flash('success', 'Applicant marked as ' . $status . ' and notified.');
    }
    redirect('applicants.php?job=' . $jobId . (get('filter') ? '&filter=' . urlencode(get('filter')) : ''));
}

$filter = get('filter');
$params = [$jobId];
$sql = 'SELECT a.*, u.name, u.avatar, u.headline, u.skills, u.location, u.experience, u.is_verified, u.phone,
               (SELECT AVG(rating) FROM reviews r WHERE r.reviewee_id = u.id) AS rating_avg,
               (SELECT COUNT(*) FROM reviews r WHERE r.reviewee_id = u.id) AS rating_total
        FROM applications a JOIN users u ON u.id = a.user_id WHERE a.job_id = ?';
if (in_array($filter, ['pending', 'shortlisted', 'accepted', 'rejected'], true)) {
    $sql .= ' AND a.status = ?';
    $params[] = $filter;
}
$apps = db_all($sql . ' ORDER BY a.created_at DESC', $params);
$counts = [];
foreach (db_all('SELECT status, COUNT(*) AS n FROM applications WHERE job_id = ? GROUP BY status', [$jobId]) as $r) {
    $counts[$r['status']] = (int)$r['n'];
}

$pageTitle = 'Applicants';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <a href="job.php?id=<?= $jobId ?>" class="small"><i class="bi bi-arrow-left"></i> Back to job</a>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-0">Applicants for “<?= e($job['title']) ?>”</h1>
            <div class="small text-muted"><?= job_type_badge($job['type']) ?> <?= status_badge($job['status']) ?></div>
        </div>
    </div>

    <ul class="nav nav-pills mb-3 gap-1">
        <li class="nav-item"><a class="nav-link <?= $filter === '' ? 'active' : '' ?>" href="?job=<?= $jobId ?>">All (<?= array_sum($counts) ?>)</a></li>
        <?php foreach (['pending', 'shortlisted', 'accepted', 'rejected'] as $s): ?>
            <li class="nav-item"><a class="nav-link <?= $filter === $s ? 'active' : '' ?>" href="?job=<?= $jobId ?>&filter=<?= $s ?>"><?= ucfirst($s) ?> (<?= $counts[$s] ?? 0 ?>)</a></li>
        <?php endforeach; ?>
    </ul>

    <?php if (!$apps): ?>
        <div class="card card-body text-center py-5 text-muted">No applicants here yet.</div>
    <?php endif; ?>

    <div class="row g-3">
        <?php foreach ($apps as $a): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row gap-3">
                            <div class="d-flex gap-3 flex-grow-1 min-w-0">
                                <?= avatar($a, 56) ?>
                                <div class="min-w-0">
                                    <a href="profile.php?id=<?= (int)$a['user_id'] ?>" class="fw-bold text-reset"><?= e($a['name']) ?></a>
                                    <?php if ($a['is_verified']): ?><i class="bi bi-patch-check-fill verified"></i><?php endif; ?>
                                    <?= status_badge($a['status']) ?>
                                    <div class="small text-muted"><?= e($a['headline']) ?></div>
                                    <div class="small text-muted">
                                        <?php if ($a['location']): ?><i class="bi bi-geo-alt"></i> <?= e($a['location']) ?> · <?php endif; ?>
                                        <?php if ($a['experience']): ?><?= e($a['experience']) ?> · <?php endif; ?>
                                        <?= $a['rating_total'] ? '<i class="bi bi-star-fill text-warning"></i> ' . number_format((float)$a['rating_avg'], 1) . ' (' . (int)$a['rating_total'] . ')' : 'No reviews yet' ?>
                                        · Applied <?= e(time_ago($a['created_at'])) ?>
                                    </div>
                                    <p class="mt-2 mb-1" style="white-space:pre-line"><?= e($a['cover_letter']) ?></p>
                                    <?php if ($a['proposed_rate']): ?><div class="small"><strong>Proposed price:</strong> <?= e(money($a['proposed_rate'], $job['currency'])) ?></div><?php endif; ?>
                                    <?php if ($a['status'] === 'accepted' && $a['phone']): ?><div class="small"><strong>Phone:</strong> <a href="tel:<?= e(preg_replace('/\s+/', '', $a['phone'])) ?>"><?= e($a['phone']) ?></a></div><?php endif; ?>
                                </div>
                            </div>
                            <div class="d-flex flex-md-column gap-2 flex-wrap" style="min-width:170px">
                                <a href="messages.php?with=<?= (int)$a['user_id'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-chat-dots me-1"></i>Message</a>
                                <?php foreach (['shortlisted' => ['Shortlist', 'btn-outline-info'], 'accepted' => ['Hire', 'btn-success'], 'rejected' => ['Decline', 'btn-outline-danger']] as $s => [$label, $cls]): ?>
                                    <?php if ($a['status'] !== $s): ?>
                                        <form method="post"><?= csrf_field() ?>
                                            <input type="hidden" name="application_id" value="<?= (int)$a['id'] ?>">
                                            <input type="hidden" name="status" value="<?= $s ?>">
                                            <?php if ($s === 'accepted'): ?><input type="hidden" name="mark_filled" value="<?= $job['type'] === 'juakali' ? 1 : 0 ?>"><?php endif; ?>
                                            <button class="btn btn-sm <?= $cls ?> w-100" <?= $s === 'rejected' ? 'data-confirm="Decline this applicant? They will be notified."' : '' ?>><?= $label ?></button>
                                        </form>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
