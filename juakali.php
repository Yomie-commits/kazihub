<?php
require __DIR__ . '/includes/init.php';

$me = current_user();
$view = get('view') === 'workers' ? 'workers' : 'jobs';
$q = get('q');
$category = in_array(get('category'), JUAKALI_CATEGORIES, true) ? get('category') : '';
$county = in_array(get('county'), KENYA_COUNTIES, true) ? get('county') : '';
$sort = get('sort') === 'near' ? 'near' : 'newest';

// Where is "me"? URL coordinates first (from the Near me button), then the profile.
$lat = is_numeric(get('lat')) ? (float) get('lat') : ($me['latitude'] ?? null);
$lng = is_numeric(get('lng')) ? (float) get('lng') : ($me['longitude'] ?? null);
$haveLocation = $lat !== null && $lng !== null;
if ($sort === 'near' && !$haveLocation) {
    $sort = 'newest';
}

$perPage = 15;
$params = [];

if ($view === 'jobs') {
    $where = ["j.type = 'juakali'", "j.status = 'open'"];
    if ($q !== '') {
        $where[] = '(j.title LIKE ? OR j.description LIKE ? OR j.town LIKE ?)';
        array_push($params, "%$q%", "%$q%", "%$q%");
    }
    if ($category) { $where[] = 'j.category = ?'; $params[] = $category; }
    if ($county) { $where[] = 'j.county = ?'; $params[] = $county; }
    $rows = db_all(JOB_SELECT . ' WHERE ' . implode(' AND ', $where) . " ORDER BY CASE WHEN j.urgency = 'urgent' THEN 0 ELSE 1 END, j.created_at DESC LIMIT 500", $params);
} else {
    $where = ["u.role = 'worker'", "u.status = 'active'", 'u.is_available = 1'];
    if ($q !== '') {
        $where[] = '(u.name LIKE ? OR u.skills LIKE ? OR u.bio LIKE ?)';
        array_push($params, "%$q%", "%$q%", "%$q%");
    }
    if ($category) { $where[] = 'u.skills LIKE ?'; $params[] = '%' . explode(' ', $category)[0] . '%'; }
    if ($county) { $where[] = 'u.county = ?'; $params[] = $county; }
    $rows = db_all('SELECT u.*, (SELECT AVG(r.rating) FROM reviews r WHERE r.reviewee_id = u.id) AS rating,
            (SELECT COUNT(*) FROM reviews r WHERE r.reviewee_id = u.id) AS review_count
        FROM users u WHERE ' . implode(' AND ', $where) . ' ORDER BY u.is_verified DESC, u.last_seen DESC LIMIT 500', $params);
}

foreach ($rows as &$r) {
    $r['km'] = $haveLocation ? distance_km($lat, $lng, $r['latitude'], $r['longitude']) : null;
}
unset($r);
if ($sort === 'near') {
    usort($rows, fn($a, $b) => ($a['km'] ?? PHP_FLOAT_MAX) <=> ($b['km'] ?? PHP_FLOAT_MAX));
}
$total = count($rows);
$rows = array_slice($rows, (current_page() - 1) * $perPage, $perPage);

// Map markers (jobs only; rounded so exact spots aren't exposed)
$markers = [];
if ($view === 'jobs') {
    foreach ($rows as $j) {
        if ($j['latitude'] !== null && $j['longitude'] !== null) {
            $markers[] = [
                'lat' => round((float) $j['latitude'], 3), 'lng' => round((float) $j['longitude'], 3),
                'title' => $j['title'], 'pay' => pay_range($j), 'url' => url('job.php', ['id' => $j['id']]),
            ];
        }
    }
}

$useMap = $view === 'jobs' && $markers;
$pageTitle = $view === 'jobs' ? 'Juakali jobs' : 'Available workers';
$baseQuery = array_filter(['q' => $q, 'category' => $category, 'county' => $county]);
require __DIR__ . '/includes/header.php';
?>
<div class="page-head py-4 mb-4">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Juakali</h1>
                <p class="text-body-secondary mb-0">Local skilled and casual work, matched by location.</p>
            </div>
            <?php if (!$me || $me['role'] === 'employer'): ?>
                <a href="<?= e(url('job-post.php', ['type' => 'juakali'])) ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Post a juakali job</a>
            <?php endif; ?>
        </div>
        <ul class="nav nav-pills mt-3">
            <li class="nav-item"><a class="nav-link <?= $view === 'jobs' ? 'active bg-brand' : 'text-body' ?>" href="<?= e(url('juakali.php', $baseQuery)) ?>"><i class="bi bi-briefcase"></i> Jobs</a></li>
            <li class="nav-item"><a class="nav-link <?= $view === 'workers' ? 'active bg-brand' : 'text-body' ?>" href="<?= e(url('juakali.php', $baseQuery + ['view' => 'workers'])) ?>"><i class="bi bi-people"></i> Available workers</a></li>
        </ul>
    </div>
</div>

<div class="container">
    <div class="row g-4">
        <aside class="col-lg-3">
            <form class="card sidebar-filter" method="get">
                <div class="card-body">
                    <?php if ($view === 'workers'): ?><input type="hidden" name="view" value="workers"><?php endif; ?>
                    <?php if ($sort === 'near'): ?>
                        <input type="hidden" name="sort" value="near"><input type="hidden" name="lat" value="<?= e($lat) ?>"><input type="hidden" name="lng" value="<?= e($lng) ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label" for="q">Search</label>
                        <input class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="<?= $view === 'jobs' ? 'e.g. fix sink' : 'e.g. electrician' ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="category"><?= $view === 'jobs' ? 'Category' : 'Trade' ?></label>
                        <select class="form-select" id="category" name="category">
                            <option value="">All</option><?= options(JUAKALI_CATEGORIES, $category) ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="county">County</label>
                        <select class="form-select" id="county" name="county">
                            <option value="">All counties</option><?= options(KENYA_COUNTIES, $county) ?>
                        </select>
                    </div>
                    <button class="btn btn-brand w-100 mb-2">Search</button>
                    <a href="#" class="btn btn-outline-secondary w-100" data-near-me><i class="bi bi-geo-alt"></i> Near me</a>
                    <?php if ($baseQuery || $sort === 'near'): ?><a href="<?= e(url('juakali.php', $view === 'workers' ? ['view' => 'workers'] : [])) ?>" class="btn btn-link btn-sm w-100 mt-1">Clear filters</a><?php endif; ?>
                </div>
            </form>
        </aside>

        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3 small text-body-secondary">
                <span><?= number_format($total) ?> <?= $view === 'jobs' ? 'open job' : 'available worker' ?><?= $total === 1 ? '' : 's' ?></span>
                <span><?= $sort === 'near' ? '<i class="bi bi-geo-alt-fill text-brand"></i> Sorted by distance from you' : 'Newest first' ?></span>
            </div>

            <?php if ($useMap): ?><div id="map" class="mb-4 border"></div><?php endif; ?>

            <?php if (!$rows): ?>
                <div class="card"><?= empty_state($view === 'jobs' ? 'bi-tools' : 'bi-people', $view === 'jobs' ? 'No juakali jobs found' : 'No available workers found', 'Try another county or category.') ?></div>
            <?php endif; ?>

            <?php if ($view === 'jobs'): ?>
                <?php foreach ($rows as $j) echo job_card($j, $j['km']); ?>
            <?php else: ?>
                <div class="row g-3">
                <?php foreach ($rows as $w): ?>
                    <div class="col-md-6">
                        <div class="card h-100 card-hover position-relative">
                            <div class="card-body d-flex gap-3">
                                <div class="position-relative">
                                    <?= avatar($w, 64) ?>
                                    <?php if (is_online($w['last_seen'])): ?><span class="online-dot position-absolute" style="bottom:2px;right:2px" title="Online now"></span><?php endif; ?>
                                </div>
                                <div class="min-w-0">
                                    <a class="stretched-link stretched-title fw-bold" href="<?= e(url('user.php', ['id' => $w['id']])) ?>"><?= e($w['name']) ?></a>
                                    <?php if ($w['is_verified']): ?><i class="bi bi-patch-check-fill text-brand"></i><?php endif; ?>
                                    <div class="small text-body-secondary">
                                        <i class="bi bi-geo-alt"></i> <?= e(trim(($w['town'] ? $w['town'] . ', ' : '') . $w['county'], ', ') ?: 'Location not set') ?>
                                        <?php if ($w['km'] !== null): ?> · <strong class="text-body"><?= e(format_km($w['km'])) ?></strong><?php endif; ?>
                                    </div>
                                    <div class="small my-1">
                                        <?= $w['review_count'] ? stars((float) $w['rating']) . ' <span class="text-body-secondary">(' . (int) $w['review_count'] . ')</span>' : '<span class="text-body-secondary">No reviews yet</span>' ?>
                                        <?php if ($w['hourly_rate']): ?> · <?= e(money($w['hourly_rate'])) ?>/hr<?php endif; ?>
                                    </div>
                                    <div><?php foreach (array_slice(skills_list($w['skills']), 0, 4) as $sk): ?><span class="chip"><?= e($sk) ?></span><?php endforeach; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?= pagination($total, $perPage) ?>
        </div>
    </div>
</div>
<?php
if ($useMap) {
    $center = $haveLocation ? [$lat, $lng] : [$markers[0]['lat'], $markers[0]['lng']];
    $pageScript = '<script>
(function(){
  if (!window.L) return;
  var markers = ' . json_encode($markers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';
  var map = L.map("map").setView(' . json_encode($center) . ', 11);
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {maxZoom: 18, attribution: "&copy; OpenStreetMap contributors"}).addTo(map);
  var bounds = [];
  function esc(s){var d=document.createElement("div");d.textContent=s;return d.innerHTML;}
  markers.forEach(function(m){
    L.marker([m.lat, m.lng]).addTo(map).bindPopup("<strong>"+esc(m.title)+"</strong><br>"+esc(m.pay)+"<br><a href=\""+m.url+"\">View job</a>");
    bounds.push([m.lat, m.lng]);
  });
  ' . ($haveLocation ? 'L.circleMarker(' . json_encode([$lat, $lng]) . ', {radius: 8, color: "#1DBF73", fillOpacity: .9}).addTo(map).bindPopup("You are here"); bounds.push(' . json_encode([$lat, $lng]) . ');' : '') . '
  if (bounds.length > 1) map.fitBounds(bounds, {padding: [30, 30], maxZoom: 13});
})();
</script>';
}
require __DIR__ . '/includes/footer.php';
