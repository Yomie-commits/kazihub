<?php
require __DIR__ . '/includes/bootstrap.php';

$type = get('type');
if ($type === 'juakali') {
    redirect('juakali.php' . (get('q') !== '' ? '?q=' . urlencode(get('q')) : ''));
}
if (!in_array($type, ['freelance', 'uae'], true)) {
    $type = '';
}
$q = get('q');
$category = get('category');
$location = get('location');
$visa = get('visa') === '1';
$sort = get('sort', 'new');

$where = ["j.status = 'open'", 'j.is_verified = 1', 'u.is_active = 1'];
$params = [];
if ($type) {
    $where[] = 'j.type = ?';
    $params[] = $type;
}
if ($q !== '') {
    $where[] = '(j.title LIKE ? OR j.description LIKE ? OR j.category LIKE ? OR j.employer_name LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($category !== '') {
    $where[] = 'j.category = ?';
    $params[] = $category;
}
if ($location !== '') {
    $where[] = 'j.location LIKE ?';
    $params[] = '%' . $location . '%';
}
if ($visa) {
    $where[] = 'j.visa_support = 1';
}
$whereSql = ' WHERE ' . implode(' AND ', $where);
$order = $sort === 'pay' ? 'COALESCE(j.budget_max, j.budget_min, 0) DESC' : 'j.created_at DESC';

$total = (int)db_value('SELECT COUNT(*) FROM jobs j JOIN users u ON u.id = j.client_id' . $whereSql, $params);
$p = paginate($total, 12);
$jobs = db_all(jobs_select_sql() . $whereSql . " ORDER BY $order LIMIT {$p['limit']} OFFSET {$p['offset']}", $params);

$titles = [
    ''          => ['All jobs', 'Freelance, juakali and UAE opportunities in one place.'],
    'freelance' => ['Freelance jobs', 'Remote and project-based work posted by clients.'],
    'uae'       => ['UAE jobs', 'Every listing is checked by the KaziHub team before it goes live.'],
];
[$heading, $sub] = $titles[$type];
$cats = $type ? categories_for($type) : array_unique(array_merge(service_categories(), uae_categories()));
sort($cats);

$pageTitle = $heading;
$activeNav = $type ?: 'freelance';
require __DIR__ . '/includes/header.php';
?>
<div class="bg-white border-bottom">
    <div class="container py-4">
        <ul class="nav nav-pills mb-3 gap-1">
            <li class="nav-item"><a class="nav-link <?= $type === '' ? 'active' : '' ?>" href="jobs.php">All</a></li>
            <li class="nav-item"><a class="nav-link <?= $type === 'freelance' ? 'active' : '' ?>" href="jobs.php?type=freelance"><i class="bi bi-laptop me-1"></i>Freelance</a></li>
            <li class="nav-item"><a class="nav-link" href="juakali.php"><i class="bi bi-tools me-1"></i>Juakali</a></li>
            <li class="nav-item"><a class="nav-link <?= $type === 'uae' ? 'active' : '' ?>" href="jobs.php?type=uae"><i class="bi bi-globe-americas me-1"></i>UAE</a></li>
        </ul>
        <h1 class="h3 fw-bold mb-1"><?= e($heading) ?></h1>
        <p class="text-muted mb-3"><?= e($sub) ?></p>
        <form class="row g-2" method="get">
            <input type="hidden" name="type" value="<?= e($type) ?>">
            <div class="col-md-4"><input type="text" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Job title or keyword"></div>
            <div class="col-6 col-md-3">
                <select name="category" class="form-select">
                    <option value="">All categories</option>
                    <?php foreach ($cats as $c): ?><option <?= $c === $category ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2"><input type="text" name="location" value="<?= e($location) ?>" class="form-control" placeholder="<?= $type === 'uae' ? 'Dubai, Abu Dhabi…' : 'Location' ?>"></div>
            <div class="col-6 col-md-2">
                <select name="sort" class="form-select">
                    <option value="new">Newest</option>
                    <option value="pay" <?= $sort === 'pay' ? 'selected' : '' ?>>Highest pay</option>
                </select>
            </div>
            <div class="col-6 col-md-1 d-grid"><button class="btn btn-primary"><i class="bi bi-search"></i></button></div>
            <?php if ($type === 'uae'): ?>
                <div class="col-12">
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="visa" value="1" id="visa" <?= $visa ? 'checked' : '' ?> onchange="this.form.submit()">
                        <label class="form-check-label small" for="visa">Only show jobs with visa provided</label></div>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="container py-4">
    <?php if ($type === 'uae'): ?>
        <div class="alert alert-info d-flex gap-3 align-items-start">
            <i class="bi bi-shield-check fs-4"></i>
            <div class="small"><strong>Stay safe:</strong> Genuine employers never ask you to pay for a job offer. Confirm the recruiter's licence with the National Employment Authority (NEA) before you travel, and keep copies of your passport and contract. <a href="about.php#uae-safety">Read our safety tips</a>.</div>
        </div>
    <?php endif; ?>
    <p class="text-muted small"><?= $total ?> job<?= $total === 1 ? '' : 's' ?> found</p>
    <?php if ($jobs): ?>
        <div class="row g-3">
            <?php foreach ($jobs as $job): ?><div class="col-md-6 col-lg-4"><?= job_card($job) ?></div><?php endforeach; ?>
        </div>
        <?= pagination_links($p) ?>
    <?php else: ?>
        <div class="card card-body text-center py-5">
            <i class="bi bi-briefcase fs-1 text-muted"></i>
            <p class="mt-2 mb-0">No jobs match your search yet. <a href="jobs.php?type=<?= e($type) ?>">Clear filters</a></p>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
