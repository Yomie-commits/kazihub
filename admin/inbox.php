<?php
require __DIR__ . '/../includes/init.php';
require_login(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) post('id');
    if (post('action') === 'delete') {
        db_exec('DELETE FROM contact_messages WHERE id = ?', [$id]);
        flash('success', 'Message deleted.');
    } else {
        db_exec("UPDATE contact_messages SET status = 'reviewed' WHERE id = ?", [$id]);
        flash('success', 'Marked as handled.');
    }
    redirect('admin/inbox.php');
}

$messages = db_all("SELECT c.*, u.id AS user_id FROM contact_messages c LEFT JOIN users u ON u.email = c.email
    ORDER BY CASE WHEN c.status = 'new' THEN 0 ELSE 1 END, c.created_at DESC LIMIT 200");

$pageTitle = 'Admin · Contact inbox';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
    <?php require __DIR__ . '/_nav.php'; ?>
    <div class="card">
        <?php if (!$messages): ?><?= empty_state('bi-inbox', 'Inbox is empty') ?><?php endif; ?>
        <ul class="list-group list-group-flush">
            <?php foreach ($messages as $m): ?>
                <li class="list-group-item p-3">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div class="min-w-0">
                            <?= $m['status'] === 'new' ? status_badge('new') : '' ?>
                            <strong><?= e($m['subject']) ?></strong>
                            <div class="small text-body-secondary"><?= e($m['name']) ?> · <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a> · <?= e(time_ago($m['created_at'])) ?>
                                <?php if ($m['user_id']): ?> · <a href="<?= e(url('admin/users.php', ['q' => $m['email']])) ?>">KaziHub account</a><?php endif; ?></div>
                            <p class="mb-0 mt-2" style="white-space:pre-line"><?= e($m['message']) ?></p>
                        </div>
                        <div class="d-flex gap-1 align-items-start">
                            <?php if ($m['user_id']): ?><a class="btn btn-sm btn-outline-brand" href="<?= e(url('messages.php', ['with' => $m['user_id']])) ?>">Reply on KaziHub</a><?php endif; ?>
                            <?php if ($m['status'] === 'new'): ?>
                                <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><button class="btn btn-sm btn-light">Mark handled</button></form>
                            <?php endif; ?>
                            <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-sm btn-light text-danger" data-confirm="Delete this message?"><i class="bi bi-trash"></i></button></form>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
