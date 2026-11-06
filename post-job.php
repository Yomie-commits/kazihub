<?php
require __DIR__ . '/includes/bootstrap.php';
$user = require_role(['client']);

$id = get_int('id');
$job = null;
if ($id) {
    $job = db_one('SELECT * FROM jobs WHERE id = ?', [$id]);
    if (!$job || ((int)$job['client_id'] !== (int)$user['id'] && $user['role'] !== 'admin')) {
        flash('danger', 'Job not found.');
        redirect('dashboard.php');
    }
}

$errors = [];
if (is_post()) {
    verify_csrf();

    if (post('action') === 'delete' && $job) {
        db_exec('DELETE FROM jobs WHERE id = ?', [$job['id']]);
        flash('success', 'Job deleted.');
        redirect('dashboard.php');
    }

    $d = [
        'type'          => post('type'),
        'title'         => post('title'),
        'category'      => post('category'),
        'description'   => post('description'),
        'location'      => post('location'),
        'budget_min'    => post_decimal('budget_min'),
        'budget_max'    => post_decimal('budget_max'),
        'pay_period'    => post('pay_period'),
        'currency'      => post('currency'),
        'employer_name' => post('employer_name'),
        'visa_support'  => !empty($_POST['visa_support']) ? 1 : 0,
        'accommodation' => !empty($_POST['accommodation']) ? 1 : 0,
        'deadline'      => post('deadline'),
        'status'        => post('status', 'open'),
    ];
    $lat = post('latitude');
    $lng = post('longitude');

    if (!in_array($d['type'], ['freelance', 'juakali', 'uae'], true)) $errors['type'] = 'Choose a job type.';
    if (mb_strlen($d['title']) < 5 || mb_strlen($d['title']) > 150) $errors['title'] = 'Give the job a clear title (5–150 characters).';
    if (!isset($errors['type']) && !in_array($d['category'], categories_for($d['type']), true)) $errors['category'] = 'Choose a category.';
    if (mb_strlen($d['description']) < 30) $errors['description'] = 'Describe the job in a bit more detail (at least 30 characters).';
    if (!in_array($d['pay_period'], ['fixed', 'hourly', 'daily', 'monthly'], true)) $d['pay_period'] = 'fixed';
    if (!in_array($d['currency'], ['KES', 'AED', 'USD'], true)) $d['currency'] = 'KES';
    if (!in_array($d['status'], ['open', 'closed', 'filled'], true)) $d['status'] = 'open';
    if ($d['budget_min'] !== null && $d['budget_max'] !== null && $d['budget_min'] > $d['budget_max']) {
        $errors['budget_max'] = 'Maximum pay must be more than the minimum.';
    }
    if ($d['deadline'] !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d['deadline']) || strtotime($d['deadline']) < strtotime('today'))) {
        $errors['deadline'] = 'Deadline must be today or later.';
    }
    if ($d['type'] === 'juakali' && $d['location'] === '') $errors['location'] = 'Juakali jobs need a location so nearby workers can find them.';
    if ($d['type'] === 'uae') {
        if ($d['employer_name'] === '') $errors['employer_name'] = 'Name the UAE employer so we can verify the job.';
        if (!$d['location']) $d['location'] = 'UAE';
    } else {
        $d['employer_name'] = '';
        $d['visa_support'] = $d['accommodation'] = 0;
    }
    $coordsOk = $d['type'] === 'juakali' && valid_coords($lat, $lng);

    if (!$errors) {
        // UAE jobs are reviewed by an admin before they go live (and again after edits).
        $verified = ($d['type'] !== 'uae' || $user['role'] === 'admin') ? 1 : 0;
        $params = [
            $d['type'], $d['title'], $d['category'], $d['description'], $d['location'] ?: null,
            $coordsOk ? $lat : null, $coordsOk ? $lng : null, $d['budget_min'], $d['budget_max'], $d['pay_period'],
            $d['currency'], $d['employer_name'] ?: null, $d['visa_support'], $d['accommodation'], $verified,
            $d['deadline'] ?: null,
        ];
        if ($job) {
            db_exec('UPDATE jobs SET type=?, title=?, category=?, description=?, location=?, latitude=?, longitude=?, budget_min=?, budget_max=?,
                     pay_period=?, currency=?, employer_name=?, visa_support=?, accommodation=?, is_verified=?, deadline=?, status=? WHERE id=?',
                array_merge($params, [$d['status'], $job['id']]));
            $jid = $job['id'];
            flash('success', $verified ? 'Job updated.' : 'Job updated. UAE jobs are re-checked by our team after edits.');
        } else {
            db_exec('INSERT INTO jobs (type, title, category, description, location, latitude, longitude, budget_min, budget_max, pay_period,
                     currency, employer_name, visa_support, accommodation, is_verified, deadline, client_id, status, created_at)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                array_merge($params, [$user['id'], 'open', now()]));
            $jid = db()->lastInsertId();
            flash('success', $verified ? 'Your job is live!' : 'Thanks! Your UAE job will go live once our team verifies it (usually within 24 hours).');
        }
        redirect('job.php?id=' . $jid);
    }
}

$src = $_POST ?: ($job ?? ['type' => get('type', 'freelance')]);
$v = fn($k, $def = '') => e($src[$k] ?? $def);
$err = fn($k) => isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . e($errors[$k]) . '</div>' : '';
$curType = in_array($src['type'] ?? '', ['freelance', 'juakali', 'uae'], true) ? $src['type'] : 'freelance';

$pageTitle = $job ? 'Edit job' : 'Post a job';
$activeNav = 'post';
$catsJson = json_encode(['freelance' => service_categories(), 'juakali' => juakali_categories(), 'uae' => uae_categories()], JSON_HEX_TAG);
$extraScripts = "<script>window.KH_CATEGORIES = $catsJson;</script>";
// app.js reads KH_CATEGORIES on load, so define it before app.js runs.
$extraHead = $extraScripts;
$extraScripts = '';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4" style="max-width:860px">
    <h1 class="h3 fw-bold mb-1"><?= $job ? 'Edit job' : 'Post a job' ?></h1>
    <p class="text-muted mb-4">It's free. Good descriptions get better applicants.</p>

    <form method="post" class="card">
        <?= csrf_field() ?>
        <div class="card-body row g-3">
            <div class="col-12">
                <label class="form-label fw-semibold">Job type</label>
                <select name="type" id="job-type" class="form-select">
                    <option value="freelance" <?= $curType === 'freelance' ? 'selected' : '' ?>>Freelance / remote project</option>
                    <option value="juakali" <?= $curType === 'juakali' ? 'selected' : '' ?>>Juakali / local job (fundi, casual work)</option>
                    <option value="uae" <?= $curType === 'uae' ? 'selected' : '' ?>>UAE job (needs verification)</option>
                </select>
                <?= $err('type') ?>
            </div>
            <div class="col-md-8">
                <label class="form-label fw-semibold">Job title</label>
                <input type="text" name="title" maxlength="150" class="form-control" value="<?= $v('title') ?>" placeholder="e.g. Plumber needed to fix kitchen sink" required>
                <?= $err('title') ?>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Category</label>
                <select name="category" id="job-category" class="form-select" required>
                    <option value="">Choose a category</option>
                    <?php foreach (categories_for($curType) as $c): ?><option <?= ($src['category'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
                </select>
                <?= $err('category') ?>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" rows="6" class="form-control" required placeholder="What needs to be done, requirements, working hours, tools…"><?= $v('description') ?></textarea>
                <?= $err('description') ?>
            </div>

            <div class="col-12" data-show-for="uae">
                <div class="row g-3 p-3 rounded-3 bg-info-subtle mx-0">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">UAE employer name</label>
                        <input type="text" name="employer_name" class="form-control" value="<?= $v('employer_name') ?>" placeholder="e.g. Al Noor Hospitality LLC">
                        <?= $err('employer_name') ?>
                    </div>
                    <div class="col-md-6 d-flex align-items-end gap-4 pb-2">
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="visa_support" value="1" id="visa" <?= !empty($src['visa_support']) ? 'checked' : '' ?>><label class="form-check-label" for="visa">Visa provided</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="accommodation" value="1" id="acc" <?= !empty($src['accommodation']) ? 'checked' : '' ?>><label class="form-check-label" for="acc">Accommodation</label></div>
                    </div>
                    <div class="col-12 small text-muted"><i class="bi bi-info-circle me-1"></i>UAE jobs are reviewed by KaziHub before they're published, to protect job seekers from fake offers.</div>
                </div>
            </div>

            <div class="col-12" data-show-for="juakali uae">
                <label class="form-label fw-semibold">Location</label>
                <div class="input-group">
                    <input type="text" name="location" class="form-control" value="<?= $v('location') ?>" placeholder="e.g. Kilimani, Nairobi">
                    <button type="button" class="btn btn-outline-primary" data-show-for="juakali" data-geo-lat="#latitude" data-geo-lng="#longitude" data-geo-status="#geo-status"><i class="bi bi-crosshair me-1"></i>Use GPS (I'm at the job site)</button>
                </div>
                <input type="hidden" name="latitude" id="latitude" value="<?= $v('latitude') ?>">
                <input type="hidden" name="longitude" id="longitude" value="<?= $v('longitude') ?>">
                <div class="form-text" id="geo-status" data-show-for="juakali"><?= !empty($src['latitude']) ? 'GPS location saved.' : 'Adding GPS puts your job on the map and shows it to the nearest workers first.' ?></div>
                <?= $err('location') ?>
            </div>

            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Pay from</label>
                <input type="number" name="budget_min" min="0" step="50" class="form-control" value="<?= $v('budget_min') ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Pay up to</label>
                <input type="number" name="budget_max" min="0" step="50" class="form-control" value="<?= $v('budget_max') ?>">
                <?= $err('budget_max') ?>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Paid</label>
                <select name="pay_period" class="form-select">
                    <?php foreach (['fixed' => 'Fixed price', 'hourly' => 'Per hour', 'daily' => 'Per day', 'monthly' => 'Per month'] as $k => $l): ?>
                        <option value="<?= $k ?>" <?= ($src['pay_period'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Currency</label>
                <select name="currency" id="job-currency" class="form-select" <?= $job ? 'data-touched="1"' : '' ?>>
                    <?php foreach (['KES', 'AED', 'USD'] as $c): ?><option <?= ($src['currency'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Application deadline <span class="text-muted small fw-normal">optional</span></label>
                <input type="date" name="deadline" class="form-control" value="<?= $v('deadline') ?>" min="<?= date('Y-m-d') ?>">
                <?= $err('deadline') ?>
            </div>
            <?php if ($job): ?>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['open' => 'Open', 'filled' => 'Filled', 'closed' => 'Closed'] as $k => $l): ?>
                            <option value="<?= $k ?>" <?= ($src['status'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between py-3">
            <a href="dashboard.php" class="btn btn-link text-muted">Cancel</a>
            <button class="btn btn-primary px-4"><?= $job ? 'Save changes' : 'Post job' ?></button>
        </div>
    </form>
    <?php if ($job): ?>
        <form method="post" class="mt-3 text-end"><?= csrf_field() ?><input type="hidden" name="action" value="delete">
            <button class="btn btn-sm btn-outline-danger" data-confirm="Delete this job and all its applications?"><i class="bi bi-trash me-1"></i>Delete job</button></form>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
