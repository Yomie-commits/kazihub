<?php
require __DIR__ . '/../includes/init.php';
$admin = require_login(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job = db_one('SELECT * FROM jobs WHERE id = ?', [(int) post('id')]);
    $action = post('action');
    if ($job) {
        $map = ['approve' => 'open', 'reject' => 'rejected', 'close' => 'closed'];
        if (isset($map[$action])) {
            db_exec('UPDATE jobs SET status = ? WHERE id = ?', [$map[$action], $job['id']]);
            $notes = [
                'approve' => 'Your job "%s" has been approved and is now live on KaziHub.',
                'reject' => 'Your job "%s" was not approved. Please make sure the company details are correct and that workers are not asked to pay any fees, then edit and resubmit.',
                'close' => 'Your job "%s" was closed by the KaziHub team.',
            ];
            db_insert('messages', ['sender_id' => $admin['id'], 'receiver_id' => $job['employer_id'], 'body' => sprintf($notes[$action], $job['title']), 'created_at' => now()]);
            flash('success', 'Job ' . ($action === 'approve' ? 'approved' : $map[$action]) . '.');
        } elseif ($action === 'delete') {
            db_exec('DELETE FROM jobs WHERE id = ?', [$job['id']]);
            flash('success', 'Job deleted.');
        }
    }
    redirect_back('admin/jobs.php');
}

$status = in_array(get('status'), ['pending', 'open', 'closed', 'rejected'], true) ? get('status') : '';
$type = in_array(get('type'), ['juakali', 'uae'], true) ? get('type') : '';
$q = get('q');
$where = ['1 = 1'];
$params = [];
if ($status) { $where[] = 'j.status = ?'; $params[] = $status; }
if ($type) { $where[] = 'j.type = ?'; $params[] = $type; }
if ($q !== '') { $where[] = '(j.title LIKE ? OR j.company LIKE ? OR u.name LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
$whereSql = ' WHERE ' . implode(' AND ', $where);
$perPage = 25;
$total = (int) db_value('SELECT COUNT(*) FROM jobs j JOIN users u ON u.id = j.employer_id' . $whereSql, $params);
$offset = (current_page() - 1) * $perPage;
$jobs = db_all(JOB_SELECT . $whereSql . " ORDER BY CASE WHEN j.status = 'pending' THEN 0 ELSE 1 END, j.created_at DESC LIMIT $perPage OFFSET $offset", $params);

$pageTitle = 'Admin · Jobs';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
    <?php require __DIR__ . '/_nav.php'; ?>
    <form class="row g-2 mb-3" method="get">
        <div class="col-md-5"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search title, company or employer"></div>
        <div class="col-6 col-md-3"><select class="form-select" name="status"><option value="">All statuses</option><?= options(['pending' => 'Pending', 'open' => 'Open', 'closed' => 'Closed', 'rejected' => 'Rejected'], $status, true) ?></select></div>
        <div class="col-6 col-md-2"><select class="form-select" name="type"><option value="">All types</option><?= options(['juakali' => 'Juakali', 'uae' => 'UAE'], $type, true) ?></select></div>
        <div class="col-md-2"><button class="btn btn-brand w-100">Filter</button></div>
    </form>
    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Job</th><th>Employer</th><th>Type</th><th>Status</th><th>Posted</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php if (!$jobs): ?><tr><td colspan="6" class="text-center text-body-secondary py-4">No jobs found.</td></tr><?php endif; ?>
                <?php foreach ($jobs as $j): ?>
                    <tr>
                        <td><a href="<?= e(url('job.php', ['id' => $j['id']])) ?>" class="fw-semibold"><?= e($j['title']) ?></a><div class="small text-body-secondary"><?= e($j['company'] ?: $j['category']) ?> · <?= e($j['county']) ?> · <?= e(pay_range($j)) ?></div></td>
                        <td class="small"><a href="<?= e(url('user.php', ['id' => $j['employer_id']])) ?>"><?= e($j['employer_name']) ?></a><?= $j['employer_verified'] ? ' <i class="bi bi-patch-check-fill text-brand"></i>' : '' ?></td>
                        <td><?= $j['type'] === 'uae' ? '<span class="badge text-bg-primary">UAE</span>' : '<span class="badge text-bg-warning">Juakali</span>' ?></td>
                        <td><?= status_badge($j['status']) ?></td>
                        <td class="small text-nowrap text-body-secondary"><?= e(time_ago($j['created_at'])) ?></td>
                        <td class="text-end text-nowrap">
                            <?php foreach (['approve' => ['bi-check-lg', 'btn-brand', $j['status'] !== 'open'], 'reject' => ['bi-x-lg', 'btn-outline-danger', $j['status'] === 'pending'], 'close' => ['bi-lock', 'btn-light', $j['status'] === 'open'], 'delete' => ['bi-trash', 'btn-light text-danger', true]] as $act => [$icon, $cls, $show]):
                                if (!$show) continue; ?>
                                <form method="post" class="d-inline"><?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $j['id'] ?>"><input type="hidden" name="action" value="<?= $act ?>">
                                    <button class="btn btn-sm <?= $cls ?>" title="<?= ucfirst($act) ?>" <?= $act === 'delete' ? 'data-confirm="Delete this job permanently?"' : '' ?>><i class="bi <?= $icon ?>"></i></button>
                                </form>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?= pagination($total, $perPage) ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
