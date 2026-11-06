<?php
require __DIR__ . '/includes/init.php';

$me = current_user();
$q = get('q');
$category = in_array(get('category'), UAE_CATEGORIES, true) ? get('category') : '';
$emirate = in_array(get('emirate'), UAE_EMIRATES, true) ? get('emirate') : '';
$visa = get('visa') === '1';

$where = ["j.type = 'uae'", "j.status = 'open'"];
$params = [];
if ($q !== '') {
    $where[] = '(j.title LIKE ? OR j.description LIKE ? OR j.company LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%");
}
if ($category) { $where[] = 'j.category = ?'; $params[] = $category; }
if ($emirate) { $where[] = 'j.county = ?'; $params[] = $emirate; }
if ($visa) { $where[] = 'j.visa_sponsored = 1'; }
$whereSql = ' WHERE ' . implode(' AND ', $where);

$perPage = 15;
$total = (int) db_value('SELECT COUNT(*) FROM jobs j' . $whereSql, $params);
$offset = (current_page() - 1) * $perPage;
$jobs = db_all(JOB_SELECT . $whereSql . " ORDER BY j.created_at DESC LIMIT $perPage OFFSET $offset", $params);

$pageTitle = 'UAE jobs';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head py-4 mb-4">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">UAE opportunities</h1>
                <p class="text-body-secondary mb-0">Every listing is reviewed by the KaziHub team before it is published.</p>
            </div>
            <?php if (!$me || $me['role'] === 'employer'): ?>
                <a href="<?= e(url('job-post.php', ['type' => 'uae'])) ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Post a UAE job</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container">
    <div class="warning-box p-3 mb-4 d-flex gap-3">
        <i class="bi bi-exclamation-triangle-fill text-accent fs-4"></i>
        <div class="small">
            <strong>Stay safe:</strong> genuine employers do not ask you to pay to apply or for a “visa processing fee” before you get a written offer.
            Check that any agency is licensed by the National Employment Authority (NEA). Use the <em>Report</em> button on any listing that seems suspicious.
            <a href="<?= e(url('safety.php')) ?>">More safety tips</a>
        </div>
    </div>
    <div class="row g-4">
        <aside class="col-lg-3">
            <form class="card sidebar-filter" method="get">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="q">Search</label>
                        <input class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="e.g. driver, waiter">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="category">Industry</label>
                        <select class="form-select" id="category" name="category"><option value="">All</option><?= options(UAE_CATEGORIES, $category) ?></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="emirate">Emirate</label>
                        <select class="form-select" id="emirate" name="emirate"><option value="">All emirates</option><?= options(UAE_EMIRATES, $emirate) ?></select>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="visa" name="visa" value="1" <?= $visa ? 'checked' : '' ?>>
                        <label class="form-check-label" for="visa">Visa sponsored only</label>
                    </div>
                    <button class="btn btn-brand w-100">Search</button>
                    <?php if ($q || $category || $emirate || $visa): ?><a href="<?= e(url('uae-jobs.php')) ?>" class="btn btn-link btn-sm w-100 mt-1">Clear filters</a><?php endif; ?>
                </div>
            </form>
        </aside>
        <div class="col-lg-9">
            <div class="small text-body-secondary mb-3"><?= number_format($total) ?> verified job<?= $total === 1 ? '' : 's' ?></div>
            <?php if (!$jobs): ?>
                <div class="card"><?= empty_state('bi-airplane', 'No UAE jobs match your search', 'New verified listings are added regularly. Check back soon.') ?></div>
            <?php endif; ?>
            <?php foreach ($jobs as $j) echo job_card($j); ?>
            <?= pagination($total, $perPage) ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
