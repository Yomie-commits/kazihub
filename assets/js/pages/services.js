import { init, sb, state, $, esc, param, options, serviceCard, ratingsFor, emptyState, pagination, currentPage, cleanSearch, spinner, SERVICE_SELECT } from '../app.js';
import { SERVICE_CATEGORIES } from '../data.js';

await init();
const q = param('q'), category = SERVICE_CATEGORIES.includes(param('category')) ? param('category') : '';
const maxPrice = param('max_price'), sort = param('sort', 'newest');

$('#main').innerHTML = `
<div class="page-head py-4 mb-4"><div class="container">
    <h1 class="h3 fw-bold mb-1">Freelance services</h1>
    <p class="text-body-secondary mb-0">Hire talented Kenyan freelancers for design, tech, writing, marketing and more.</p>
</div></div>
<div class="container"><div class="row g-4">
    <aside class="col-lg-3"><form class="card sidebar-filter" method="get"><div class="card-body">
        <div class="mb-3"><label class="form-label" for="q">Search</label><input class="form-control" id="q" name="q" value="${esc(q)}" placeholder="e.g. logo, website"></div>
        <div class="mb-3"><label class="form-label" for="category">Category</label><select class="form-select" id="category" name="category"><option value="">All categories</option>${options(SERVICE_CATEGORIES, category)}</select></div>
        <div class="mb-3"><label class="form-label" for="max_price">Max budget (KES)</label><input type="number" min="0" step="100" class="form-control" id="max_price" name="max_price" value="${esc(maxPrice)}"></div>
        <div class="mb-3"><label class="form-label" for="sort">Sort by</label><select class="form-select" id="sort" name="sort">${options({ newest: 'Newest', price_low: 'Price: low to high', price_high: 'Price: high to low' }, sort, true)}</select></div>
        <button class="btn btn-brand w-100">Apply filters</button>
        ${q || category || maxPrice ? '<a href="services.html" class="btn btn-link w-100 btn-sm mt-1">Clear filters</a>' : ''}
    </div></form></aside>
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-body-secondary small" id="count"></span>
            ${state.profile?.role === 'worker' ? '<a class="btn btn-sm btn-outline-brand" href="service-edit.html"><i class="bi bi-plus-lg"></i> Offer a service</a>' : ''}
        </div>
        <div id="results">${spinner()}</div>
    </div>
</div></div>`;

const perPage = 12, page = currentPage();
let query = sb.from('services').select(SERVICE_SELECT, { count: 'exact' }).eq('is_active', true);
const term = cleanSearch(q);
if (term) query = query.or(`title.ilike.%${term}%,description.ilike.%${term}%`);
if (category) query = query.eq('category', category);
if (maxPrice && !isNaN(maxPrice)) query = query.lte('price', Number(maxPrice));
const order = { newest: ['created_at', false], price_low: ['price', true], price_high: ['price', false] }[sort] || ['created_at', false];
query = query.order(order[0], { ascending: order[1] }).range((page - 1) * perPage, page * perPage - 1);

const { data, count, error } = await query;
const list = (data || []).filter(s => s.seller);
$('#count').textContent = `${count || 0} service${count === 1 ? '' : 's'} found`;
if (error || !list.length) {
    $('#results').innerHTML = `<div class="card">${emptyState('bi-search', error ? 'Could not load services' : 'No services match your search', error ? 'Please refresh the page.' : 'Try a different keyword or category.')}</div>`;
} else {
    const ratings = await ratingsFor(list.map(s => s.user_id));
    $('#results').innerHTML = `<div class="row g-3">${list.map(s => `<div class="col-sm-6 col-xl-4">${serviceCard(s, ratings[s.user_id])}</div>`).join('')}</div>${pagination(count, perPage, page)}`;
}
