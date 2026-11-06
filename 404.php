<?php
require __DIR__ . '/includes/bootstrap.php';
http_response_code(404);
$pageTitle = 'Page not found';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5 text-center">
    <div class="display-1 fw-bold text-primary">404</div>
    <h2 class="fw-bold">Page not found</h2>
    <p class="text-muted">The page you're looking for doesn't exist or has moved.</p>
    <a href="index.php" class="btn btn-primary">Back to home</a>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
