<?php
require __DIR__ . '/includes/bootstrap.php';
$user = require_role(['freelancer']);

$id = get_int('id');
$service = null;
if ($id) {
    $service = db_one('SELECT * FROM services WHERE id = ?', [$id]);
    if (!$service || ((int)$service['user_id'] !== (int)$user['id'] && $user['role'] !== 'admin')) {
        flash('danger', 'Service not found.');
        redirect('dashboard.php');
    }
}

$errors = [];
if (is_post()) {
    verify_csrf();

    if (post('action') === 'delete' && $service) {
        delete_upload($service['image']);
        db_exec('DELETE FROM services WHERE id = ?', [$service['id']]);
        flash('success', 'Service deleted.');
        redirect('dashboard.php');
    }

    $title = post('title');
    $category = post('category');
    $description = post('description');
    $price = post_decimal('price');
    $days = (int)post('delivery_days');
    $status = post('status') === 'paused' ? 'paused' : 'active';

    if (mb_strlen($title) < 10 || mb_strlen($title) > 150) $errors['title'] = 'Title should be 10–150 characters, e.g. "I will design a logo for your business".';
    if (!in_array($category, service_categories(), true)) $errors['category'] = 'Choose a category.';
    if (mb_strlen($description) < 30) $errors['description'] = 'Describe what the buyer gets (at least 30 characters).';
    if ($price === null || $price < 100) $errors['price'] = 'Minimum price is KES 100.';
    if ($days < 1 || $days > 90) $errors['delivery_days'] = 'Delivery must be between 1 and 90 days.';

    $image = null;
    try {
        $image = upload_image('image', 'services');
    } catch (RuntimeException $ex) {
        $errors['image'] = $ex->getMessage();
    }

    if (!$errors) {
        if ($service) {
            db_exec('UPDATE services SET title=?, category=?, description=?, price=?, delivery_days=?, status=? WHERE id=?',
                [$title, $category, $description, $price, $days, $status, $service['id']]);
            if ($image) {
                delete_upload($service['image']);
                db_exec('UPDATE services SET image = ? WHERE id = ?', [$image, $service['id']]);
            }
            $sid = $service['id'];
            flash('success', 'Service updated.');
        } else {
            db_exec('INSERT INTO services (user_id, title, category, description, price, delivery_days, image, status, created_at) VALUES (?,?,?,?,?,?,?,?,?)',
                [$user['id'], $title, $category, $description, $price, $days, $image, $status, now()]);
            $sid = db()->lastInsertId();
            flash('success', 'Your service is live!');
        }
        redirect('service.php?id=' . $sid);
    }
}

$v = fn($k) => e($_POST[$k] ?? ($service[$k] ?? ''));
$err = fn($k) => isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . e($errors[$k]) . '</div>' : '';
$pageTitle = $service ? 'Edit service' : 'Create a service';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4" style="max-width:820px">
    <h1 class="h3 fw-bold mb-4"><?= $service ? 'Edit service' : 'Create a service' ?></h1>
    <form method="post" enctype="multipart/form-data" class="card">
        <?= csrf_field() ?>
        <div class="card-body row g-3">
            <div class="col-12">
                <label class="form-label fw-semibold">Service title</label>
                <input type="text" name="title" class="form-control" maxlength="150" value="<?= $v('title') ?>" placeholder="I will …" required>
                <?= $err('title') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Category</label>
                <select name="category" class="form-select" required>
                    <option value="">Choose…</option>
                    <?php foreach (service_categories() as $c): ?><option <?= ($_POST['category'] ?? $service['category'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
                </select>
                <?= $err('category') ?>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Price (KES)</label>
                <input type="number" name="price" min="100" step="50" class="form-control" value="<?= $v('price') ?>" required>
                <?= $err('price') ?>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Delivery (days)</label>
                <input type="number" name="delivery_days" min="1" max="90" class="form-control" value="<?= $v('delivery_days') ?: 3 ?>" required>
                <?= $err('delivery_days') ?>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" rows="7" class="form-control" required placeholder="What's included, what you need from the buyer, revisions…"><?= $v('description') ?></textarea>
                <?= $err('description') ?>
            </div>
            <div class="col-md-8">
                <label class="form-label fw-semibold">Cover image <span class="text-muted small fw-normal">(optional, max 2 MB)</span></label>
                <input type="file" name="image" accept="image/*" class="form-control" data-preview="#svc-preview">
                <?= $err('image') ?>
                <img id="svc-preview" src="<?= e($service['image'] ?? '') ?>" class="mt-2 rounded <?= empty($service['image']) ? 'd-none' : '' ?>" style="max-height:140px" alt="">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="active">Active (visible)</option>
                    <option value="paused" <?= ($_POST['status'] ?? $service['status'] ?? '') === 'paused' ? 'selected' : '' ?>>Paused (hidden)</option>
                </select>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between py-3">
            <a href="dashboard.php" class="btn btn-link text-muted">Cancel</a>
            <button class="btn btn-primary px-4"><?= $service ? 'Save changes' : 'Publish service' ?></button>
        </div>
    </form>
    <?php if ($service): ?>
        <form method="post" class="mt-3 text-end">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete">
            <button class="btn btn-sm btn-outline-danger" data-confirm="Delete this service permanently?"><i class="bi bi-trash me-1"></i>Delete service</button>
        </form>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
