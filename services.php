<?php
require __DIR__ . '/includes/init.php';

$q = get('q');
$category = in_array(get('category'), SERVICE_CATEGORIES, true) ? get('category') : '';
$maxPrice = get('max_price');
$sort = get('sort', 'top');

$where = ["s.is_active = 1", "u.status = 'active'"];
$params = [];
if ($q !== '') {
    $where[] = '(s.title LIKE ? OR s.description LIKE ? OR u.skills LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if ($category) {
    $where[] = 's.category = ?';
    $params[] = $category;
}
if ($maxPrice !== '' && is_numeric($maxPrice)) {
    $where[] = 's.price <= ?';
    $params[] = (float) $maxPrice;
}
$orderBy = [
    'top'        => 'u.is_verified DESC, seller_rating DESC, s.created_at DESC',
    'newest'     => 's.created_at DESC',
    'price_low'  => 's.price ASC',
    'price_high' => 's.price DESC',
][$sort] ?? 's.created_at DESC';

$whereSql = ' WHERE ' . implode(' AND ', $where);
$perPage = 12;
$total = (int) db_value('SELECT COUNT(*) FROM services s JOIN users u ON u.id = s.user_id' . $whereSql, $params);
$offset = (current_page() - 1) * $perPage;
$services = db_all(SERVICE_SELECT . $whereSql . " ORDER BY $orderBy LIMIT $perPage OFFSET $offset", $params);

$pageTitle = 'Freelance services';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head py-4 mb-4">
    <div class="container">
        <h1 class="h3 fw-bold mb-1">Freelance services</h1>
        <p class="text-body-secondary mb-0">Hire talented Kenyan freelancers for design, tech, writing, marketing and more.</p>
    </div>
</div>

<div class="container">
    <div class="row g-4">
        <aside class="col-lg-3">
            <form class="card sidebar-filter" method="get">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="q">Search</label>
                        <input class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="e.g. logo, website">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="category">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">All categories</option>
                            <?= options(SERVICE_CATEGORIES, $category) ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="max_price">Max budget (KES)</label>
                        <input type="number" min="0" step="100" class="form-control" id="max_price" name="max_price" value="<?= e($maxPrice) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="sort">Sort by</label>
                        <select class="form-select" id="sort" name="sort">
                            <?= options(['top' => 'Top rated', 'newest' => 'Newest', 'price_low' => 'Price: low to high', 'price_high' => 'Price: high to low'], $sort, true) ?>
                        </select>
                    </div>
                    <button class="btn btn-brand w-100">Apply filters</button>
                    <?php if ($q || $category || $maxPrice): ?><a href="<?= e(url('services.php')) ?>" class="btn btn-link w-100 btn-sm mt-1">Clear filters</a><?php endif; ?>
                </div>
            </form>
        </aside>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-body-secondary small"><?= number_format($total) ?> service<?= $total === 1 ? '' : 's' ?> found</span>
                <?php if (current_user() && current_user()['role'] === 'worker'): ?>
                    <a class="btn btn-sm btn-outline-brand" href="<?= e(url('service-edit.php')) ?>"><i class="bi bi-plus-lg"></i> Offer a service</a>
                <?php endif; ?>
            </div>
            <?php if (!$services): ?>
                <div class="card"><?= empty_state('bi-search', 'No services match your search', 'Try a different keyword or category.') ?></div>
            <?php endif; ?>
            <div class="row g-3">
                <?php foreach ($services as $s): ?>
                    <div class="col-sm-6 col-xl-4"><?= service_card($s) ?></div>
                <?php endforeach; ?>
            </div>
            <?= pagination($total, $perPage) ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
