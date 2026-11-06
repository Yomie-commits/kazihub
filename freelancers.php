<?php
require __DIR__ . '/includes/bootstrap.php';

$q = get('q');
$category = get('category');
$maxPrice = get_int('max_price');
$sort = get('sort', 'top');

$where = ["s.status = 'active'", 'u.is_active = 1'];
$params = [];
if ($q !== '') {
    $where[] = '(s.title LIKE ? OR s.description LIKE ? OR u.skills LIKE ? OR u.name LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
if (in_array($category, service_categories(), true)) {
    $where[] = 's.category = ?';
    $params[] = $category;
}
if ($maxPrice > 0) {
    $where[] = 's.price <= ?';
    $params[] = $maxPrice;
}
$whereSql = ' WHERE ' . implode(' AND ', $where);
$order = match ($sort) {
    'new'        => 's.created_at DESC',
    'price_low'  => 's.price ASC',
    'price_high' => 's.price DESC',
    default      => 'rating_avg DESC, rating_total DESC, s.created_at DESC',
};

$total = (int)db_value('SELECT COUNT(*) FROM services s JOIN users u ON u.id = s.user_id' . $whereSql, $params);
$p = paginate($total, 12);
$services = db_all(services_select_sql() . $whereSql . " ORDER BY $order LIMIT {$p['limit']} OFFSET {$p['offset']}", $params);

$pageTitle = 'Hire freelancers';
$activeNav = 'freelancers';
require __DIR__ . '/includes/header.php';
?>
<div class="bg-white border-bottom">
    <div class="container py-4">
        <h1 class="h3 fw-bold mb-1">Hire freelancers</h1>
        <p class="text-muted mb-3">Services from verified Kenyan talent: design, tech, marketing, writing and more.</p>
        <form class="row g-2" method="get">
            <div class="col-md-5">
                <div class="input-group"><span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" value="<?= e($q) ?>" class="form-control" placeholder="What service are you looking for?">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="category" class="form-select">
                    <option value="">All categories</option>
                    <?php foreach (service_categories() as $c): ?><option <?= $c === $category ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="sort" class="form-select">
                    <option value="top" <?= $sort === 'top' ? 'selected' : '' ?>>Top rated</option>
                    <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Newest</option>
                    <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: low to high</option>
                    <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: high to low</option>
                </select>
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary">Search</button></div>
        </form>
    </div>
</div>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted small"><?= $total ?> service<?= $total === 1 ? '' : 's' ?> found</span>
        <?php if (($me = current_user()) && $me['role'] === 'freelancer'): ?>
            <a href="service-form.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Create a service</a>
        <?php endif; ?>
    </div>
    <?php if ($services): ?>
        <div class="row g-3">
            <?php foreach ($services as $s): ?><div class="col-sm-6 col-lg-4 col-xl-3"><?= service_card($s) ?></div><?php endforeach; ?>
        </div>
        <?= pagination_links($p) ?>
    <?php else: ?>
        <div class="card card-body text-center py-5">
            <i class="bi bi-search fs-1 text-muted"></i>
            <p class="mt-2 mb-0">No services match your search. Try different keywords or <a href="freelancers.php">clear the filters</a>.</p>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
