<?php
require __DIR__ . '/includes/init.php';

$service = db_one(SERVICE_SELECT . ' WHERE s.id = ?', [(int) get('id')]);
$me = current_user();
$isOwner = $me && $service && (int) $me['id'] === (int) $service['user_id'];

if (!$service || ((!$service['is_active']) && !$isOwner && !is_admin())) {
    http_response_code(404);
    $pageTitle = 'Service not found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container py-5">' . empty_state('bi-search', 'Service not found', 'It may have been removed by the seller.', '<a class="btn btn-brand" href="' . e(url('services.php')) . '">Browse services</a>') . '</div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Place an order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $me = require_login();
    if ($isOwner) {
        flash('warning', "You can't order your own service.");
    } elseif (!$service['is_active']) {
        flash('warning', 'This service is paused.');
    } else {
        $requirements = post('requirements');
        if (mb_strlen($requirements) < 10) {
            flash('danger', 'Please describe what you need (at least 10 characters).');
            redirect('service.php', ['id' => $service['id']]);
        }
        $orderId = db_insert('orders', [
            'service_id' => $service['id'],
            'buyer_id' => $me['id'],
            'seller_id' => $service['user_id'],
            'title' => $service['title'],
            'price' => $service['price'],
            'requirements' => mb_substr($requirements, 0, 3000),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        db_insert('messages', [
            'sender_id' => $me['id'],
            'receiver_id' => $service['user_id'],
            'body' => "New order #$orderId for \"{$service['title']}\":\n\n" . $requirements,
            'created_at' => now(),
        ]);
        flash('success', 'Order placed! The seller has been notified and will accept it shortly.');
        redirect('orders.php', ['id' => $orderId]);
    }
    redirect('service.php', ['id' => $service['id']]);
}

$seller = db_one('SELECT * FROM users WHERE id = ?', [$service['user_id']]);
$rating = user_rating((int) $seller['id']);
$reviews = db_all('SELECT r.*, u.name, u.avatar FROM reviews r JOIN users u ON u.id = r.reviewer_id
    JOIN orders o ON o.id = r.order_id WHERE o.service_id = ? ORDER BY r.created_at DESC LIMIT 10', [$service['id']]);
$more = db_all(SERVICE_SELECT . ' WHERE s.user_id = ? AND s.id <> ? AND s.is_active = 1 ORDER BY s.created_at DESC LIMIT 2', [$seller['id'], $service['id']]);
$hue = crc32($service['category']) % 360;

$pageTitle = $service['title'];
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= e(url('services.php')) ?>">Services</a></li>
            <li class="breadcrumb-item"><a href="<?= e(url('services.php', ['category' => $service['category']])) ?>"><?= e($service['category']) ?></a></li>
        </ol>
    </nav>
    <div class="row g-4">
        <div class="col-lg-8">
            <h1 class="h3 fw-bold"><?= e($service['title']) ?></h1>
            <div class="d-flex align-items-center gap-2 mb-3">
                <?= avatar($seller, 36) ?>
                <a href="<?= e(url('user.php', ['id' => $seller['id']])) ?>" class="fw-semibold text-decoration-none text-body"><?= e($seller['name']) ?></a>
                <?php if ($seller['is_verified']): ?><i class="bi bi-patch-check-fill text-brand" title="Verified"></i><?php endif; ?>
                <?php if ($rating['count']): ?><span class="small"><?= stars($rating['avg']) ?> (<?= $rating['count'] ?>)</span><?php endif; ?>
            </div>
            <?php if (!$service['is_active']): ?><div class="alert alert-warning">This service is paused and hidden from search.</div><?php endif; ?>
            <?php if ($service['image']): ?>
                <img src="<?= e(url($service['image'])) ?>" class="img-fluid rounded-4 mb-4 w-100 object-fit-cover" style="max-height:420px" alt="">
            <?php else: ?>
                <div class="service-thumb-placeholder rounded-4 mb-4" style="background:linear-gradient(135deg,hsl(<?= $hue ?>,55%,48%),hsl(<?= ($hue + 40) % 360 ?>,60%,38%));font-size:4rem;max-height:360px">
                    <i class="bi <?= category_icon($service['category']) ?>"></i>
                </div>
            <?php endif; ?>
            <div class="card mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold">About this service</h5>
                    <p class="mb-0" style="white-space:pre-line"><?= e($service['description']) ?></p>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">About the seller</h5>
                    <div class="d-flex gap-3">
                        <?= avatar($seller, 64) ?>
                        <div>
                            <a href="<?= e(url('user.php', ['id' => $seller['id']])) ?>" class="fw-bold text-body text-decoration-none"><?= e($seller['name']) ?></a>
                            <div class="small text-body-secondary mb-2"><?= e(trim(($seller['town'] ? $seller['town'] . ', ' : '') . $seller['county'], ', ')) ?></div>
                            <p class="small mb-2"><?= e(excerpt($seller['bio'], 300)) ?></p>
                            <?php foreach (array_slice(skills_list($seller['skills']), 0, 8) as $sk): ?><span class="chip"><?= e($sk) ?></span><?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Reviews for this service</h5>
                    <?php if (!$reviews): ?><p class="text-body-secondary mb-0">No reviews yet. Be the first to order!</p><?php endif; ?>
                    <?php foreach ($reviews as $r): ?>
                        <div class="d-flex gap-3 py-3 border-bottom">
                            <?= avatar($r, 40) ?>
                            <div>
                                <div class="fw-semibold"><?= e($r['name']) ?> <span class="small text-body-secondary fw-normal">· <?= e(time_ago($r['created_at'])) ?></span></div>
                                <?= stars((float) $r['rating'], false) ?>
                                <?php if ($r['comment']): ?><p class="mb-0 mt-1 small"><?= e($r['comment']) ?></p><?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card sidebar-filter shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-body-secondary">Price</span>
                        <span class="fs-3 fw-bold"><?= e(money($service['price'])) ?></span>
                    </div>
                    <p class="small text-body-secondary mb-3"><i class="bi bi-clock"></i> Delivered in <?= (int) $service['delivery_days'] ?> day<?= $service['delivery_days'] > 1 ? 's' : '' ?></p>
                    <?php if ($isOwner): ?>
                        <a href="<?= e(url('service-edit.php', ['id' => $service['id']])) ?>" class="btn btn-outline-brand w-100"><i class="bi bi-pencil"></i> Edit this service</a>
                    <?php elseif (!$me): ?>
                        <a href="<?= e(url('login.php')) ?>" class="btn btn-brand w-100 btn-lg">Sign in to order</a>
                    <?php elseif ($service['is_active']): ?>
                        <form method="post">
                            <?= csrf_field() ?>
                            <label class="form-label" for="requirements">What do you need?</label>
                            <textarea class="form-control mb-3" id="requirements" name="requirements" rows="4" required minlength="10" placeholder="Describe your project, deadlines and any files or links the seller needs."></textarea>
                            <button class="btn btn-brand w-100 btn-lg">Order for <?= e(money($service['price'])) ?></button>
                        </form>
                        <a href="<?= e(url('messages.php', ['with' => $seller['id']])) ?>" class="btn btn-outline-secondary w-100 mt-2"><i class="bi bi-chat-dots"></i> Ask a question first</a>
                        <p class="small text-body-secondary mt-3 mb-0"><i class="bi bi-info-circle"></i> You'll agree payment with the seller once they accept. Never pay outside an agreed order.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($more): ?>
                <h6 class="fw-bold mt-4 mb-3">More from <?= e(explode(' ', $seller['name'])[0]) ?></h6>
                <?php foreach ($more as $m): ?><div class="mb-3"><?= service_card($m) ?></div><?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
