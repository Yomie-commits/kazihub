<?php
require __DIR__ . '/includes/init.php';
$me = require_login(['worker']);

$apps = db_all('SELECT a.*, j.title, j.type, j.county, j.town, j.status AS job_status, j.employer_id, j.currency, u.name AS employer_name
    FROM applications a JOIN jobs j ON j.id = a.job_id JOIN users u ON u.id = j.employer_id
    WHERE a.worker_id = ? ORDER BY a.created_at DESC', [$me['id']]);

$pageTitle = 'My applications';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">My applications</h1>
        <a href="<?= e(url('juakali.php')) ?>" class="btn btn-outline-brand">Find more jobs</a>
    </div>
    <div class="card">
        <?php if (!$apps): ?>
            <?= empty_state('bi-send', "You haven't applied for any jobs yet", 'Browse juakali and UAE jobs and apply in one click.', '<a class="btn btn-brand" href="' . e(url('juakali.php')) . '">Browse jobs</a>') ?>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Job</th><th>Employer</th><th>Status</th><th>Applied</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($apps as $a): ?>
                    <tr>
                        <td>
                            <a class="fw-semibold text-decoration-none" href="<?= e(url('job.php', ['id' => $a['job_id']])) ?>"><?= e($a['title']) ?></a>
                            <div class="small text-body-secondary"><?= $a['type'] === 'uae' ? 'UAE' : 'Juakali' ?> · <?= e(trim(($a['town'] ? $a['town'] . ', ' : '') . $a['county'], ', ')) ?><?= $a['job_status'] === 'closed' ? ' · job closed' : '' ?></div>
                        </td>
                        <td><?= e($a['employer_name']) ?></td>
                        <td><?= status_badge($a['status']) ?></td>
                        <td class="small text-body-secondary text-nowrap"><?= e(time_ago($a['created_at'])) ?></td>
                        <td class="text-end"><a class="btn btn-sm btn-light" href="<?= e(url('messages.php', ['with' => $a['employer_id']])) ?>" title="Message employer"><i class="bi bi-chat-dots"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
