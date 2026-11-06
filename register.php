<?php
require __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
$role = in_array(get('role'), ['worker', 'employer'], true) ? get('role') : 'worker';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = post('name');
    $email = strtolower(post('email'));
    $phone = post('phone');
    $role = post('role');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if (mb_strlen($name) < 2 || mb_strlen($name) > 120) $errors['name'] = 'Enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
    elseif (db_value('SELECT id FROM users WHERE email = ?', [$email])) $errors['email'] = 'An account with this email already exists.';
    if ($phone !== '' && !preg_match('/^\+?[0-9 ]{9,15}$/', $phone)) $errors['phone'] = 'Enter a valid phone number, e.g. 0712 345 678.';
    if (!isset(ROLES[$role])) $errors['role'] = 'Choose how you will use KaziHub.';
    if (strlen($password) < 8) $errors['password'] = 'Use at least 8 characters.';
    elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) $errors['password'] = 'Use at least one letter and one number.';
    if ($password !== $confirm) $errors['password_confirm'] = 'Passwords do not match.';
    if (empty($_POST['terms'])) $errors['terms'] = 'Please accept the terms to continue.';

    if (!$errors) {
        $id = db_insert('users', [
            'name' => $name,
            'email' => $email,
            'phone' => $phone ?: null,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'created_at' => now(),
        ]);
        login_user(['id' => $id]);
        flash('success', 'Welcome to KaziHub, ' . $name . '! Complete your profile so people can find and trust you.');
        redirect('profile.php', ['welcome' => 1]);
    }
}

$pageTitle = 'Create account';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 fw-bold mb-1">Create your account</h1>
                    <p class="text-body-secondary mb-4">Already have one? <a href="<?= e(url('login.php')) ?>">Sign in</a></p>

                    <form method="post" novalidate>
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">I want to…</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="role" id="role-worker" value="worker" <?= old('role', $role) === 'worker' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-brand w-100 py-3" for="role-worker"><i class="bi bi-person-workspace d-block fs-4"></i>Find work</label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="role" id="role-employer" value="employer" <?= old('role', $role) === 'employer' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-brand w-100 py-3" for="role-employer"><i class="bi bi-building d-block fs-4"></i>Hire people</label>
                                </div>
                            </div>
                            <?php if (isset($errors['role'])): ?><div class="text-danger small mt-1"><?= e($errors['role']) ?></div><?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="name">Full name</label>
                            <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= e(old('name')) ?>" autocomplete="name" required>
                            <div class="invalid-feedback"><?= e($errors['name'] ?? '') ?></div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-sm-7">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" required>
                                <div class="invalid-feedback"><?= e($errors['email'] ?? '') ?></div>
                            </div>
                            <div class="col-sm-5">
                                <label class="form-label" for="phone">Phone <span class="text-body-secondary fw-normal">(optional)</span></label>
                                <input type="tel" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" id="phone" name="phone" value="<?= e(old('phone')) ?>" placeholder="07xx xxx xxx" autocomplete="tel">
                                <div class="invalid-feedback"><?= e($errors['phone'] ?? '') ?></div>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label" for="password">Password</label>
                                <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" autocomplete="new-password" required>
                                <div class="invalid-feedback"><?= e($errors['password'] ?? '') ?></div>
                                <div class="form-text">8+ characters, with a letter and a number.</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="password_confirm">Confirm password</label>
                                <input type="password" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
                                <div class="invalid-feedback"><?= e($errors['password_confirm'] ?? '') ?></div>
                            </div>
                        </div>
                        <div class="form-check mb-4">
                            <input class="form-check-input <?= isset($errors['terms']) ? 'is-invalid' : '' ?>" type="checkbox" id="terms" name="terms" value="1" <?= old('terms') ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="terms">I agree to the KaziHub terms and the <a href="<?= e(url('safety.php')) ?>" target="_blank">safety guidelines</a>.</label>
                            <div class="invalid-feedback"><?= e($errors['terms'] ?? '') ?></div>
                        </div>
                        <button class="btn btn-brand w-100 btn-lg">Create account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
