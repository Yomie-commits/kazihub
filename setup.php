<?php
/**
 * One-time installer: creates the database tables and the first admin account.
 * It locks itself once an admin exists. Delete this file after setup.
 */
require __DIR__ . '/includes/init.php';

function table_exists(string $table): bool
{
    try {
        db()->query("SELECT 1 FROM $table LIMIT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function run_sql_file(string $file): void
{
    $sql = file_get_contents($file);
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    foreach (array_filter(array_map('trim', preg_split('/;\s*\n/', $sql . "\n"))) as $statement) {
        db()->exec($statement);
    }
}

$hasTables = table_exists('users');
$hasAdmin = $hasTables && db_value("SELECT COUNT(*) FROM users WHERE role = 'admin'") > 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$hasAdmin) {
    if (post('action') === 'tables' && !$hasTables) {
        try {
            run_sql_file(__DIR__ . '/database/schema.sql');
            if (post('demo')) {
                run_sql_file(__DIR__ . '/database/demo_data.sql');
            }
            flash('success', 'Database tables created.' . (post('demo') ? ' Demo data added.' : ''));
        } catch (PDOException $e) {
            flash('danger', 'Could not create tables: ' . $e->getMessage() . ' — import database/schema.sql in phpMyAdmin instead.');
        }
        redirect('setup.php');
    }
    if (post('action') === 'admin' && $hasTables) {
        $name = post('name');
        $email = strtolower(post('email'));
        $password = $_POST['password'] ?? '';
        if (mb_strlen($name) < 2) $errors[] = 'Enter a name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
        elseif (db_value('SELECT id FROM users WHERE email = ?', [$email])) $errors[] = 'That email is already registered.';
        if (strlen($password) < 10) $errors[] = 'Admin password must be at least 10 characters.';
        if (!$errors) {
            $id = db_insert('users', [
                'name' => $name, 'email' => $email, 'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'admin', 'is_verified' => 1, 'created_at' => now(),
            ]);
            login_user(['id' => $id]);
            flash('success', 'Admin account created. Setup is complete — please delete setup.php from your server.');
            redirect('admin/index.php');
        }
    }
}

$pageTitle = 'Setup';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 fw-bold mb-4"><i class="bi bi-gear text-brand"></i> KaziHub setup</h1>

                    <?php if ($hasAdmin): ?>
                        <div class="alert alert-success mb-0">
                            Setup is already complete. <strong>Delete <code>setup.php</code> from your server</strong> for security.
                            <div class="mt-2"><a href="<?= e(url('login.php')) ?>" class="btn btn-sm btn-brand">Go to sign in</a></div>
                        </div>
                    <?php elseif (!$hasTables): ?>
                        <p><strong>Step 1 of 2:</strong> create the database tables.</p>
                        <p class="small text-body-secondary">Database connection is working. You can also import <code>database/schema.sql</code> in phpMyAdmin instead.</p>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="tables">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="demo" value="1" id="demo">
                                <label class="form-check-label" for="demo">Also add demo users, jobs and services (good for testing; demo password is <code>password123</code>)</label>
                            </div>
                            <button class="btn btn-brand">Create tables</button>
                        </form>
                    <?php else: ?>
                        <p><strong>Step 2 of 2:</strong> create your admin account.</p>
                        <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="admin">
                            <div class="mb-3"><label class="form-label" for="name">Name</label><input class="form-control" id="name" name="name" value="<?= e(old('name')) ?>" required></div>
                            <div class="mb-3"><label class="form-label" for="email">Email</label><input type="email" class="form-control" id="email" name="email" value="<?= e(old('email')) ?>" required></div>
                            <div class="mb-4"><label class="form-label" for="password">Password</label><input type="password" class="form-control" id="password" name="password" minlength="10" required><div class="form-text">At least 10 characters.</div></div>
                            <button class="btn btn-brand">Create admin</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
