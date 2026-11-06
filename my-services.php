<?php
require __DIR__ . '/includes/init.php';
$me = require_login(['worker']);

$services = db_all('SELECT s.*,
        (SELECT COUNT(*) FROM orders o WHERE o.service_id = s.id) AS order_count,
        (SELECT COUNT(*) FROM orders o WHERE o.service_id = s.id AND o.status IN (\'pending\',\'in_progress\',\'delivered\')) AS active_orders
    FROM services s WHERE s.user_id = ? ORDER BY s.created_at DESC', [$me['id']]);

$pageTitle = 'My services';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">My services</h1>
        <a href="<?= e(url('service-edit.php')) ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> New service</a>
    </div>
    <div class="card">
        <?php if (!$services): ?>
            <?= empty_state('bi-grid', "You haven't listed any services yet", 'Create your first service so clients can hire you.', '<a class="btn btn-brand" href="' . e(url('service-edit.php')) . '">Create a service</a>') ?>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Service</th><th>Price</th><th>Orders</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($services as $s): ?>
                    <tr>
                        <td><a href="<?= e(url('service.php', ['id' => $s['id']])) ?>" class="fw-semibold text-decoration-none"><?= e($s['title']) ?></a><div class="small text-body-secondary"><?= e($s['category']) ?></div></td>
                        <td class="text-nowrap"><?= e(money($s['price'])) ?></td>
                        <td><?= (int) $s['order_count'] ?><?php if ($s['active_orders']): ?> <span class="badge text-bg-primary"><?= (int) $s['active_orders'] ?> active</span><?php endif; ?></td>
                        <td><?= $s['is_active'] ? status_badge('active') : '<span class="badge text-bg-secondary">Paused</span>' ?></td>
                        <td class="text-end text-nowrap">
                            <a href="<?= e(url('service-edit.php', ['id' => $s['id']])) ?>" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="<?= e(url('service-edit.php')) ?>" class="d-inline">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="action" value="toggle">
                                <button class="btn btn-sm btn-light" title="<?= $s['is_active'] ? 'Pause' : 'Activate' ?>"><i class="bi <?= $s['is_active'] ? 'bi-pause' : 'bi-play' ?>"></i></button>
                            </form>
                            <form method="post" action="<?= e(url('service-edit.php')) ?>" class="d-inline">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="action" value="delete">
                                <button class="btn btn-sm btn-light text-danger" title="Delete" data-confirm="Delete this service? This cannot be undone."><i class="bi bi-trash"></i></button>
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
