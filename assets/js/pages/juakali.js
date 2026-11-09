import { init, sb, state, $, esc, param, link, options, jobCard, avatar, stars, money, place, skillsList, isOnline, formatKm,
    distanceKm, emptyState, pagination, currentPage, cleanSearch, wireNearMe, spinner, payRange, JOB_SELECT, setTitle } from '../app.js';
import { JUAKALI_CATEGORIES, KENYA_COUNTIES } from '../data.js';

const { profile } = await init();
const view = param('view') === 'workers' ? 'workers' : 'jobs';
setTitle(view === 'jobs' ? 'Juakali jobs' : 'Available workers');
const q = param('q');
const category = JUAKALI_CATEGORIES.includes(param('category')) ? param('category') : '';
const county = KENYA_COUNTIES.includes(param('county')) ? param('county') : '';
let lat = param('lat') !== '' && !isNaN(param('lat')) ? Number(param('lat')) : (profile?.latitude ?? null);
let lng = param('lng') !== '' && !isNaN(param('lng')) ? Number(param('lng')) : (profile?.longitude ?? null);
const haveLocation = lat !== null && lng !== null;
const sort = param('sort') === 'near' && haveLocation ? 'near' : 'newest';
const base = { q, category, county };

$('#main').innerHTML = `
<div class="page-head py-4 mb-4"><div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div><h1 class="h3 fw-bold mb-1">Juakali</h1><p class="text-body-secondary mb-0">Local skilled and casual work, matched by location.</p></div>
        ${!profile || profile.role !== 'worker' ? '<a href="job-post.html?type=juakali" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Post a juakali job</a>' : ''}
    </div>
    <ul class="nav nav-pills mt-3">
        <li class="nav-item"><a class="nav-link ${view === 'jobs' ? 'active bg-brand' : 'text-body'}" href="${link('juakali.html', base)}"><i class="bi bi-briefcase"></i> Jobs</a></li>
        <li class="nav-item"><a class="nav-link ${view === 'workers' ? 'active bg-brand' : 'text-body'}" href="${link('juakali.html', { ...base, view: 'workers' })}"><i class="bi bi-people"></i> Available workers</a></li>
    </ul>
</div></div>
<div class="container"><div class="row g-4">
    <aside class="col-lg-3"><form class="card sidebar-filter" method="get"><div class="card-body">
        ${view === 'workers' ? '<input type="hidden" name="view" value="workers">' : ''}
        ${sort === 'near' ? `<input type="hidden" name="sort" value="near"><input type="hidden" name="lat" value="${esc(lat)}"><input type="hidden" name="lng" value="${esc(lng)}">` : ''}
        <div class="mb-3"><label class="form-label" for="q">Search</label><input class="form-control" id="q" name="q" value="${esc(q)}" placeholder="${view === 'jobs' ? 'e.g. fix sink' : 'e.g. electrician'}"></div>
        <div class="mb-3"><label class="form-label" for="category">${view === 'jobs' ? 'Category' : 'Trade'}</label><select class="form-select" id="category" name="category"><option value="">All</option>${options(JUAKALI_CATEGORIES, category)}</select></div>
        <div class="mb-3"><label class="form-label" for="county">County</label><select class="form-select" id="county" name="county"><option value="">All counties</option>${options(KENYA_COUNTIES, county)}</select></div>
        <button class="btn btn-brand w-100 mb-2">Search</button>
        <a href="#" class="btn btn-outline-secondary w-100" data-near-me><i class="bi bi-geo-alt"></i> Near me</a>
        ${q || category || county || sort === 'near' ? `<a href="${view === 'workers' ? 'juakali.html?view=workers' : 'juakali.html'}" class="btn btn-link btn-sm w-100 mt-1">Clear filters</a>` : ''}
    </div></form></aside>
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3 small text-body-secondary">
            <span id="count"></span><span>${sort === 'near' ? '<i class="bi bi-geo-alt-fill text-brand"></i> Sorted by distance from you' : 'Newest first'}</span>
        </div>
        <div id="map" class="mb-4 border d-none"></div>
        <div id="results">${spinner()}</div>
    </div>
</div></div>`;
wireNearMe();

const term = cleanSearch(q);
let rows;
if (view === 'jobs') {
    let query = sb.from('jobs').select(JOB_SELECT).eq('type', 'juakali').eq('status', 'open');
    if (term) query = query.or(`title.ilike.%${term}%,description.ilike.%${term}%,town.ilike.%${term}%`);
    if (category) query = query.eq('category', category);
    if (county) query = query.eq('county', county);
    const { data } = await query.order('created_at', { ascending: false }).limit(500);
    rows = (data || []).sort((a, b) => (a.urgency === 'urgent' ? 0 : 1) - (b.urgency === 'urgent' ? 0 : 1));
} else {
    let query = sb.from('profiles').select('*').eq('role', 'worker').eq('status', 'active').eq('is_available', true);
    if (term) query = query.or(`name.ilike.%${term}%,skills.ilike.%${term}%,bio.ilike.%${term}%`);
    if (category) query = query.ilike('skills', `%${category.split(' ')[0]}%`);
    if (county) query = query.eq('county', county);
    const { data } = await query.order('is_verified', { ascending: false }).order('last_seen', { ascending: false }).limit(500);
    rows = data || [];
}
rows.forEach(r => { r.km = haveLocation ? distanceKm(lat, lng, r.latitude, r.longitude) : null; });
if (sort === 'near') rows.sort((a, b) => (a.km ?? Infinity) - (b.km ?? Infinity));

const total = rows.length, perPage = 15, page = currentPage();
const pageRows = rows.slice((page - 1) * perPage, page * perPage);
$('#count').textContent = `${total} ${view === 'jobs' ? 'open job' : 'available worker'}${total === 1 ? '' : 's'}`;

if (!pageRows.length) {
    $('#results').innerHTML = `<div class="card">${emptyState(view === 'jobs' ? 'bi-tools' : 'bi-people', view === 'jobs' ? 'No juakali jobs found' : 'No available workers found', 'Try another county or category.')}</div>`;
} else if (view === 'jobs') {
    $('#results').innerHTML = pageRows.map(j => jobCard(j, j.km)).join('') + pagination(total, perPage, page);
} else {
    const { data: ratingRows } = await sb.from('user_ratings').select('*').in('reviewee_id', pageRows.map(w => w.id));
    const ratings = Object.fromEntries((ratingRows || []).map(r => [r.reviewee_id, r]));
    $('#results').innerHTML = `<div class="row g-3">${pageRows.map(w => { const r = ratings[w.id]; return `<div class="col-md-6"><div class="card h-100 card-hover position-relative"><div class="card-body d-flex gap-3">
        <div class="position-relative">${avatar(w, 64)}${isOnline(w.last_seen) ? '<span class="online-dot position-absolute" style="bottom:2px;right:2px" title="Online now"></span>' : ''}</div>
        <div class="min-w-0">
            <a class="stretched-link stretched-title fw-bold" href="${link('user.html', { id: w.id })}">${esc(w.name)}</a>
            ${w.is_verified ? '<i class="bi bi-patch-check-fill text-brand"></i>' : ''}
            <div class="small text-body-secondary"><i class="bi bi-geo-alt"></i> ${esc(place(w.town, w.county) || 'Location not set')}${w.km !== null ? ` · <strong class="text-body">${formatKm(w.km)}</strong>` : ''}</div>
            <div class="small my-1">${r ? `${stars(r.rating)} <span class="text-body-secondary">(${r.review_count})</span>` : '<span class="text-body-secondary">No reviews yet</span>'}${w.hourly_rate ? ` · ${money(w.hourly_rate)}/hr` : ''}</div>
            <div>${skillsList(w.skills).slice(0, 4).map(s => `<span class="chip">${esc(s)}</span>`).join('')}</div>
        </div></div></div></div>`; }).join('')}</div>${pagination(total, perPage, page)}`;
}

// Map of jobs on this page
const markers = view === 'jobs' ? pageRows.filter(j => j.latitude !== null && j.longitude !== null) : [];
if (markers.length && window.L) {
    $('#map').classList.remove('d-none');
    const map = L.map('map').setView(haveLocation ? [lat, lng] : [markers[0].latitude, markers[0].longitude], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
    const bounds = [];
    markers.forEach(j => {
        L.marker([j.latitude, j.longitude]).addTo(map)
            .bindPopup(`<strong>${esc(j.title)}</strong><br>${esc(payRange(j))}<br><a href="${link('job.html', { id: j.id })}">View job</a>`);
        bounds.push([j.latitude, j.longitude]);
    });
    if (haveLocation) {
        L.circleMarker([lat, lng], { radius: 8, color: '#1DBF73', fillOpacity: 0.9 }).addTo(map).bindPopup('You are here');
        bounds.push([lat, lng]);
    }
    if (bounds.length > 1) map.fitBounds(bounds, { padding: [30, 30], maxZoom: 13 });
}
