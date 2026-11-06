<?php
require __DIR__ . '/includes/init.php';
$me = require_login(['employer', 'admin']);

$id = (int) (get('id') ?: post('id'));
$job = null;
if ($id) {
    $job = is_admin()
        ? db_one('SELECT * FROM jobs WHERE id = ?', [$id])
        : db_one('SELECT * FROM jobs WHERE id = ? AND employer_id = ?', [$id, $me['id']]);
    if (!$job) {
        flash('danger', 'Job not found.');
        redirect('my-jobs.php');
    }
}

// Close / reopen / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $job && in_array(post('action'), ['close', 'reopen', 'delete'], true)) {
    $action = post('action');
    if ($action === 'delete') {
        db_exec('DELETE FROM jobs WHERE id = ?', [$job['id']]);
        flash('success', 'Job deleted.');
    } elseif ($action === 'close') {
        db_exec("UPDATE jobs SET status = 'closed' WHERE id = ?", [$job['id']]);
        flash('success', 'Job closed. It no longer accepts applications.');
    } elseif ($action === 'reopen' && $job['status'] === 'closed') {
        $newStatus = ($job['type'] === 'uae' && !is_admin()) ? 'pending' : 'open';
        db_exec('UPDATE jobs SET status = ? WHERE id = ?', [$newStatus, $job['id']]);
        flash('success', $newStatus === 'pending' ? 'Job sent for review again.' : 'Job reopened.');
    }
    redirect('my-jobs.php');
}

$type = $job['type'] ?? (get('type') === 'uae' ? 'uae' : 'juakali');
$form = $job ?: [
    'type' => $type, 'title' => '', 'category' => '', 'description' => '', 'county' => $me['county'] ?? '', 'town' => '',
    'latitude' => '', 'longitude' => '', 'budget_min' => '', 'budget_max' => '', 'pay_period' => 'fixed',
    'urgency' => 'normal', 'company' => $me['company_name'] ?? '', 'visa_sponsored' => 0, 'accommodation' => 0,
    'positions' => 1, 'deadline' => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $job ? $job['type'] : (post('type') === 'uae' ? 'uae' : 'juakali');
    $form = array_merge($form, [
        'type' => $type,
        'title' => post('title'),
        'category' => $type === 'uae' ? post('category_uae') : post('category_juakali'),
        'description' => post('description'),
        'county' => $type === 'uae' ? post('emirate') : post('county'),
        'town' => post('town'),
        'latitude' => post('latitude'),
        'longitude' => post('longitude'),
        'budget_min' => post('budget_min'),
        'budget_max' => post('budget_max'),
        'pay_period' => post('pay_period'),
        'urgency' => post('urgency') === 'urgent' ? 'urgent' : 'normal',
        'company' => post('company'),
        'visa_sponsored' => isset($_POST['visa_sponsored']) ? 1 : 0,
        'accommodation' => isset($_POST['accommodation']) ? 1 : 0,
        'positions' => post('positions', '1'),
        'deadline' => post('deadline'),
    ]);

    if (mb_strlen($form['title']) < 5 || mb_strlen($form['title']) > 150) $errors['title'] = 'Title should be 5–150 characters.';
    $cats = $type === 'uae' ? UAE_CATEGORIES : JUAKALI_CATEGORIES;
    if (!in_array($form['category'], $cats, true)) $errors['category'] = 'Choose a category.';
    if (mb_strlen($form['description']) < 30) $errors['description'] = 'Describe the job in at least 30 characters.';
    $places = $type === 'uae' ? UAE_EMIRATES : KENYA_COUNTIES;
    if (!in_array($form['county'], $places, true)) $errors['county'] = $type === 'uae' ? 'Choose an emirate.' : 'Choose a county.';
    foreach (['budget_min', 'budget_max'] as $b) {
        if ($form[$b] !== '' && (!is_numeric($form[$b]) || $form[$b] < 0)) $errors[$b] = 'Enter a valid amount.';
    }
    if (!$errors && $form['budget_min'] !== '' && $form['budget_max'] !== '' && (float) $form['budget_max'] < (float) $form['budget_min']) {
        $errors['budget_max'] = 'Maximum must be more than minimum.';
    }
    if (!in_array($form['pay_period'], ['fixed', 'daily', 'monthly'], true)) $form['pay_period'] = 'fixed';
    if (!ctype_digit((string) $form['positions']) || $form['positions'] < 1 || $form['positions'] > 500) $errors['positions'] = 'Enter 1–500.';
    if ($form['deadline'] !== '' && (!strtotime($form['deadline']) || $form['deadline'] < date('Y-m-d'))) $errors['deadline'] = 'Deadline must be today or later.';
    if ($type === 'uae' && mb_strlen($form['company']) < 2) $errors['company'] = 'Enter the hiring company or licensed agency name.';

    $hasCoords = $type === 'juakali' && is_numeric($form['latitude']) && is_numeric($form['longitude']);

    if (!$errors) {
        $data = [
            'title' => $form['title'],
            'category' => $form['category'],
            'description' => $form['description'],
            'county' => $form['county'],
            'town' => $form['town'] ?: null,
            'latitude' => $hasCoords ? (float) $form['latitude'] : null,
            'longitude' => $hasCoords ? (float) $form['longitude'] : null,
            'budget_min' => $form['budget_min'] === '' ? null : (float) $form['budget_min'],
            'budget_max' => $form['budget_max'] === '' ? null : (float) $form['budget_max'],
            'currency' => $type === 'uae' ? 'AED' : 'KES',
            'pay_period' => $form['pay_period'],
            'urgency' => $type === 'juakali' ? $form['urgency'] : 'normal',
            'company' => $type === 'uae' ? $form['company'] : null,
            'visa_sponsored' => $type === 'uae' ? $form['visa_sponsored'] : 0,
            'accommodation' => $type === 'uae' ? $form['accommodation'] : 0,
            'positions' => (int) $form['positions'],
            'deadline' => $form['deadline'] ?: null,
        ];
        // UAE jobs always go through admin review (also after edits) so listings can't be changed after approval.
        $needsReview = $type === 'uae' && !is_admin();

        if ($job) {
            if ($needsReview && $job['status'] !== 'closed') {
                $data['status'] = 'pending';
            }
            db_update('jobs', (int) $job['id'], $data);
            flash('success', $needsReview ? 'Job updated and sent for review. It will be visible again once approved.' : 'Job updated.');
            redirect('job.php', ['id' => $job['id']]);
        }
        $data += ['employer_id' => $me['id'], 'type' => $type, 'status' => $needsReview ? 'pending' : 'open', 'created_at' => now()];
        $newId = db_insert('jobs', $data);
        flash('success', $needsReview
            ? 'Thanks! Your UAE job has been sent to our team for review. We usually approve listings within 24 hours.'
            : 'Your job is live. Workers nearby can now apply.');
        redirect('job.php', ['id' => $newId]);
    }
}

$pageTitle = $job ? 'Edit job' : 'Post a job';
require __DIR__ . '/includes/header.php';
$err = fn($k) => isset($errors[$k]) ? ' is-invalid' : '';
$fb = fn($k) => '<div class="invalid-feedback">' . e($errors[$k] ?? '') . '</div>';
?>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <a href="<?= e(url('my-jobs.php')) ?>" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> My job posts</a>
            <h1 class="h3 fw-bold mt-2 mb-4"><?= e($pageTitle) ?></h1>
            <form method="post" class="card" novalidate>
                <div class="card-body p-4">
                    <?= csrf_field() ?>
                    <?php if ($job): ?><input type="hidden" name="id" value="<?= (int) $job['id'] ?>"><?php endif; ?>

                    <label class="form-label">Job type</label>
                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="type" id="type-juakali" value="juakali" <?= $form['type'] === 'juakali' ? 'checked' : '' ?> <?= $job ? 'disabled' : '' ?>>
                            <label class="btn btn-outline-brand w-100 py-3" for="type-juakali"><i class="bi bi-tools d-block fs-4"></i>Juakali (Kenya)</label>
                        </div>
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="type" id="type-uae" value="uae" <?= $form['type'] === 'uae' ? 'checked' : '' ?> <?= $job ? 'disabled' : '' ?>>
                            <label class="btn btn-outline-brand w-100 py-3" for="type-uae"><i class="bi bi-airplane d-block fs-4"></i>UAE job</label>
                        </div>
                    </div>

                    <div class="alert alert-info small d-none" data-for-type="uae">
                        <i class="bi bi-shield-check"></i> UAE jobs are reviewed by our team before they appear. Include the real employer or your NEA-licensed agency name. Listings that ask workers for fees will be removed.
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="title">Job title</label>
                        <input class="form-control<?= $err('title') ?>" id="title" name="title" maxlength="150" value="<?= e($form['title']) ?>" placeholder="e.g. Fix leaking kitchen sink" required>
                        <?= $fb('title') ?>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6" data-for-type="juakali">
                            <label class="form-label" for="category_juakali">Category</label>
                            <select class="form-select<?= $err('category') ?>" id="category_juakali" name="category_juakali">
                                <option value="">Choose…</option><?= options(JUAKALI_CATEGORIES, $form['category']) ?>
                            </select>
                            <?= $fb('category') ?>
                        </div>
                        <div class="col-md-6" data-for-type="uae">
                            <label class="form-label" for="category_uae">Industry</label>
                            <select class="form-select<?= $err('category') ?>" id="category_uae" name="category_uae">
                                <option value="">Choose…</option><?= options(UAE_CATEGORIES, $form['category']) ?>
                            </select>
                            <?= $fb('category') ?>
                        </div>
                        <div class="col-md-6" data-for-type="uae">
                            <label class="form-label" for="company">Hiring company / agency</label>
                            <input class="form-control<?= $err('company') ?>" id="company" name="company" value="<?= e($form['company']) ?>">
                            <?= $fb('company') ?>
                        </div>
                        <div class="col-md-6" data-for-type="juakali">
                            <label class="form-label" for="urgency">Urgency</label>
                            <select class="form-select" id="urgency" name="urgency">
                                <?= options(['normal' => 'Normal', 'urgent' => 'Urgent (today / tomorrow)'], $form['urgency'], true) ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control<?= $err('description') ?>" id="description" name="description" rows="6" required placeholder="What needs to be done, skills or tools required, working hours…"><?= e($form['description']) ?></textarea>
                        <?= $fb('description') ?>
                    </div>

                    <h6 class="fw-bold mt-4">Location</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6" data-for-type="juakali">
                            <label class="form-label" for="county">County</label>
                            <select class="form-select<?= $err('county') ?>" id="county" name="county">
                                <option value="">Choose…</option><?= options(KENYA_COUNTIES, $form['type'] === 'juakali' ? $form['county'] : '') ?>
                            </select>
                            <?= $fb('county') ?>
                        </div>
                        <div class="col-md-6" data-for-type="uae">
                            <label class="form-label" for="emirate">Emirate</label>
                            <select class="form-select<?= $err('county') ?>" id="emirate" name="emirate">
                                <option value="">Choose…</option><?= options(UAE_EMIRATES, $form['type'] === 'uae' ? $form['county'] : '') ?>
                            </select>
                            <?= $fb('county') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="town">Town / area</label>
                            <input class="form-control" id="town" name="town" value="<?= e($form['town']) ?>" placeholder="e.g. Westlands">
                        </div>
                        <div class="col-12" data-for-type="juakali">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-geo-fill data-lat="#latitude" data-lng="#longitude" data-status="#geoStatus"><i class="bi bi-crosshair"></i> Use my current location (job site)</button>
                            <span id="geoStatus" class="small text-body-secondary ms-2"><?= $form['latitude'] ? '<i class="bi bi-check-circle-fill text-success"></i> Location set' : 'Helps nearby workers find this job' ?></span>
                            <input type="hidden" id="latitude" name="latitude" value="<?= e($form['latitude']) ?>">
                            <input type="hidden" id="longitude" name="longitude" value="<?= e($form['longitude']) ?>">
                        </div>
                    </div>

                    <h6 class="fw-bold mt-4">Pay</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-4">
                            <label class="form-label" for="budget_min">Minimum <span class="text-body-secondary fw-normal" data-for-type="juakali">(KES)</span><span class="text-body-secondary fw-normal" data-for-type="uae">(AED)</span></label>
                            <input type="number" min="0" class="form-control<?= $err('budget_min') ?>" id="budget_min" name="budget_min" value="<?= e($form['budget_min'] !== '' && $form['budget_min'] !== null ? (int) $form['budget_min'] : '') ?>">
                            <?= $fb('budget_min') ?>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label" for="budget_max">Maximum</label>
                            <input type="number" min="0" class="form-control<?= $err('budget_max') ?>" id="budget_max" name="budget_max" value="<?= e($form['budget_max'] !== '' && $form['budget_max'] !== null ? (int) $form['budget_max'] : '') ?>">
                            <?= $fb('budget_max') ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="pay_period">Paid</label>
                            <select class="form-select" id="pay_period" name="pay_period">
                                <?= options(['fixed' => 'Per job (fixed)', 'daily' => 'Per day', 'monthly' => 'Per month'], $form['pay_period'], true) ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-6 col-md-4">
                            <label class="form-label" for="positions">Workers needed</label>
                            <input type="number" min="1" max="500" class="form-control<?= $err('positions') ?>" id="positions" name="positions" value="<?= e($form['positions']) ?>">
                            <?= $fb('positions') ?>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label" for="deadline">Apply by <span class="text-body-secondary fw-normal">(optional)</span></label>
                            <input type="date" class="form-control<?= $err('deadline') ?>" id="deadline" name="deadline" value="<?= e($form['deadline']) ?>" min="<?= date('Y-m-d') ?>">
                            <?= $fb('deadline') ?>
                        </div>
                        <div class="col-md-4 d-flex flex-column justify-content-end" data-for-type="uae">
                            <div class="form-check"><input class="form-check-input" type="checkbox" id="visa_sponsored" name="visa_sponsored" value="1" <?= $form['visa_sponsored'] ? 'checked' : '' ?>><label class="form-check-label" for="visa_sponsored">Visa sponsored</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" id="accommodation" name="accommodation" value="1" <?= $form['accommodation'] ? 'checked' : '' ?>><label class="form-check-label" for="accommodation">Accommodation provided</label></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between p-3">
                    <a href="<?= e(url('my-jobs.php')) ?>" class="btn btn-light">Cancel</a>
                    <button class="btn btn-brand px-4"><?= $job ? 'Save changes' : 'Post job' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
