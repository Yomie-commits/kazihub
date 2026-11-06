<?php
require __DIR__ . '/../includes/init.php';
$admin = require_login(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = db_one('SELECT * FROM users WHERE id = ?', [(int) post('id')]);
    $action = post('action');
    if (!$user || (int) $user['id'] === (int) $admin['id']) {
        flash('warning', 'You cannot change your own account here.');
    } elseif ($action === 'verify') {
        db_exec('UPDATE users SET is_verified = ? WHERE id = ?', [$user['is_verified'] ? 0 : 1, $user['id']]);
        if (!$user['is_verified']) {
            db_insert('messages', ['sender_id' => $admin['id'], 'receiver_id' => $user['id'], 'body' => 'Your KaziHub account is now verified. A verified badge will show on your profile. 🎉', 'created_at' => now()]);
        }
        flash('success', $user['is_verified'] ? 'Verification removed.' : 'User verified.');
    } elseif ($action === 'suspend') {
        $new = $user['status'] === 'suspended' ? 'active' : 'suspended';
        db_exec('UPDATE users SET status = ? WHERE id = ?', [$new, $user['id']]);
        if ($new === 'suspended') {
            db_exec("UPDATE jobs SET status = 'closed' WHERE employer_id = ? AND status IN ('open','pending')", [$user['id']]);
            db_exec('UPDATE services SET is_active = 0 WHERE user_id = ?', [$user['id']]);
        }
        flash('success', $new === 'suspended' ? 'User suspended. Their open jobs and services were hidden.' : 'User reactivated.');
    }
    redirect_back('admin/users.php');
}

$q = get('q');
$role = in_array(get('role'), ['worker', 'employer', 'admin'], true) ? get('role') : '';
$where = ['1 = 1'];
$params = [];
if ($q !== '') { $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
if ($role) { $where[] = 'role = ?'; $params[] = $role; }
if (get('unverified')) { $where[] = 'is_verified = 0'; }
$whereSql = ' WHERE ' . implode(' AND ', $where);
$perPage = 25;
$total = (int) db_value('SELECT COUNT(*) FROM users' . $whereSql, $params);
$offset = (current_page() - 1) * $perPage;
$users = db_all('SELECT * FROM users' . $whereSql . " ORDER BY created_at DESC LIMIT $perPage OFFSET $offset", $params);

$pageTitle = 'Admin · Users';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
    <?php require __DIR__ . '/_nav.php'; ?>
    <form class="row g-2 mb-3" method="get">
        <div class="col-md-5"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search name, email or phone"></div>
        <div class="col-6 col-md-3"><select class="form-select" name="role"><option value="">All roles</option><?= options(['worker' => 'Workers', 'employer' => 'Employers', 'admin' => 'Admins'], $role, true) ?></select></div>
        <div class="col-6 col-md-2 d-flex align-items-center"><div class="form-check"><input class="form-check-input" type="checkbox" name="unverified" value="1" id="unv" <?= get('unverified') ? 'checked' : '' ?>><label class="form-check-label" for="unv">Unverified</label></div></div>
        <div class="col-md-2"><button class="btn btn-brand w-100">Filter</button></div>
    </form>
    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>User</th><th>Role</th><th>Phone</th><th>Location</th><th>Joined</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php if (!$users): ?><tr><td colspan="7" class="text-center text-body-secondary py-4">No users found.</td></tr><?php endif; ?>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="d-flex gap-2 align-items-center">
                                <?= avatar($u, 34) ?>
                                <div><a class="fw-semibold" href="<?= e(url('user.php', ['id' => $u['id']])) ?>"><?= e($u['name']) ?></a><?= $u['is_verified'] ? ' <i class="bi bi-patch-check-fill text-brand"></i>' : '' ?><div class="small text-body-secondary"><?= e($u['email']) ?></div></div>
                            </div>
                        </td>
                        <td><span class="badge text-bg-light"><?= e($u['role']) ?></span></td>
                        <td class="small"><?= e($u['phone']) ?></td>
                        <td class="small"><?= e($u['county']) ?></td>
                        <td class="small text-nowrap text-body-secondary"><?= e(date('j M Y', strtotime($u['created_at']))) ?></td>
                        <td><?= status_badge($u['status']) ?></td>
                        <td class="text-end text-nowrap">
                            <?php if ((int) $u['id'] !== (int) $admin['id']): ?>
                                <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><input type="hidden" name="action" value="verify">
                                    <button class="btn btn-sm <?= $u['is_verified'] ? 'btn-light' : 'btn-outline-brand' ?>"><?= $u['is_verified'] ? 'Unverify' : 'Verify' ?></button></form>
                                <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><input type="hidden" name="action" value="suspend">
                                    <button class="btn btn-sm <?= $u['status'] === 'suspended' ? 'btn-outline-success' : 'btn-outline-danger' ?>" <?= $u['status'] === 'suspended' ? '' : 'data-confirm="Suspend this user? They will not be able to sign in."' ?>><?= $u['status'] === 'suspended' ? 'Reactivate' : 'Suspend' ?></button></form>
                                <a class="btn btn-sm btn-light" href="<?= e(url('messages.php', ['with' => $u['id']])) ?>" title="Message"><i class="bi bi-chat-dots"></i></a>
                            <?php else: ?><span class="small text-body-secondary">You</span><?php endif; ?>
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
