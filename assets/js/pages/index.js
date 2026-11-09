import { init, sb, state, $, esc, link, serviceCard, jobCard, ratingsFor, emptyState, SERVICE_SELECT, JOB_SELECT } from '../app.js';

await init();
const main = $('#main');

main.innerHTML = `
<section class="hero py-5"><div class="container py-lg-5"><div class="row align-items-center g-4 g-lg-5">
    <div class="col-lg-7">
        <span class="badge bg-white text-success mb-3 px-3 py-2 rounded-pill fw-semibold"><i class="bi bi-geo-alt-fill"></i> Built for Kenya</span>
        <h1 class="display-4 mb-3">Find work. Find workers.<br>Find success.</h1>
        <p class="lead mb-4">Freelance gigs, local juakali jobs near you, and verified opportunities in the UAE, all in one place.</p>
        <form class="hero-search d-flex flex-column flex-sm-row gap-2 mb-3" id="heroSearch" role="search">
            <input class="form-control form-control-lg" type="search" name="q" placeholder="Try “plumber”, “logo design”, “driver”…" aria-label="Search">
            <select class="form-select form-select-lg w-auto" name="in" aria-label="Search in">
                <option value="services.html">Freelance</option><option value="juakali.html">Juakali</option><option value="uae-jobs.html">UAE jobs</option>
            </select>
            <button class="btn btn-accent btn-lg px-4" aria-label="Search"><i class="bi bi-search"></i></button>
        </form>
        <div class="hero-tags d-flex flex-wrap gap-2">
            <span class="small opacity-75 me-1">Popular:</span>
            <a href="juakali.html?category=Plumbing">Plumbing</a>
            <a href="services.html?category=Web+Development">Web development</a>
            <a href="juakali.html?category=Electrical">Electrical</a>
            <a href="uae-jobs.html?category=Hospitality">Hospitality (UAE)</a>
        </div>
    </div>
    <div class="col-lg-5 d-none d-lg-block"><div class="row g-3">
        ${[['bi-laptop', 'Freelance', 'Sell your digital skills', 'services.html'], ['bi-tools', 'Juakali', 'Local work near you', 'juakali.html'],
           ['bi-airplane', 'UAE Jobs', 'Verified overseas roles', 'uae-jobs.html'], ['bi-people', 'Hire', 'Find trusted workers', 'juakali.html?view=workers']]
          .map(c => `<div class="col-6"><a href="${c[3]}" class="card card-hover text-decoration-none h-100 border-0 shadow-sm"><div class="card-body">
            <i class="bi ${c[0]} fs-2 text-brand"></i><h6 class="fw-bold mt-2 mb-0">${c[1]}</h6><small class="text-body-secondary">${c[2]}</small></div></a></div>`).join('')}
    </div></div>
</div></div></section>

<section class="bg-white border-bottom"><div class="container py-4"><div class="row text-center g-3" id="stats">
    ${['Registered workers', 'Open jobs', 'Freelance services', 'Employers hiring'].map(l => `<div class="col-6 col-md-3"><div class="fs-3 fw-bold text-brand">–</div><div class="small text-body-secondary">${l}</div></div>`).join('')}
</div></div></section>

<section class="container py-5">
    <div class="text-center mb-5"><h2 class="fw-bold">Three ways to work</h2><p class="text-body-secondary">Whatever your skill, there's a place for it on KaziHub.</p></div>
    <div class="row g-4">
        <div class="col-md-4"><div class="card h-100 p-3"><div class="card-body">
            <div class="feature-icon bg-success-subtle text-success mb-3"><i class="bi bi-laptop"></i></div>
            <h5 class="fw-bold">Freelance marketplace</h5>
            <p class="text-body-secondary small">List your services, set your price, get orders from clients and build your reputation with reviews.</p>
            <ul class="list-unstyled small mb-4"><li><i class="bi bi-check2 text-brand"></i> Service listings</li><li><i class="bi bi-check2 text-brand"></i> Order tracking from start to delivery</li><li><i class="bi bi-check2 text-brand"></i> Built-in live chat</li></ul>
            <a href="services.html" class="btn btn-brand w-100">Browse services</a></div></div></div>
        <div class="col-md-4"><div class="card h-100 p-3"><div class="card-body">
            <div class="feature-icon bg-warning-subtle text-warning-emphasis mb-3"><i class="bi bi-geo-alt"></i></div>
            <h5 class="fw-bold">Juakali jobs</h5>
            <p class="text-body-secondary small">Plumbers, electricians, mama fua, fundis: find local jobs and available workers close to you.</p>
            <ul class="list-unstyled small mb-4"><li><i class="bi bi-check2 text-brand"></i> GPS “near me” matching</li><li><i class="bi bi-check2 text-brand"></i> See who's available right now</li><li><i class="bi bi-check2 text-brand"></i> Verified badges &amp; ratings</li></ul>
            <a href="juakali.html" class="btn btn-outline-brand w-100">Find local work</a></div></div></div>
        <div class="col-md-4"><div class="card h-100 p-3"><div class="card-body">
            <div class="feature-icon bg-primary-subtle text-primary mb-3"><i class="bi bi-airplane"></i></div>
            <h5 class="fw-bold">UAE opportunities</h5>
            <p class="text-body-secondary small">Every UAE listing is checked by our team before it goes live, to keep you safe from fake agents.</p>
            <ul class="list-unstyled small mb-4"><li><i class="bi bi-check2 text-brand"></i> Admin-approved listings only</li><li><i class="bi bi-check2 text-brand"></i> Visa &amp; accommodation details upfront</li><li><i class="bi bi-check2 text-brand"></i> One-click scam reporting</li></ul>
            <a href="uae-jobs.html" class="btn btn-outline-primary w-100 fw-semibold">View UAE jobs</a></div></div></div>
    </div>
</section>

<section class="container pb-5" id="popularWrap"></section>
<section class="container pb-5" id="latestWrap"></section>

<section class="container pb-4"><div class="card bg-brand-soft border-0"><div class="card-body p-4 p-lg-5"><div class="row align-items-center g-4">
    <div class="col-lg-8"><h3 class="fw-bold"><i class="bi bi-shield-check text-brand"></i> Trust &amp; safety first</h3>
        <p class="mb-0 text-body-secondary">We verify profiles, review every overseas job before it's published, and never ask workers to pay to apply. If anyone asks you for a “processing fee”, report it.</p></div>
    <div class="col-lg-4 text-lg-end"><a href="safety.html" class="btn btn-brand">Read safety tips</a></div>
</div></div></div></section>

${state.user ? '' : `<section class="container py-5 text-center"><h2 class="fw-bold">Ready to get started?</h2><p class="text-body-secondary mb-4">Create a free account in under a minute.</p>
    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
        <a href="register.html?role=worker" class="btn btn-brand btn-lg">I want to find work</a>
        <a href="register.html?role=employer" class="btn btn-outline-brand btn-lg">I want to hire</a></div></section>`}`;

$('#heroSearch').addEventListener('submit', e => {
    e.preventDefault();
    const f = new FormData(e.target);
    location.href = link(f.get('in'), { q: f.get('q').trim() });
});

const [stats, services, juakali, uae] = await Promise.all([
    sb.rpc('public_stats'),
    sb.from('services').select(SERVICE_SELECT).eq('is_active', true).order('created_at', { ascending: false }).limit(8),
    sb.from('jobs').select(JOB_SELECT).eq('type', 'juakali').eq('status', 'open').order('created_at', { ascending: false }).limit(3),
    sb.from('jobs').select(JOB_SELECT).eq('type', 'uae').eq('status', 'open').order('created_at', { ascending: false }).limit(3),
]);

if (stats.data) {
    const s = stats.data;
    $('#stats').innerHTML = [[s.workers, 'Registered workers'], [s.jobs, 'Open jobs'], [s.services, 'Freelance services'], [s.employers, 'Employers hiring']]
        .map(([v, l]) => `<div class="col-6 col-md-3"><div class="fs-3 fw-bold text-brand">${Number(v).toLocaleString()}</div><div class="small text-body-secondary">${l}</div></div>`).join('');
}

const list = (services.data || []).filter(s => s.seller);
if (list.length) {
    const ratings = await ratingsFor(list.map(s => s.user_id));
    // Best-rated first, then newest
    list.sort((a, b) => (ratings[b.user_id]?.rating || 0) - (ratings[a.user_id]?.rating || 0));
    $('#popularWrap').innerHTML = `<div class="d-flex justify-content-between align-items-end mb-3"><h3 class="fw-bold mb-0">Popular services</h3>
        <a href="services.html" class="small fw-semibold">See all <i class="bi bi-arrow-right"></i></a></div>
        <div class="row g-3">${list.slice(0, 4).map(s => `<div class="col-sm-6 col-lg-3">${serviceCard(s, ratings[s.user_id])}</div>`).join('')}</div>`;
}

const jk = juakali.data || [], ua = uae.data || [];
if (jk.length || ua.length) {
    $('#latestWrap').innerHTML = `<div class="row g-4">
        <div class="col-lg-6"><div class="d-flex justify-content-between align-items-end mb-3"><h4 class="fw-bold mb-0">Latest juakali jobs</h4><a href="juakali.html" class="small fw-semibold">See all <i class="bi bi-arrow-right"></i></a></div>
            ${jk.map(j => jobCard(j)).join('') || emptyState('bi-tools', 'No juakali jobs yet')}</div>
        <div class="col-lg-6"><div class="d-flex justify-content-between align-items-end mb-3"><h4 class="fw-bold mb-0">Latest UAE jobs</h4><a href="uae-jobs.html" class="small fw-semibold">See all <i class="bi bi-arrow-right"></i></a></div>
            ${ua.map(j => jobCard(j)).join('') || emptyState('bi-airplane', 'No UAE jobs yet')}</div></div>`;
}
