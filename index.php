<?php
require __DIR__ . '/includes/init.php';

$stats = [
    'workers'  => (int) db_value("SELECT COUNT(*) FROM users WHERE role = 'worker' AND status = 'active'"),
    'jobs'     => (int) db_value("SELECT COUNT(*) FROM jobs WHERE status = 'open'"),
    'services' => (int) db_value('SELECT COUNT(*) FROM services WHERE is_active = 1'),
    'employers'=> (int) db_value("SELECT COUNT(*) FROM users WHERE role = 'employer' AND status = 'active'"),
];
$popular = db_all(SERVICE_SELECT . " WHERE s.is_active = 1 AND u.status = 'active' ORDER BY seller_rating DESC, s.created_at DESC LIMIT 4");
$latestJuakali = db_all(JOB_SELECT . " WHERE j.type = 'juakali' AND j.status = 'open' ORDER BY j.created_at DESC LIMIT 3");
$latestUae = db_all(JOB_SELECT . " WHERE j.type = 'uae' AND j.status = 'open' ORDER BY j.created_at DESC LIMIT 3");

$pageTitle = APP_NAME;
require __DIR__ . '/includes/header.php';
?>

<section class="hero py-5">
    <div class="container py-lg-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="badge bg-white text-success mb-3 px-3 py-2 rounded-pill fw-semibold"><i class="bi bi-geo-alt-fill"></i> Built for Kenya</span>
                <h1 class="display-4 mb-3">Find work. Find workers.<br>Find success.</h1>
                <p class="lead mb-4">Freelance gigs, local juakali jobs near you, and verified opportunities in the UAE, all in one place.</p>
                <form class="hero-search d-flex flex-column flex-sm-row gap-2 mb-3" action="<?= e(url('search.php')) ?>" method="get" role="search">
                    <input class="form-control form-control-lg" type="search" name="q" placeholder="Try “plumber”, “logo design”, “driver”…" aria-label="Search">
                    <select class="form-select form-select-lg w-auto" name="in" aria-label="Search in">
                        <option value="services">Freelance</option>
                        <option value="juakali">Juakali</option>
                        <option value="uae">UAE jobs</option>
                    </select>
                    <button class="btn btn-accent btn-lg px-4"><i class="bi bi-search"></i></button>
                </form>
                <div class="hero-tags d-flex flex-wrap gap-2">
                    <span class="small opacity-75 me-1">Popular:</span>
                    <a href="<?= e(url('juakali.php', ['category' => 'Plumbing'])) ?>">Plumbing</a>
                    <a href="<?= e(url('services.php', ['category' => 'Web Development'])) ?>">Web development</a>
                    <a href="<?= e(url('juakali.php', ['category' => 'Electrical'])) ?>">Electrical</a>
                    <a href="<?= e(url('uae-jobs.php', ['category' => 'Hospitality'])) ?>">Hospitality (UAE)</a>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block">
                <div class="row g-3">
                    <?php foreach ([
                        ['bi-laptop', 'Freelance', 'Sell your digital skills', 'services.php'],
                        ['bi-tools', 'Juakali', 'Local work near you', 'juakali.php'],
                        ['bi-airplane', 'UAE Jobs', 'Verified overseas roles', 'uae-jobs.php'],
                        ['bi-people', 'Hire', 'Find trusted workers', 'juakali.php?view=workers'],
                    ] as $c): ?>
                    <div class="col-6">
                        <a href="<?= e(url($c[3])) ?>" class="card card-hover text-decoration-none h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <i class="bi <?= $c[0] ?> fs-2 text-brand"></i>
                                <h6 class="fw-bold mt-2 mb-0"><?= $c[1] ?></h6>
                                <small class="text-body-secondary"><?= $c[2] ?></small>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-white border-bottom">
    <div class="container py-4">
        <div class="row text-center g-3">
            <div class="col-6 col-md-3"><div class="fs-3 fw-bold text-brand"><?= number_format($stats['workers']) ?></div><div class="small text-body-secondary">Registered workers</div></div>
            <div class="col-6 col-md-3"><div class="fs-3 fw-bold text-brand"><?= number_format($stats['jobs']) ?></div><div class="small text-body-secondary">Open jobs</div></div>
            <div class="col-6 col-md-3"><div class="fs-3 fw-bold text-brand"><?= number_format($stats['services']) ?></div><div class="small text-body-secondary">Freelance services</div></div>
            <div class="col-6 col-md-3"><div class="fs-3 fw-bold text-brand"><?= number_format($stats['employers']) ?></div><div class="small text-body-secondary">Employers hiring</div></div>
        </div>
    </div>
</section>

<section class="container py-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold">Three ways to work</h2>
        <p class="text-body-secondary">Whatever your skill, there's a place for it on KaziHub.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100 p-3">
                <div class="card-body">
                    <div class="feature-icon bg-success-subtle text-success mb-3"><i class="bi bi-laptop"></i></div>
                    <h5 class="fw-bold">Freelance marketplace</h5>
                    <p class="text-body-secondary small">List your services, set your price, get orders from clients and build your reputation with reviews.</p>
                    <ul class="list-unstyled small mb-4">
                        <li><i class="bi bi-check2 text-brand"></i> Service listings &amp; portfolios</li>
                        <li><i class="bi bi-check2 text-brand"></i> Order tracking from start to delivery</li>
                        <li><i class="bi bi-check2 text-brand"></i> Built-in messaging</li>
                    </ul>
                    <a href="<?= e(url('services.php')) ?>" class="btn btn-brand w-100">Browse services</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 p-3">
                <div class="card-body">
                    <div class="feature-icon bg-warning-subtle text-warning-emphasis mb-3"><i class="bi bi-geo-alt"></i></div>
                    <h5 class="fw-bold">Juakali jobs</h5>
                    <p class="text-body-secondary small">Plumbers, electricians, mama fua, fundis: find local jobs and available workers close to you.</p>
                    <ul class="list-unstyled small mb-4">
                        <li><i class="bi bi-check2 text-brand"></i> GPS “near me” matching</li>
                        <li><i class="bi bi-check2 text-brand"></i> See who's available right now</li>
                        <li><i class="bi bi-check2 text-brand"></i> Verified badges &amp; ratings</li>
                    </ul>
                    <a href="<?= e(url('juakali.php')) ?>" class="btn btn-outline-brand w-100">Find local work</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 p-3">
                <div class="card-body">
                    <div class="feature-icon bg-primary-subtle text-primary mb-3"><i class="bi bi-airplane"></i></div>
                    <h5 class="fw-bold">UAE opportunities</h5>
                    <p class="text-body-secondary small">Every UAE listing is checked by our team before it goes live, to keep you safe from fake agents.</p>
                    <ul class="list-unstyled small mb-4">
                        <li><i class="bi bi-check2 text-brand"></i> Admin-approved listings only</li>
                        <li><i class="bi bi-check2 text-brand"></i> Visa &amp; accommodation details upfront</li>
                        <li><i class="bi bi-check2 text-brand"></i> One-click scam reporting</li>
                    </ul>
                    <a href="<?= e(url('uae-jobs.php')) ?>" class="btn btn-outline-primary w-100 fw-semibold">View UAE jobs</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($popular): ?>
<section class="container pb-5">
    <div class="d-flex justify-content-between align-items-end mb-3">
        <h3 class="fw-bold mb-0">Popular services</h3>
        <a href="<?= e(url('services.php')) ?>" class="small fw-semibold">See all <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row g-3">
        <?php foreach ($popular as $s): ?>
            <div class="col-sm-6 col-lg-3"><?= service_card($s) ?></div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($latestJuakali || $latestUae): ?>
<section class="container pb-5">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <h4 class="fw-bold mb-0">Latest juakali jobs</h4>
                <a href="<?= e(url('juakali.php')) ?>" class="small fw-semibold">See all <i class="bi bi-arrow-right"></i></a>
            </div>
            <?php foreach ($latestJuakali as $j) echo job_card($j); ?>
            <?php if (!$latestJuakali) echo empty_state('bi-tools', 'No juakali jobs yet'); ?>
        </div>
        <div class="col-lg-6">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <h4 class="fw-bold mb-0">Latest UAE jobs</h4>
                <a href="<?= e(url('uae-jobs.php')) ?>" class="small fw-semibold">See all <i class="bi bi-arrow-right"></i></a>
            </div>
            <?php foreach ($latestUae as $j) echo job_card($j); ?>
            <?php if (!$latestUae) echo empty_state('bi-airplane', 'No UAE jobs yet'); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="container pb-4">
    <div class="card bg-brand-soft border-0">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <h3 class="fw-bold"><i class="bi bi-shield-check text-brand"></i> Trust &amp; safety first</h3>
                    <p class="mb-0 text-body-secondary">We verify profiles, review every overseas job before it's published, and never ask workers to pay to apply.
                        If anyone asks you for a “processing fee”, report it.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="<?= e(url('safety.php')) ?>" class="btn btn-brand">Read safety tips</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (!is_logged_in()): ?>
<section class="container py-5 text-center">
    <h2 class="fw-bold">Ready to get started?</h2>
    <p class="text-body-secondary mb-4">Create a free account in under a minute.</p>
    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
        <a href="<?= e(url('register.php', ['role' => 'worker'])) ?>" class="btn btn-brand btn-lg">I want to find work</a>
        <a href="<?= e(url('register.php', ['role' => 'employer'])) ?>" class="btn btn-outline-brand btn-lg">I want to hire</a>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
