<?php
require __DIR__ . '/includes/init.php';
$me = current_user();
$errors = [];
$subjects = ['General question', 'Verification request', 'Password reset', 'Report a problem', 'Partnership', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Honeypot against bots
    if (post('website') !== '') {
        redirect('contact.php');
    }
    $name = post('name');
    $email = strtolower(post('email'));
    $subject = post('subject');
    $message = post('message');
    if (mb_strlen($name) < 2) $errors['name'] = 'Enter your name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email.';
    if (!in_array($subject, $subjects, true)) $errors['subject'] = 'Choose a subject.';
    if (mb_strlen($message) < 10) $errors['message'] = 'Your message is too short.';
    if (($_SESSION['contact_sent'] ?? 0) > time() - 60) $errors['message'] = 'Please wait a minute before sending another message.';

    if (!$errors) {
        db_insert('contact_messages', [
            'name' => mb_substr($name, 0, 120), 'email' => mb_substr($email, 0, 190),
            'subject' => $subject, 'message' => mb_substr($message, 0, 5000), 'created_at' => now(),
        ]);
        $_SESSION['contact_sent'] = time();
        flash('success', 'Thanks! We received your message and will get back to you soon.');
        redirect('contact.php');
    }
}

$pageTitle = 'Contact us';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5">
    <div class="row g-5 justify-content-center">
        <div class="col-lg-4">
            <h1 class="h3 fw-bold">Contact us</h1>
            <p class="text-body-secondary">Questions, verification requests or problems with a listing: we're here to help.</p>
            <ul class="list-unstyled small">
                <li class="mb-2"><i class="bi bi-geo-alt text-brand me-2"></i>Nairobi, Kenya</li>
                <li class="mb-2"><i class="bi bi-clock text-brand me-2"></i>Mon–Sat, 8am–6pm EAT</li>
                <li class="mb-2"><i class="bi bi-question-circle text-brand me-2"></i><a href="<?= e(url('help.php')) ?>">Read the help centre</a></li>
            </ul>
        </div>
        <div class="col-lg-6">
            <form method="post" class="card" novalidate>
                <div class="card-body p-4">
                    <?= csrf_field() ?>
                    <div class="d-none"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Name</label>
                            <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= e(old('name', $me['name'] ?? '')) ?>" required>
                            <div class="invalid-feedback"><?= e($errors['name'] ?? '') ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= e(old('email', $me['email'] ?? '')) ?>" required>
                            <div class="invalid-feedback"><?= e($errors['email'] ?? '') ?></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="subject">Subject</label>
                            <select class="form-select <?= isset($errors['subject']) ? 'is-invalid' : '' ?>" id="subject" name="subject">
                                <?= options($subjects, old('subject', get('subject', 'General question'))) ?>
                            </select>
                            <div class="invalid-feedback"><?= e($errors['subject'] ?? '') ?></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="message">Message</label>
                            <textarea class="form-control <?= isset($errors['message']) ? 'is-invalid' : '' ?>" id="message" name="message" rows="6" required><?= e(old('message')) ?></textarea>
                            <div class="invalid-feedback"><?= e($errors['message'] ?? '') ?></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white p-3 text-end"><button class="btn btn-brand px-4">Send message</button></div>
            </form>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
