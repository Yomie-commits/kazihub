<?php
require __DIR__ . '/includes/init.php';
$me = require_login();
$uid = (int) $me['id'];

/** Who may move an order from one status to another. */
const ORDER_FLOW = [
    'accept'   => ['role' => 'seller', 'from' => ['pending'], 'to' => 'in_progress', 'msg' => 'Order accepted. Time to get to work!'],
    'decline'  => ['role' => 'seller', 'from' => ['pending'], 'to' => 'cancelled', 'msg' => 'Order declined.'],
    'deliver'  => ['role' => 'seller', 'from' => ['in_progress'], 'to' => 'delivered', 'msg' => 'Marked as delivered. The buyer will review your work.'],
    'complete' => ['role' => 'buyer', 'from' => ['delivered'], 'to' => 'completed', 'msg' => 'Order completed. Please leave a review!'],
    'revision' => ['role' => 'buyer', 'from' => ['delivered'], 'to' => 'in_progress', 'msg' => 'Revision requested. Message the seller with details.'],
    'cancel'   => ['role' => 'buyer', 'from' => ['pending'], 'to' => 'cancelled', 'msg' => 'Order cancelled.'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order = db_one('SELECT * FROM orders WHERE id = ? AND (buyer_id = ? OR seller_id = ?)', [(int) post('order_id'), $uid, $uid]);
    if (!$order) {
        flash('danger', 'Order not found.');
        redirect('orders.php');
    }
    $myRole = (int) $order['buyer_id'] === $uid ? 'buyer' : 'seller';
    $action = post('action');

    if ($action === 'review') {
        $rating = (int) post('rating');
        $already = db_value('SELECT id FROM reviews WHERE order_id = ? AND reviewer_id = ?', [$order['id'], $uid]);
        if ($myRole !== 'buyer' || $order['status'] !== 'completed') {
            flash('danger', 'You can review once the order is completed.');
        } elseif ($already) {
            flash('info', 'You already reviewed this order.');
        } elseif ($rating < 1 || $rating > 5) {
            flash('danger', 'Please choose a star rating.');
        } else {
            db_insert('reviews', [
                'reviewer_id' => $uid,
                'reviewee_id' => $order['seller_id'],
                'order_id' => $order['id'],
                'rating' => $rating,
                'comment' => mb_substr(post('comment'), 0, 1000) ?: null,
                'created_at' => now(),
            ]);
            flash('success', 'Thanks for your review!');
        }
    } elseif (isset(ORDER_FLOW[$action])) {
        $rule = ORDER_FLOW[$action];
        if ($rule['role'] !== $myRole || !in_array($order['status'], $rule['from'], true)) {
            flash('danger', 'That action is not allowed right now.');
        } else {
            db_exec('UPDATE orders SET status = ?, updated_at = ? WHERE id = ?', [$rule['to'], now(), $order['id']]);
            $other = $myRole === 'buyer' ? $order['seller_id'] : $order['buyer_id'];
            db_insert('messages', [
                'sender_id' => $uid,
                'receiver_id' => $other,
                'body' => 'Order #' . $order['id'] . ' ("' . $order['title'] . '") is now: ' . str_replace('_', ' ', $rule['to']) . '.',
                'created_at' => now(),
            ]);
            flash('success', $rule['msg']);
        }
    }
    redirect('orders.php', ['id' => $order['id']]);
}

// ----- Single order view -----
if (get('id')) {
    $order = db_one('SELECT o.*, b.name AS buyer_name, b.avatar AS buyer_avatar, s.name AS seller_name, s.avatar AS seller_avatar
        FROM orders o JOIN users b ON b.id = o.buyer_id JOIN users s ON s.id = o.seller_id
        WHERE o.id = ? AND (o.buyer_id = ? OR o.seller_id = ?)', [(int) get('id'), $uid, $uid]);
    if (!$order) {
        flash('danger', 'Order not found.');
        redirect('orders.php');
    }
    $myRole = (int) $order['buyer_id'] === $uid ? 'buyer' : 'seller';
    $otherId = $myRole === 'buyer' ? $order['seller_id'] : $order['buyer_id'];
    $review = db_one('SELECT * FROM reviews WHERE order_id = ?', [$order['id']]);
    $steps = ['pending' => 'Placed', 'in_progress' => 'In progress', 'delivered' => 'Delivered', 'completed' => 'Completed'];
    $stepKeys = array_keys($steps);
    $currentStep = array_search($order['status'], $stepKeys, true);

    $pageTitle = 'Order #' . $order['id'];
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="container py-4">
        <a href="<?= e(url('orders.php')) ?>" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> All orders</a>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2 mb-4">
            <h1 class="h3 fw-bold mb-0">Order #<?= (int) $order['id'] ?> <?= status_badge($order['status']) ?></h1>
            <a href="<?= e(url('messages.php', ['with' => $otherId])) ?>" class="btn btn-outline-brand"><i class="bi bi-chat-dots"></i> Message <?= $myRole === 'buyer' ? 'seller' : 'buyer' ?></a>
        </div>
        <div class="row g-4">
            <div class="col-lg-8">
                <?php if ($order['status'] !== 'cancelled'): ?>
                <div class="card mb-4"><div class="card-body">
                    <div class="d-flex justify-content-between text-center small">
                        <?php foreach ($stepKeys as $i => $k): ?>
                            <div class="flex-fill">
                                <div class="mx-auto mb-1 rounded-circle d-flex align-items-center justify-content-center <?= $currentStep !== false && $i <= $currentStep ? 'bg-brand text-white' : 'bg-body-secondary text-body-secondary' ?>" style="width:34px;height:34px">
                                    <?= $currentStep !== false && $i < $currentStep ? '<i class="bi bi-check-lg"></i>' : $i + 1 ?>
                                </div>
                                <?= e($steps[$k]) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div></div>
                <?php endif; ?>
                <div class="card mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-1"><?= e($order['title']) ?></h5>
                        <?php if ($order['service_id']): ?><a class="small" href="<?= e(url('service.php', ['id' => $order['service_id']])) ?>">View service</a><?php endif; ?>
                        <h6 class="fw-bold mt-4">Buyer's requirements</h6>
                        <p class="mb-0" style="white-space:pre-line"><?= e($order['requirements']) ?></p>
                    </div>
                </div>

                <?php if ($order['status'] === 'completed' && $myRole === 'buyer' && !$review): ?>
                    <form method="post" class="card mb-4">
                        <div class="card-body p-4">
                            <?= csrf_field() ?>
                            <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                            <input type="hidden" name="action" value="review">
                            <h5 class="fw-bold">Rate <?= e($order['seller_name']) ?></h5>
                            <div class="mb-3"><?= rating_picker() ?></div>
                            <textarea class="form-control mb-3" name="comment" rows="3" maxlength="1000" placeholder="How was your experience?"></textarea>
                            <button class="btn btn-brand">Submit review</button>
                        </div>
                    </form>
                <?php elseif ($review): ?>
                    <div class="card mb-4"><div class="card-body p-4">
                        <h6 class="fw-bold">Buyer's review</h6>
                        <?= stars((float) $review['rating']) ?>
                        <?php if ($review['comment']): ?><p class="mb-0 mt-2"><?= e($review['comment']) ?></p><?php endif; ?>
                    </div></div>
                <?php endif; ?>
            </div>
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <dl class="row small mb-0">
                            <dt class="col-5">Price</dt><dd class="col-7 fw-bold"><?= e(money($order['price'])) ?></dd>
                            <dt class="col-5">Buyer</dt><dd class="col-7"><a href="<?= e(url('user.php', ['id' => $order['buyer_id']])) ?>"><?= e($order['buyer_name']) ?></a></dd>
                            <dt class="col-5">Seller</dt><dd class="col-7"><a href="<?= e(url('user.php', ['id' => $order['seller_id']])) ?>"><?= e($order['seller_name']) ?></a></dd>
                            <dt class="col-5">Placed</dt><dd class="col-7"><?= e(date('j M Y, H:i', strtotime($order['created_at']))) ?></dd>
                            <dt class="col-5">Updated</dt><dd class="col-7 mb-0"><?= e(time_ago($order['updated_at'] ?: $order['created_at'])) ?></dd>
                        </dl>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <?php foreach (ORDER_FLOW as $action => $rule):
                        if ($rule['role'] !== $myRole || !in_array($order['status'], $rule['from'], true)) continue;
                        $labels = [
                            'accept' => ['Accept order', 'btn-brand'], 'decline' => ['Decline', 'btn-outline-danger'],
                            'deliver' => ['Mark as delivered', 'btn-brand'], 'complete' => ['Approve & complete', 'btn-brand'],
                            'revision' => ['Request a revision', 'btn-outline-secondary'], 'cancel' => ['Cancel order', 'btn-outline-danger'],
                        ]; ?>
                        <form method="post" class="d-grid">
                            <?= csrf_field() ?>
                            <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                            <input type="hidden" name="action" value="<?= e($action) ?>">
                            <button class="btn <?= $labels[$action][1] ?>" <?= in_array($action, ['decline', 'cancel'], true) ? 'data-confirm="Are you sure?"' : '' ?>><?= e($labels[$action][0]) ?></button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

// ----- List view -----
$tab = get('tab') === 'selling' ? 'selling' : 'buying';
if ($me['role'] === 'worker' && !get('tab')) {
    $tab = 'selling';
}
$col = $tab === 'selling' ? 'o.seller_id' : 'o.buyer_id';
$otherJoin = $tab === 'selling' ? 'o.buyer_id' : 'o.seller_id';
$orders = db_all("SELECT o.*, u.name AS other_name, u.avatar AS other_avatar FROM orders o JOIN users u ON u.id = $otherJoin
    WHERE $col = ? ORDER BY o.updated_at DESC, o.id DESC", [$uid]);

$pageTitle = 'Orders';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <h1 class="h3 fw-bold mb-3">Orders</h1>
    <ul class="nav nav-tabs mb-0">
        <li class="nav-item"><a class="nav-link <?= $tab === 'buying' ? 'active' : '' ?>" href="?tab=buying">Services I bought</a></li>
        <?php if ($me['role'] === 'worker'): ?>
            <li class="nav-item"><a class="nav-link <?= $tab === 'selling' ? 'active' : '' ?>" href="?tab=selling">Orders I received</a></li>
        <?php endif; ?>
    </ul>
    <div class="card border-top-0 rounded-top-0">
        <?php if (!$orders): ?>
            <?= empty_state('bi-bag', 'No orders yet', $tab === 'selling' ? 'Orders from clients will appear here.' : 'Find a freelancer to get something done.', '<a class="btn btn-brand" href="' . e(url('services.php')) . '">Browse services</a>') ?>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>#</th><th>Service</th><th><?= $tab === 'selling' ? 'Buyer' : 'Seller' ?></th><th>Price</th><th>Status</th><th>Updated</th></tr></thead>
                <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr onclick="location.href='<?= e(url('orders.php', ['id' => $o['id']])) ?>'" style="cursor:pointer">
                        <td class="text-body-secondary"><?= (int) $o['id'] ?></td>
                        <td><a class="fw-semibold text-decoration-none" href="<?= e(url('orders.php', ['id' => $o['id']])) ?>"><?= e($o['title']) ?></a></td>
                        <td><div class="d-flex align-items-center gap-2"><?= avatar(['name' => $o['other_name'], 'avatar' => $o['other_avatar']], 28) ?><?= e($o['other_name']) ?></div></td>
                        <td class="text-nowrap"><?= e(money($o['price'])) ?></td>
                        <td><?= status_badge($o['status']) ?></td>
                        <td class="small text-body-secondary text-nowrap"><?= e(time_ago($o['updated_at'] ?: $o['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
