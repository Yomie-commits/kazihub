<?php
require __DIR__ . '/includes/bootstrap.php';
$admin = require_login();
if ($admin['role'] !== 'admin') {
    http_response_code(403);
    flash('danger', 'Admins only.');
    redirect('dashboard.php');
}

$tab = get('tab', 'overview');
if (!in_array($tab, ['overview', 'uae', 'users', 'jobs', 'services'], true)) {
    $tab = 'overview';
}

if (is_post()) {
    verify_csrf();
    $action = post('action');
    $id = (int)post('id');

    switch ($action) {
        case 'approve_job':
            $job = db_one('SELECT * FROM jobs WHERE id = ?', [$id]);
            if ($job) {
                db_exec('UPDATE jobs SET is_verified = 1 WHERE id = ?', [$id]);
                db_exec('INSERT INTO messages (sender_id, receiver_id, body, created_at) VALUES (?, ?, ?, ?)',
                    [$admin['id'], $job['client_id'], "✅ Your job \"{$job['title']}\" has been verified and is now live on KaziHub.", now()]);
                flash('success', 'Job approved and published.');
            }
            break;
        case 'reject_job':
            $job = db_one('SELECT * FROM jobs WHERE id = ?', [$id]);
            if ($job) {
                $reason = post('reason') ?: 'we could not verify the employer details';
                db_exec("UPDATE jobs SET is_verified = 0, status = 'closed' WHERE id = ?", [$id]);
                db_exec('INSERT INTO messages (sender_id, receiver_id, body, created_at) VALUES (?, ?, ?, ?)',
                    [$admin['id'], $job['client_id'], "❌ Your job \"{$job['title']}\" was not approved because $reason. Reply here if you have questions.", now()]);
                flash('info', 'Job rejected and the poster notified.');
            }
            break;
        case 'toggle_verified':
            db_exec('UPDATE users SET is_verified = 1 - is_verified WHERE id = ? AND role <> ?', [$id, 'admin']);
            flash('success', 'User verification updated.');
            break;
        case 'toggle_active':
            if ($id !== (int)$admin['id']) {
                db_exec('UPDATE users SET is_active = 1 - is_active WHERE id = ? AND role <> ?', [$id, 'admin']);
                flash('success', 'User status updated.');
            }
            break;
        case 'delete_job':
            db_exec('DELETE FROM jobs WHERE id = ?', [$id]);
            flash('success', 'Job deleted.');
            break;
        case 'delete_service':
            $svc = db_one('SELECT image FROM services WHERE id = ?', [$id]);
            if ($svc) {
                delete_upload($svc['image']);
                db_exec('DELETE FROM services WHERE id = ?', [$id]);
                flash('success', 'Service deleted.');
            }
            break;
    }
    $back = $_GET;
    redirect('admin.php?' . http_build_query($back));
}

$q = get('q');
$counts = [
    'users'       => (int)db_value("SELECT COUNT(*) FROM users WHERE role <> 'admin'"),
    'freelancers' => (int)db_value("SELECT COUNT(*) FROM users WHERE role = 'freelancer'"),
    'seekers'     => (int)db_value("SELECT COUNT(*) FROM users WHERE role = 'job_seeker'"),
    'clients'     => (int)db_value("SELECT COUNT(*) FROM users WHERE role = 'client'"),
    'jobs'        => (int)db_value("SELECT COUNT(*) FROM jobs WHERE status = 'open'"),
    'pending_uae' => (int)db_value("SELECT COUNT(*) FROM jobs WHERE type = 'uae' AND is_verified = 0 AND status = 'open'"),
    'applications'=> (int)db_value('SELECT COUNT(*) FROM applications'),
    'services'    => (int)db_value('SELECT COUNT(*) FROM services'),
    'messages'    => (int)db_value('SELECT COUNT(*) FROM messages'),
];
$newUsers = (int)db_value('SELECT COUNT(*) FROM users WHERE created_at >= ?', [date('Y-m-d H:i:s', strtotime('-7 days'))]);

$like = '%' . $q . '%';
$pendingUae = db_all(jobs_select_sql() . " WHERE j.type = 'uae' AND j.is_verified = 0 AND j.status = 'open' ORDER BY j.created_at ASC");
$users = $tab === 'users' ? db_all("SELECT * FROM users WHERE role <> 'admin' AND (name LIKE ? OR email LIKE ? OR company_name LIKE ?) ORDER BY created_at DESC LIMIT 200", [$like, $like, $like]) : [];
$jobs = $tab === 'jobs' ? db_all(jobs_select_sql() . ' WHERE (j.title LIKE ? OR u.name LIKE ?) ORDER BY j.created_at DESC LIMIT 200', [$like, $like]) : [];
$services = $tab === 'services' ? db_all('SELECT s.*, u.name FROM services s JOIN users u ON u.id = s.user_id WHERE s.title LIKE ? OR u.name LIKE ? ORDER BY s.created_at DESC LIMIT 200', [$like, $like]) : [];
$recentUsers = $tab === 'overview' ? db_all("SELECT * FROM users WHERE role <> 'admin' ORDER BY created_at DESC LIMIT 6") : [];

$btn = function (string $action, int $id, string $label, string $cls, string $confirm = '') {
    return '<form method="post" class="d-inline">' . csrf_field() . '<input type="hidden" name="action" value="' . $action . '"><input type="hidden" name="id" value="' . $id . '">'
        . '<button class="btn btn-sm ' . $cls . '"' . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . '>' . $label . '</button></form>';
};

$pageTitle = 'Admin';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 fw-bold mb-0"><i class="bi bi-shield-lock text-primary me-2"></i>Admin panel</h1>
    </div>
    <ul class="nav nav-pills mb-4 gap-1">
        <?php foreach (['overview' => 'Overview', 'uae' => 'UAE verification', 'users' => 'Users', 'jobs' => 'Jobs', 'services' => 'Services'] as $k => $l): ?>
            <li class="nav-item"><a class="nav-link <?= $tab === $k ? 'active' : '' ?>" href="admin.php?tab=<?= $k ?>"><?= $l ?>
                <?php if ($k === 'uae' && $counts['pending_uae']): ?><span class="badge bg-danger ms-1"><?= $counts['pending_uae'] ?></span><?php endif; ?></a></li>
        <?php endforeach; ?>
    </ul>

    <?php if (in_array($tab, ['users', 'jobs', 'services'], true)): ?>
        <form class="mb-3" style="max-width:420px"><input type="hidden" name="tab" value="<?= e($tab) ?>">
            <div class="input-group"><input name="q" value="<?= e($q) ?>" class="form-control" placeholder="Search…"><button class="btn btn-primary"><i class="bi bi-search"></i></button></div>
        </form>
    <?php endif; ?>

    <?php if ($tab === 'overview'): ?>
        <div class="row g-3 mb-4">
            <?php foreach ([
                ['Users', $counts['users'], "+$newUsers this week", 'bi-people'],
                ['Open jobs', $counts['jobs'], $counts['pending_uae'] . ' UAE awaiting review', 'bi-briefcase'],
                ['Applications', $counts['applications'], '', 'bi-send'],
                ['Services', $counts['services'], $counts['messages'] . ' messages sent', 'bi-stars'],
            ] as [$l, $n, $sub, $icon]): ?>
                <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body">
                    <div class="d-flex justify-content-between"><span class="text-muted small"><?= $l ?></span><i class="bi <?= $icon ?> text-primary"></i></div>
                    <div class="fs-3 fw-bold"><?= $n ?></div><div class="small text-muted"><?= e($sub) ?></div>
                </div></div></div>
            <?php endforeach; ?>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card h-100"><div class="card-header bg-white fw-bold">Users by type</div><div class="card-body">
                    <?php foreach (['Freelancers' => $counts['freelancers'], 'Job seekers' => $counts['seekers'], 'Employers' => $counts['clients']] as $l => $n):
                        $pct = $counts['users'] ? round($n / $counts['users'] * 100) : 0; ?>
                        <div class="d-flex justify-content-between small"><span><?= $l ?></span><span><?= $n ?> (<?= $pct ?>%)</span></div>
                        <div class="progress mb-3" style="height:8px"><div class="progress-bar bg-primary" style="width:<?= $pct ?>%"></div></div>
                    <?php endforeach; ?>
                </div></div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100"><div class="card-header bg-white fw-bold">Newest members</div>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentUsers as $u): ?>
                            <li class="list-group-item d-flex align-items-center gap-2"><?= avatar($u, 32) ?>
                                <a href="profile.php?id=<?= (int)$u['id'] ?>" class="text-reset small fw-semibold"><?= e($u['name']) ?></a>
                                <span class="small text-muted ms-auto"><?= e(role_label($u['role'])) ?> · <?= e(time_ago($u['created_at'])) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

    <?php elseif ($tab === 'uae'): ?>
        <p class="text-muted small">Check each employer and recruiter (e.g. on the NEA licensed agencies list) before approving. Approved jobs show a <i class="bi bi-patch-check-fill verified"></i> verified badge.</p>
        <?php foreach ($pendingUae as $j): ?>
            <div class="card mb-3"><div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                    <div>
                        <h5 class="fw-bold mb-1"><a href="job.php?id=<?= (int)$j['id'] ?>" class="text-reset"><?= e($j['title']) ?></a></h5>
                        <div class="small text-muted mb-2">Employer: <strong><?= e($j['employer_name']) ?></strong> · Posted by <a href="profile.php?id=<?= (int)$j['client_id'] ?>"><?= e($j['client_name']) ?></a> · <?= e(time_ago($j['created_at'])) ?></div>
                        <div class="small mb-2"><?= e(budget_label($j)) ?> · <?= e($j['location']) ?> · Visa: <?= $j['visa_support'] ? 'yes' : 'no' ?> · Housing: <?= $j['accommodation'] ? 'yes' : 'no' ?></div>
                        <p class="small mb-0" style="white-space:pre-line"><?= e(mb_substr($j['description'], 0, 600)) ?></p>
                    </div>
                    <div style="min-width:240px">
                        <?= $btn('approve_job', (int)$j['id'], '<i class="bi bi-check-lg me-1"></i>Approve & publish', 'btn-success w-100 mb-2') ?>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="reject_job"><input type="hidden" name="id" value="<?= (int)$j['id'] ?>">
                            <input name="reason" class="form-control form-control-sm mb-1" placeholder="Reason (sent to poster)">
                            <button class="btn btn-sm btn-outline-danger w-100">Reject</button></form>
                    </div>
                </div>
            </div></div>
        <?php endforeach; ?>
        <?php if (!$pendingUae): ?><div class="card card-body text-center text-muted py-5"><i class="bi bi-check2-all fs-1"></i>All UAE jobs are reviewed.</div><?php endif; ?>

    <?php elseif ($tab === 'users'): ?>
        <div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
            <thead class="table-light small"><tr><th>User</th><th>Type</th><th>Joined</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><div class="d-flex align-items-center gap-2"><?= avatar($u, 32) ?><div><a href="profile.php?id=<?= (int)$u['id'] ?>" class="fw-semibold text-reset"><?= e($u['name']) ?></a>
                        <?php if ($u['is_verified']): ?><i class="bi bi-patch-check-fill verified"></i><?php endif; ?><div class="small text-muted"><?= e($u['email']) ?></div></div></div></td>
                    <td class="small"><?= e(role_label($u['role'])) ?></td>
                    <td class="small text-muted"><?= e(date('j M Y', strtotime($u['created_at']))) ?></td>
                    <td><?= $u['is_active'] ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-danger">Suspended</span>' ?></td>
                    <td class="text-end text-nowrap">
                        <?= $btn('toggle_verified', (int)$u['id'], $u['is_verified'] ? 'Unverify' : 'Verify', 'btn-outline-info') ?>
                        <?= $btn('toggle_active', (int)$u['id'], $u['is_active'] ? 'Suspend' : 'Restore', $u['is_active'] ? 'btn-outline-danger' : 'btn-outline-success', $u['is_active'] ? 'Suspend this user? They will be signed out and hidden.' : '') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$users): ?><tr><td colspan="5" class="text-center text-muted py-4">No users found.</td></tr><?php endif; ?>
            </tbody>
        </table></div></div>

    <?php elseif ($tab === 'jobs'): ?>
        <div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
            <thead class="table-light small"><tr><th>Job</th><th>Type</th><th>Posted by</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($jobs as $j): ?>
                <tr>
                    <td><a href="job.php?id=<?= (int)$j['id'] ?>" class="fw-semibold text-reset"><?= e($j['title']) ?></a><div class="small text-muted"><?= e(time_ago($j['created_at'])) ?></div></td>
                    <td><?= job_type_badge($j['type']) ?></td>
                    <td class="small"><?= e($j['client_name']) ?></td>
                    <td><?= status_badge($j['status']) ?> <?= $j['is_verified'] ? '' : '<span class="badge bg-warning-subtle text-warning-emphasis">Unverified</span>' ?></td>
                    <td class="text-end text-nowrap">
                        <a href="post-job.php?id=<?= (int)$j['id'] ?>" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
                        <?= $btn('delete_job', (int)$j['id'], '<i class="bi bi-trash"></i>', 'btn-outline-danger', 'Delete this job permanently?') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$jobs): ?><tr><td colspan="5" class="text-center text-muted py-4">No jobs found.</td></tr><?php endif; ?>
            </tbody>
        </table></div></div>

    <?php elseif ($tab === 'services'): ?>
        <div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
            <thead class="table-light small"><tr><th>Service</th><th>Seller</th><th>Price</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody>
            <?php foreach ($services as $s): ?>
                <tr>
                    <td><a href="service.php?id=<?= (int)$s['id'] ?>" class="fw-semibold text-reset"><?= e($s['title']) ?></a></td>
                    <td class="small"><?= e($s['name']) ?></td>
                    <td class="small"><?= e(money($s['price'])) ?></td>
                    <td><?= status_badge($s['status']) ?></td>
                    <td class="text-end"><?= $btn('delete_service', (int)$s['id'], '<i class="bi bi-trash"></i>', 'btn-outline-danger', 'Delete this service?') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$services): ?><tr><td colspan="5" class="text-center text-muted py-4">No services found.</td></tr><?php endif; ?>
            </tbody>
        </table></div></div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
