<?php
require __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect(is_admin() ? 'admin/index.php' : 'dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attempts = $_SESSION['login_attempts'] ?? ['count' => 0, 'since' => time()];
    if (time() - $attempts['since'] > 900) {
        $attempts = ['count' => 0, 'since' => time()];
    }

    if ($attempts['count'] >= 5) {
        $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
    } else {
        $email = strtolower(post('email'));
        $user = db_one('SELECT * FROM users WHERE email = ?', [$email]);
        if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
            if ($user['status'] === 'suspended') {
                $error = 'This account has been suspended. Please contact support.';
            } else {
                if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                    db_exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($_POST['password'], PASSWORD_DEFAULT), $user['id']]);
                }
                login_user($user);
                $next = $_SESSION['after_login'] ?? '';
                unset($_SESSION['after_login']);
                flash('success', 'Welcome back, ' . $user['name'] . '!');
                // Only allow local paths for the post-login redirect
                if ($next && $next[0] === '/' && substr($next, 0, 2) !== '//') {
                    header('Location: ' . $next);
                    exit;
                }
                redirect($user['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php');
            }
        } else {
            $attempts['count']++;
            $error = 'Incorrect email or password.';
        }
    }
    $_SESSION['login_attempts'] = $attempts;
}

$pageTitle = 'Sign in';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 fw-bold mb-1">Welcome back</h1>
                    <p class="text-body-secondary mb-4">New to KaziHub? <a href="<?= e(url('register.php')) ?>">Create an account</a></p>
                    <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
                    <form method="post">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" class="form-control form-control-lg" id="password" name="password" autocomplete="current-password" required>
                        </div>
                        <button class="btn btn-brand btn-lg w-100">Sign in</button>
                    </form>
                    <p class="small text-body-secondary mt-4 mb-0">Forgot your password? <a href="<?= e(url('contact.php', ['subject' => 'Password reset'])) ?>">Contact support</a> and we'll help you get back in.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
