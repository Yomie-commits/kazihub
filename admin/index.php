<?php
require __DIR__ . '/../includes/init.php';
require_login(['admin']);

$counts = [
    ['Workers', db_value("SELECT COUNT(*) FROM users WHERE role = 'worker'"), 'bi-person-workspace', 'success'],
    ['Employers', db_value("SELECT COUNT(*) FROM users WHERE role = 'employer'"), 'bi-building', 'primary'],
    ['Open jobs', db_value("SELECT COUNT(*) FROM jobs WHERE status = 'open'"), 'bi-briefcase', 'warning'],
    ['Active services', db_value('SELECT COUNT(*) FROM services WHERE is_active = 1'), 'bi-grid', 'info'],
    ['Orders', db_value('SELECT COUNT(*) FROM orders'), 'bi-bag', 'secondary'],
    ['Applications', db_value('SELECT COUNT(*) FROM applications'), 'bi-send', 'danger'],
];
$pending = db_all(JOB_SELECT . " WHERE j.status = 'pending' ORDER BY j.created_at ASC LIMIT 10");
$newUsers = db_all('SELECT * FROM users ORDER BY created_at DESC LIMIT 8');

$pageTitle = 'Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
    <?php require __DIR__ . '/_nav.php'; ?>
    <div class="row g-3 mb-4">
        <?php foreach ($counts as [$label, $value, $icon, $color]): ?>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card stat-card h-100"><div class="card-body">
                    <div class="stat-icon bg-<?= $color ?>-subtle text-<?= $color ?>-emphasis mb-2"><i class="bi <?= $icon ?>"></i></div>
                    <div class="stat-value"><?= number_format((int) $value) ?></div><div class="small text-body-secondary"><?= $label ?></div>
                </div></div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between"><strong>Jobs waiting for approval</strong><a class="small" href="<?= e(url('admin/jobs.php', ['status' => 'pending'])) ?>">All</a></div>
                <?php if (!$pending): ?><div class="card-body small text-body-secondary">Nothing to review. 🎉</div><?php endif; ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($pending as $j): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between gap-2">
                                <div class="min-w-0">
                                    <a href="<?= e(url('job.php', ['id' => $j['id']])) ?>" class="fw-semibold"><?= e($j['title']) ?></a>
                                    <div class="small text-body-secondary"><?= e($j['company'] ?: $j['employer_name']) ?> · <?= e($j['county']) ?> · <?= e(pay_range($j)) ?> · by <?= e($j['employer_name']) ?><?= $j['employer_verified'] ? ' ✔' : '' ?></div>
                                </div>
                                <div class="d-flex gap-1 align-items-start">
                                    <form method="post" action="<?= e(url('admin/jobs.php')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $j['id'] ?>"><input type="hidden" name="action" value="approve"><button class="btn btn-sm btn-brand">Approve</button></form>
                                    <form method="post" action="<?= e(url('admin/jobs.php')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $j['id'] ?>"><input type="hidden" name="action" value="reject"><button class="btn btn-sm btn-outline-danger">Reject</button></form>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between"><strong>Newest users</strong><a class="small" href="<?= e(url('admin/users.php')) ?>">All</a></div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($newUsers as $u): ?>
                        <li class="list-group-item d-flex gap-2 align-items-center">
                            <?= avatar($u, 34) ?>
                            <div class="flex-grow-1 min-w-0">
                                <a class="small fw-semibold" href="<?= e(url('user.php', ['id' => $u['id']])) ?>"><?= e($u['name']) ?></a>
                                <div class="small text-body-secondary text-truncate"><?= e($u['email']) ?></div>
                            </div>
                            <span class="badge text-bg-light"><?= e($u['role']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
