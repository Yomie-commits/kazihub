<?php
require __DIR__ . '/includes/init.php';
$me = require_login();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    if (!password_verify($current, $me['password_hash'])) {
        flash('danger', 'Your current password is incorrect.');
    } elseif (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
        flash('danger', 'New password needs 8+ characters with a letter and a number.');
    } elseif ($new !== ($_POST['new_password_confirm'] ?? '')) {
        flash('danger', 'New passwords do not match.');
    } else {
        db_exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $me['id']]);
        session_regenerate_id(true);
        flash('success', 'Password updated.');
    }
    redirect('profile.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'profile') {
    $data = [
        'name'   => post('name'),
        'phone'  => post('phone') ?: null,
        'county' => post('county') ?: null,
        'town'   => post('town') ?: null,
        'bio'    => post('bio') ?: null,
    ];
    $lat = post('latitude');
    $lng = post('longitude');
    if ($lat !== '' && $lng !== '' && is_numeric($lat) && is_numeric($lng) && abs((float) $lat) <= 90 && abs((float) $lng) <= 180) {
        $data['latitude'] = (float) $lat;
        $data['longitude'] = (float) $lng;
    }

    if (mb_strlen($data['name']) < 2) $errors['name'] = 'Enter your full name.';
    if ($data['phone'] && !preg_match('/^\+?[0-9 ]{9,15}$/', $data['phone'])) $errors['phone'] = 'Enter a valid phone number.';
    if ($data['county'] && !in_array($data['county'], KENYA_COUNTIES, true)) $errors['county'] = 'Choose a county from the list.';
    if ($data['bio'] && mb_strlen($data['bio']) > 1500) $errors['bio'] = 'Keep your bio under 1500 characters.';

    if ($me['role'] === 'worker') {
        $skills = array_slice(array_unique(array_filter(array_map(fn($s) => mb_substr(trim($s), 0, 40), explode(',', post('skills'))))), 0, 15);
        $data['skills'] = $skills ? implode(', ', $skills) : null;
        $data['experience_years'] = post('experience_years') === '' ? null : max(0, min(60, (int) post('experience_years')));
        $data['hourly_rate'] = post('hourly_rate') === '' ? null : max(0, (float) post('hourly_rate'));
        $data['is_available'] = isset($_POST['is_available']) ? 1 : 0;
    } else {
        $data['company_name'] = post('company_name') ?: null;
    }

    if (!$errors) {
        try {
            $avatar = upload_image($_FILES['avatar'] ?? [], 'avatars');
            if ($avatar) {
                delete_upload($me['avatar']);
                $data['avatar'] = $avatar;
            }
        } catch (RuntimeException $ex) {
            $errors['avatar'] = $ex->getMessage();
        }
    }

    if (!$errors) {
        db_update('users', (int) $me['id'], $data);
        flash('success', 'Profile saved.');
        redirect('profile.php');
    }
    $me = array_merge($me, $_POST);
}

$completion = profile_completion($me);
$pageTitle = 'Edit profile';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-0">Your profile</h1>
            <p class="text-body-secondary mb-0">A complete profile gets more work. <a href="<?= e(url('user.php', ['id' => $me['id']])) ?>">See your public profile</a></p>
        </div>
        <div style="min-width:220px">
            <div class="d-flex justify-content-between small"><span>Profile strength</span><strong><?= $completion ?>%</strong></div>
            <div class="progress" role="progressbar" aria-valuenow="<?= $completion ?>" aria-valuemin="0" aria-valuemax="100" style="height:8px">
                <div class="progress-bar bg-brand" style="width:<?= $completion ?>%"></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <form method="post" enctype="multipart/form-data" class="card">
                <div class="card-body p-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="profile">

                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div id="avatarWrap"><?= avatar($me, 80) ?></div>
                        <img id="avatarPreview" class="rounded-circle object-fit-cover d-none" width="80" height="80" alt="">
                        <div>
                            <label class="form-label mb-1" for="avatar">Profile photo</label>
                            <input class="form-control form-control-sm <?= isset($errors['avatar']) ? 'is-invalid' : '' ?>" type="file" id="avatar" name="avatar" accept="image/*" data-preview="#avatarPreview">
                            <div class="invalid-feedback"><?= e($errors['avatar'] ?? '') ?></div>
                            <div class="form-text">A clear photo of your face builds trust. Max 2 MB.</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Full name</label>
                            <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= e($me['name']) ?>" required>
                            <div class="invalid-feedback"><?= e($errors['name'] ?? '') ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="phone">Phone</label>
                            <input class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" id="phone" name="phone" value="<?= e($me['phone']) ?>" placeholder="07xx xxx xxx">
                            <div class="invalid-feedback"><?= e($errors['phone'] ?? '') ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="county">County</label>
                            <select class="form-select <?= isset($errors['county']) ? 'is-invalid' : '' ?>" id="county" name="county">
                                <option value="">Choose…</option>
                                <?= options(KENYA_COUNTIES, $me['county']) ?>
                            </select>
                            <div class="invalid-feedback"><?= e($errors['county'] ?? '') ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="town">Town / area</label>
                            <input class="form-control" id="town" name="town" value="<?= e($me['town']) ?>" placeholder="e.g. Kasarani">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Exact location <span class="text-body-secondary fw-normal">(for “near me” matching)</span></label>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-geo-fill data-lat="#latitude" data-lng="#longitude" data-status="#geoStatus"><i class="bi bi-crosshair"></i> Use my current location</button>
                                <span id="geoStatus" class="small text-body-secondary">
                                    <?= $me['latitude'] ? '<i class="bi bi-check-circle-fill text-success"></i> Location set' : 'Not set' ?>
                                </span>
                            </div>
                            <input type="hidden" id="latitude" name="latitude" value="<?= e($me['latitude']) ?>">
                            <input type="hidden" id="longitude" name="longitude" value="<?= e($me['longitude']) ?>">
                            <div class="form-text">Only the distance is shown to others, never your exact position.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="bio">About you</label>
                            <textarea class="form-control <?= isset($errors['bio']) ? 'is-invalid' : '' ?>" id="bio" name="bio" rows="4" maxlength="1500" placeholder="<?= $me['role'] === 'worker' ? 'Describe your experience, what you do best and where you work.' : 'Tell workers about you or your business.' ?>"><?= e($me['bio']) ?></textarea>
                            <div class="invalid-feedback"><?= e($errors['bio'] ?? '') ?></div>
                        </div>

                        <?php if ($me['role'] === 'worker'): ?>
                            <div class="col-12">
                                <label class="form-label" for="skills">Skills</label>
                                <input class="form-control" id="skills" name="skills" value="<?= e($me['skills']) ?>" placeholder="e.g. Plumbing, Tiling, Pipe fitting">
                                <div class="form-text">Separate skills with commas. Up to 15.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="experience_years">Years of experience</label>
                                <input type="number" min="0" max="60" class="form-control" id="experience_years" name="experience_years" value="<?= e($me['experience_years']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="hourly_rate">Rate per hour (KES)</label>
                                <input type="number" min="0" step="50" class="form-control" id="hourly_rate" name="hourly_rate" value="<?= e($me['hourly_rate'] !== null ? (int) $me['hourly_rate'] : '') ?>">
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_available" name="is_available" value="1" <?= $me['is_available'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_available">I'm available for work now</label>
                                </div>
                            </div>
                        <?php elseif ($me['role'] === 'employer'): ?>
                            <div class="col-12">
                                <label class="form-label" for="company_name">Company / business name <span class="text-body-secondary fw-normal">(optional)</span></label>
                                <input class="form-control" id="company_name" name="company_name" value="<?= e($me['company_name']) ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer bg-white text-end p-3">
                    <button class="btn btn-brand px-4">Save profile</button>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="fw-bold">Verification</h6>
                    <?php if ($me['is_verified']): ?>
                        <p class="small mb-0"><i class="bi bi-patch-check-fill text-brand"></i> Your account is verified.</p>
                    <?php else: ?>
                        <p class="small text-body-secondary">Verified accounts get a badge and appear higher in search. Send us a message with your ID and we'll verify you.</p>
                        <a class="btn btn-sm btn-outline-brand" href="<?= e(url('contact.php', ['subject' => 'Verification request'])) ?>">Request verification</a>
                    <?php endif; ?>
                </div>
            </div>
            <form method="post" class="card">
                <div class="card-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="password">
                    <h6 class="fw-bold mb-3">Change password</h6>
                    <div class="mb-2"><input type="password" class="form-control" name="current_password" placeholder="Current password" autocomplete="current-password" required></div>
                    <div class="mb-2"><input type="password" class="form-control" name="new_password" placeholder="New password" autocomplete="new-password" required></div>
                    <div class="mb-3"><input type="password" class="form-control" name="new_password_confirm" placeholder="Confirm new password" autocomplete="new-password" required></div>
                    <button class="btn btn-outline-secondary w-100">Update password</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
$pageScript = "<script>document.getElementById('avatar').addEventListener('change',function(){document.getElementById('avatarWrap').classList.add('d-none');});</script>";
require __DIR__ . '/includes/footer.php';
