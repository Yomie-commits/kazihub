<?php
require __DIR__ . '/../includes/init.php';
require_login(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    db_exec("UPDATE reports SET status = 'reviewed' WHERE id = ?", [(int) post('id')]);
    flash('success', 'Report marked as reviewed.');
    redirect('admin/reports.php');
}

$reports = db_all('SELECT r.*, u.name AS reporter_name, j.title AS job_title, j.status AS job_status, j.type AS job_type,
        e.name AS employer_name, e.id AS employer_id
    FROM reports r JOIN users u ON u.id = r.reporter_id JOIN jobs j ON j.id = r.job_id JOIN users e ON e.id = j.employer_id
    ORDER BY CASE WHEN r.status = \'new\' THEN 0 ELSE 1 END, r.created_at DESC LIMIT 200');

$pageTitle = 'Admin · Reports';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
    <?php require __DIR__ . '/_nav.php'; ?>
    <div class="card">
        <?php if (!$reports): ?><?= empty_state('bi-flag', 'No reports', 'Reported listings will appear here.') ?><?php endif; ?>
        <ul class="list-group list-group-flush">
            <?php foreach ($reports as $r): ?>
                <li class="list-group-item p-3">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div>
                            <?= status_badge($r['status']) ?>
                            <a class="fw-semibold" href="<?= e(url('job.php', ['id' => $r['job_id']])) ?>"><?= e($r['job_title']) ?></a>
                            <span class="small text-body-secondary">(<?= e(strtoupper($r['job_type'])) ?>, job is <?= e($r['job_status']) ?>) by
                                <a href="<?= e(url('user.php', ['id' => $r['employer_id']])) ?>"><?= e($r['employer_name']) ?></a></span>
                            <p class="mb-1 mt-2"><?= e($r['reason']) ?></p>
                            <div class="small text-body-secondary">Reported by <?= e($r['reporter_name']) ?> · <?= e(time_ago($r['created_at'])) ?></div>
                        </div>
                        <div class="d-flex gap-1 align-items-start">
                            <?php if ($r['job_status'] !== 'closed'): ?>
                                <form method="post" action="<?= e(url('admin/jobs.php')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['job_id'] ?>"><input type="hidden" name="action" value="close">
                                    <button class="btn btn-sm btn-outline-danger">Close job</button></form>
                            <?php endif; ?>
                            <?php if ($r['status'] === 'new'): ?>
                                <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn btn-sm btn-light">Mark reviewed</button></form>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
