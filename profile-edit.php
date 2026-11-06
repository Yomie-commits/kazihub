<?php
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();
$errors = [];

if (is_post()) {
    verify_csrf();
    $action = post('action', 'profile');

    if ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        if (!password_verify($current, $user['password_hash'])) {
            $errors['current_password'] = 'Your current password is incorrect.';
        } elseif (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
            $errors['new_password'] = 'New password must be at least 8 characters with letters and numbers.';
        } elseif ($new !== ($_POST['new_password_confirm'] ?? '')) {
            $errors['new_password'] = 'New passwords do not match.';
        } else {
            db_exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            flash('success', 'Password changed.');
            redirect('profile-edit.php');
        }
    } else {
        $data = [
            'name'         => post('name'),
            'phone'        => post('phone'),
            'location'     => post('location'),
            'headline'     => post('headline'),
            'bio'          => post('bio'),
            'skills'       => implode(', ', array_slice(skills_list(post('skills')), 0, 15)),
            'experience'   => post('experience'),
            'hourly_rate'  => post_decimal('hourly_rate'),
            'company_name' => post('company_name'),
            'company_size' => post('company_size'),
            'industry'     => post('industry'),
        ];
        $lat = post('latitude');
        $lng = post('longitude');

        if (mb_strlen($data['name']) < 2) $errors['name'] = 'Please enter your name.';
        if ($data['phone'] !== '' && !preg_match('/^\+?[0-9 ]{9,15}$/', $data['phone'])) $errors['phone'] = 'Enter a valid phone number.';
        if (mb_strlen($data['bio']) > 2000) $errors['bio'] = 'Keep your bio under 2000 characters.';
        if ($data['experience'] !== '' && !in_array($data['experience'], experience_levels(), true)) $data['experience'] = '';

        $avatarPath = null;
        try {
            $avatarPath = upload_image('avatar', 'avatars');
        } catch (RuntimeException $ex) {
            $errors['avatar'] = $ex->getMessage();
        }

        if (!$errors) {
            $coordsOk = valid_coords($lat, $lng);
            db_exec(
                'UPDATE users SET name=?, phone=?, location=?, headline=?, bio=?, skills=?, experience=?, hourly_rate=?,
                    company_name=?, company_size=?, industry=?, latitude=?, longitude=? WHERE id=?',
                [
                    $data['name'], $data['phone'] ?: null, $data['location'] ?: null, $data['headline'] ?: null,
                    $data['bio'] ?: null, $data['skills'] ?: null, $data['experience'] ?: null, $data['hourly_rate'],
                    $data['company_name'] ?: null, $data['company_size'] ?: null, $data['industry'] ?: null,
                    $coordsOk ? $lat : $user['latitude'], $coordsOk ? $lng : $user['longitude'], $user['id'],
                ]
            );
            if ($avatarPath) {
                delete_upload($user['avatar']);
                db_exec('UPDATE users SET avatar = ? WHERE id = ?', [$avatarPath, $user['id']]);
            }
            if (!empty($_POST['remove_avatar']) && !$avatarPath) {
                delete_upload($user['avatar']);
                db_exec('UPDATE users SET avatar = NULL WHERE id = ?', [$user['id']]);
            }
            flash('success', 'Profile saved.');
            redirect('profile-edit.php');
        }
    }
}

$u = $user;
$completion = profile_completion($u);
$isWorker = in_array($u['role'], ['freelancer', 'job_seeker'], true);
$isClient = $u['role'] === 'client';
$val = fn($k) => e($_POST[$k] ?? $u[$k] ?? '');
$err = fn($k) => isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . e($errors[$k]) . '</div>' : '';

$pageTitle = 'Edit profile';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body text-center">
                    <div class="mb-3 d-flex justify-content-center"><?= avatar($u, 96) ?></div>
                    <h5 class="fw-bold mb-0"><?= e($u['name']) ?></h5>
                    <div class="small text-muted mb-3"><?= e(role_label($u['role'])) ?></div>
                    <div class="d-flex justify-content-between small mb-1"><span>Profile strength</span><strong><?= $completion ?>%</strong></div>
                    <div class="progress mb-3" style="height:8px"><div class="progress-bar bg-primary" style="width:<?= $completion ?>%"></div></div>
                    <?php if ($completion < 80): ?>
                        <p class="small text-muted mb-3">Profiles over 80% get noticed far more. Add a photo, bio and <?= $isClient ? 'company details' : 'skills' ?>.</p>
                    <?php endif; ?>
                    <a href="profile.php?id=<?= (int)$u['id'] ?>" class="btn btn-outline-primary btn-sm">View public profile</a>
                </div>
            </div>
            <?php if (get('welcome')): ?>
                <div class="alert alert-success small"><strong>Next steps:</strong>
                    <?= $u['role'] === 'freelancer' ? 'Complete your profile, then create your first service from the dashboard.' :
                        ($isClient ? 'Add your company details, then post your first job.' : 'Add your skills and location so we can show jobs near you.') ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-8">
            <form method="post" enctype="multipart/form-data" class="card mb-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="profile">
                <div class="card-header bg-white fw-bold py-3">Profile details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Profile photo</label>
                            <div class="d-flex align-items-center gap-3">
                                <img id="avatar-preview" class="rounded-circle object-fit-cover d-none" width="64" height="64" alt="">
                                <input type="file" name="avatar" accept="image/*" class="form-control" data-preview="#avatar-preview">
                            </div>
                            <?= $err('avatar') ?>
                            <?php if ($u['avatar']): ?>
                                <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_avatar" value="1" id="rm-av"><label class="form-check-label small" for="rm-av">Remove current photo</label></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Full name<?= $isClient ? ' / contact person' : '' ?></label>
                            <input type="text" name="name" class="form-control" value="<?= $val('name') ?>" required>
                            <?= $err('name') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control" value="<?= $val('phone') ?>" placeholder="0712 345 678">
                            <?= $err('phone') ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Headline</label>
                            <input type="text" name="headline" maxlength="150" class="form-control" value="<?= $val('headline') ?>"
                                   placeholder="<?= $isClient ? 'e.g. Construction company in Nairobi' : 'e.g. Experienced plumber · Logo designer · Hotel housekeeper' ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Location</label>
                            <div class="input-group">
                                <input type="text" name="location" class="form-control" value="<?= $val('location') ?>" placeholder="e.g. Kasarani, Nairobi">
                                <button type="button" class="btn btn-outline-primary" data-geo-lat="#latitude" data-geo-lng="#longitude" data-geo-status="#geo-status"><i class="bi bi-crosshair me-1"></i>Use my GPS</button>
                            </div>
                            <input type="hidden" name="latitude" id="latitude" value="<?= e($u['latitude']) ?>">
                            <input type="hidden" name="longitude" id="longitude" value="<?= e($u['longitude']) ?>">
                            <div class="form-text" id="geo-status">
                                <?= $u['latitude'] ? 'GPS location saved. Juakali jobs will be sorted by distance from here.' : 'Add your GPS location to see the nearest juakali jobs and appear in "workers near me".' ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">About <?= $isClient ? 'your company' : 'you' ?></label>
                            <textarea name="bio" rows="4" class="form-control" maxlength="2000"><?= $val('bio') ?></textarea>
                            <?= $err('bio') ?>
                        </div>

                        <?php if ($isWorker): ?>
                            <div class="col-12">
                                <label class="form-label">Skills <span class="text-muted small">(separate with commas)</span></label>
                                <input type="text" name="skills" class="form-control" value="<?= $val('skills') ?>" placeholder="e.g. Plumbing, Tiling, Welding">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Experience</label>
                                <select name="experience" class="form-select">
                                    <option value="">Select…</option>
                                    <?php foreach (experience_levels() as $lvl): ?>
                                        <option <?= ($_POST['experience'] ?? $u['experience']) === $lvl ? 'selected' : '' ?>><?= e($lvl) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?= $u['role'] === 'freelancer' ? 'Hourly rate (KES)' : 'Expected daily rate (KES)' ?></label>
                                <input type="number" min="0" step="50" name="hourly_rate" class="form-control" value="<?= $val('hourly_rate') ?>">
                            </div>
                        <?php endif; ?>

                        <?php if ($isClient): ?>
                            <div class="col-md-6">
                                <label class="form-label">Company name</label>
                                <input type="text" name="company_name" class="form-control" value="<?= $val('company_name') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Company size</label>
                                <select name="company_size" class="form-select">
                                    <option value="">Select…</option>
                                    <?php foreach (['Just me', '2-10', '11-50', '51-200', '200+'] as $sz): ?>
                                        <option <?= ($_POST['company_size'] ?? $u['company_size']) === $sz ? 'selected' : '' ?>><?= $sz ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Industry</label>
                                <input type="text" name="industry" class="form-control" value="<?= $val('industry') ?>" placeholder="e.g. Construction">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer bg-white text-end py-3">
                    <button class="btn btn-primary px-4">Save profile</button>
                </div>
            </form>

            <form method="post" class="card">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="password">
                <div class="card-header bg-white fw-bold py-3">Change password</div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Current password</label>
                        <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                        <?= $err('current_password') ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">New password</label>
                        <input type="password" name="new_password" class="form-control" required autocomplete="new-password">
                        <?= $err('new_password') ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Confirm new password</label>
                        <input type="password" name="new_password_confirm" class="form-control" required autocomplete="new-password">
                    </div>
                </div>
                <div class="card-footer bg-white text-end py-3">
                    <button class="btn btn-outline-primary">Update password</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
