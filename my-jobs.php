<?php
require __DIR__ . '/includes/init.php';
$me = require_login(['employer', 'admin']);

$jobs = db_all('SELECT j.*,
        (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id AND a.status <> \'withdrawn\') AS applicant_count,
        (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id AND a.status = \'pending\') AS new_count
    FROM jobs j WHERE j.employer_id = ? ORDER BY j.created_at DESC', [$me['id']]);

$pageTitle = 'My job posts';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">My job posts</h1>
        <a href="<?= e(url('job-post.php')) ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Post a job</a>
    </div>
    <div class="card">
        <?php if (!$jobs): ?>
            <?= empty_state('bi-briefcase', "You haven't posted any jobs yet", 'Post a job and start receiving applications from workers.', '<a class="btn btn-brand" href="' . e(url('job-post.php')) . '">Post your first job</a>') ?>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Job</th><th>Type</th><th>Applicants</th><th>Status</th><th>Posted</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($jobs as $j): ?>
                    <tr>
                        <td><a class="fw-semibold text-decoration-none" href="<?= e(url('job.php', ['id' => $j['id']])) ?>"><?= e($j['title']) ?></a><div class="small text-body-secondary"><?= e($j['category']) ?> · <?= e($j['county']) ?></div></td>
                        <td><?= $j['type'] === 'uae' ? '<span class="badge text-bg-primary">UAE</span>' : '<span class="badge text-bg-warning">Juakali</span>' ?></td>
                        <td>
                            <a href="<?= e(url('job.php', ['id' => $j['id'], 'tab' => 'applicants'])) ?>" class="text-decoration-none"><?= (int) $j['applicant_count'] ?></a>
                            <?php if ($j['new_count']): ?><span class="badge text-bg-danger"><?= (int) $j['new_count'] ?> new</span><?php endif; ?>
                        </td>
                        <td><?= status_badge($j['status']) ?></td>
                        <td class="small text-body-secondary text-nowrap"><?= e(time_ago($j['created_at'])) ?></td>
                        <td class="text-end text-nowrap">
                            <a href="<?= e(url('job-post.php', ['id' => $j['id']])) ?>" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>
                            <?php if ($j['status'] === 'closed'): ?>
                                <form method="post" action="<?= e(url('job-post.php')) ?>" class="d-inline"><?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $j['id'] ?>"><input type="hidden" name="action" value="reopen">
                                    <button class="btn btn-sm btn-light" title="Reopen"><i class="bi bi-arrow-counterclockwise"></i></button>
                                </form>
                            <?php elseif ($j['status'] !== 'rejected'): ?>
                                <form method="post" action="<?= e(url('job-post.php')) ?>" class="d-inline"><?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $j['id'] ?>"><input type="hidden" name="action" value="close">
                                    <button class="btn btn-sm btn-light" title="Close" data-confirm="Close this job?"><i class="bi bi-lock"></i></button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?= e(url('job-post.php')) ?>" class="d-inline"><?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $j['id'] ?>"><input type="hidden" name="action" value="delete">
                                <button class="btn btn-sm btn-light text-danger" title="Delete" data-confirm="Delete this job and all its applications?"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
