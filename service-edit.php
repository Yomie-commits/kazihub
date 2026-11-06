<?php
require __DIR__ . '/includes/init.php';
$me = require_login(['worker']);

$id = (int) (get('id') ?: post('id'));
$service = $id ? db_one('SELECT * FROM services WHERE id = ? AND user_id = ?', [$id, $me['id']]) : null;
if ($id && !$service) {
    flash('danger', 'Service not found.');
    redirect('my-services.php');
}

// Delete / pause toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $service && in_array(post('action'), ['delete', 'toggle'], true)) {
    if (post('action') === 'delete') {
        db_exec('DELETE FROM services WHERE id = ?', [$service['id']]);
        delete_upload($service['image']);
        flash('success', 'Service deleted.');
    } else {
        db_exec('UPDATE services SET is_active = ? WHERE id = ?', [$service['is_active'] ? 0 : 1, $service['id']]);
        flash('success', $service['is_active'] ? 'Service paused. It is hidden from search.' : 'Service is live again.');
    }
    redirect('my-services.php');
}

$errors = [];
$form = $service ?: ['title' => '', 'category' => '', 'description' => '', 'price' => '', 'delivery_days' => 3, 'image' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = array_merge($form, [
        'title' => post('title'),
        'category' => post('category'),
        'description' => post('description'),
        'price' => post('price'),
        'delivery_days' => post('delivery_days'),
    ]);
    if (mb_strlen($form['title']) < 10 || mb_strlen($form['title']) > 150) $errors['title'] = 'Title should be 10–150 characters, e.g. “I will design a modern logo for your business”.';
    if (!in_array($form['category'], SERVICE_CATEGORIES, true)) $errors['category'] = 'Choose a category.';
    if (mb_strlen($form['description']) < 50) $errors['description'] = 'Describe your service in at least 50 characters.';
    if (!is_numeric($form['price']) || $form['price'] < 100 || $form['price'] > 10000000) $errors['price'] = 'Enter a price of at least KES 100.';
    if (!ctype_digit((string) $form['delivery_days']) || $form['delivery_days'] < 1 || $form['delivery_days'] > 90) $errors['delivery_days'] = 'Delivery must be 1–90 days.';

    if (!$errors) {
        try {
            $image = upload_image($_FILES['image'] ?? [], 'services');
        } catch (RuntimeException $ex) {
            $errors['image'] = $ex->getMessage();
        }
    }

    if (!$errors) {
        $data = [
            'title' => $form['title'],
            'category' => $form['category'],
            'description' => $form['description'],
            'price' => (float) $form['price'],
            'delivery_days' => (int) $form['delivery_days'],
        ];
        if (!empty($image)) {
            $data['image'] = $image;
        }
        if ($service) {
            if (!empty($image)) delete_upload($service['image']);
            db_update('services', (int) $service['id'], $data);
            flash('success', 'Service updated.');
            redirect('service.php', ['id' => $service['id']]);
        }
        $data['user_id'] = $me['id'];
        $data['created_at'] = now();
        $newId = db_insert('services', $data);
        flash('success', 'Your service is live! Share the link to get your first order.');
        redirect('service.php', ['id' => $newId]);
    }
}

$pageTitle = $service ? 'Edit service' : 'Create a service';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <a href="<?= e(url('my-services.php')) ?>" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> My services</a>
            <h1 class="h3 fw-bold mt-2 mb-4"><?= e($pageTitle) ?></h1>
            <form method="post" enctype="multipart/form-data" class="card" novalidate>
                <div class="card-body p-4">
                    <?= csrf_field() ?>
                    <?php if ($service): ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>"><?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label" for="title">Service title</label>
                        <input class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" id="title" name="title" maxlength="150" value="<?= e($form['title']) ?>" placeholder="I will…" required>
                        <div class="invalid-feedback"><?= e($errors['title'] ?? '') ?></div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="category">Category</label>
                            <select class="form-select <?= isset($errors['category']) ? 'is-invalid' : '' ?>" id="category" name="category" required>
                                <option value="">Choose…</option>
                                <?= options(SERVICE_CATEGORIES, $form['category']) ?>
                            </select>
                            <div class="invalid-feedback"><?= e($errors['category'] ?? '') ?></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="price">Price (KES)</label>
                            <input type="number" min="100" step="50" class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>" id="price" name="price" value="<?= e($form['price'] !== '' ? (int) $form['price'] : '') ?>" required>
                            <div class="invalid-feedback"><?= e($errors['price'] ?? '') ?></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="delivery_days">Delivery (days)</label>
                            <input type="number" min="1" max="90" class="form-control <?= isset($errors['delivery_days']) ? 'is-invalid' : '' ?>" id="delivery_days" name="delivery_days" value="<?= e($form['delivery_days']) ?>" required>
                            <div class="invalid-feedback"><?= e($errors['delivery_days'] ?? '') ?></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" id="description" name="description" rows="8" required placeholder="What's included, what you need from the client, how revisions work…"><?= e($form['description']) ?></textarea>
                        <div class="invalid-feedback"><?= e($errors['description'] ?? '') ?></div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="image">Cover image <span class="text-body-secondary fw-normal">(optional)</span></label>
                        <input type="file" class="form-control <?= isset($errors['image']) ? 'is-invalid' : '' ?>" id="image" name="image" accept="image/*" data-preview="#imgPreview">
                        <div class="invalid-feedback"><?= e($errors['image'] ?? '') ?></div>
                        <img id="imgPreview" src="<?= $form['image'] ? e(url($form['image'])) : '' ?>" class="img-fluid rounded mt-2 <?= $form['image'] ? '' : 'd-none' ?>" style="max-height:200px" alt="">
                    </div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between p-3">
                    <a href="<?= e(url('my-services.php')) ?>" class="btn btn-light">Cancel</a>
                    <button class="btn btn-brand px-4"><?= $service ? 'Save changes' : 'Publish service' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
