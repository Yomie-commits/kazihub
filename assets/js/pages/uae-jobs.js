import { init, sb, $, esc, param, options, jobCard, emptyState, pagination, currentPage, cleanSearch, spinner, JOB_SELECT } from '../app.js';
import { UAE_CATEGORIES, UAE_EMIRATES } from '../data.js';

const { profile } = await init();
const q = param('q');
const category = UAE_CATEGORIES.includes(param('category')) ? param('category') : '';
const emirate = UAE_EMIRATES.includes(param('emirate')) ? param('emirate') : '';
const visa = param('visa') === '1';

$('#main').innerHTML = `
<div class="page-head py-4 mb-4"><div class="container"><div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div><h1 class="h3 fw-bold mb-1">UAE opportunities</h1><p class="text-body-secondary mb-0">Every listing is reviewed by the KaziHub team before it is published.</p></div>
    ${!profile || profile.role !== 'worker' ? '<a href="job-post.html?type=uae" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Post a UAE job</a>' : ''}
</div></div></div>
<div class="container">
    <div class="warning-box p-3 mb-4 d-flex gap-3"><i class="bi bi-exclamation-triangle-fill text-accent fs-4"></i><div class="small">
        <strong>Stay safe:</strong> genuine employers do not ask you to pay to apply or for a “visa processing fee” before you get a written offer.
        Check that any agency is licensed by the National Employment Authority (NEA). Use the <em>Report</em> button on any listing that seems suspicious.
        <a href="safety.html">More safety tips</a></div></div>
    <div class="row g-4">
        <aside class="col-lg-3"><form class="card sidebar-filter" method="get"><div class="card-body">
            <div class="mb-3"><label class="form-label" for="q">Search</label><input class="form-control" id="q" name="q" value="${esc(q)}" placeholder="e.g. driver, waiter"></div>
            <div class="mb-3"><label class="form-label" for="category">Industry</label><select class="form-select" id="category" name="category"><option value="">All</option>${options(UAE_CATEGORIES, category)}</select></div>
            <div class="mb-3"><label class="form-label" for="emirate">Emirate</label><select class="form-select" id="emirate" name="emirate"><option value="">All emirates</option>${options(UAE_EMIRATES, emirate)}</select></div>
            <div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="visa" name="visa" value="1" ${visa ? 'checked' : ''}><label class="form-check-label" for="visa">Visa sponsored only</label></div>
            <button class="btn btn-brand w-100">Search</button>
            ${q || category || emirate || visa ? '<a href="uae-jobs.html" class="btn btn-link btn-sm w-100 mt-1">Clear filters</a>' : ''}
        </div></form></aside>
        <div class="col-lg-9"><div class="small text-body-secondary mb-3" id="count"></div><div id="results">${spinner()}</div></div>
    </div>
</div>`;

const perPage = 15, page = currentPage();
let query = sb.from('jobs').select(JOB_SELECT, { count: 'exact' }).eq('type', 'uae').eq('status', 'open');
const term = cleanSearch(q);
if (term) query = query.or(`title.ilike.%${term}%,description.ilike.%${term}%,company.ilike.%${term}%`);
if (category) query = query.eq('category', category);
if (emirate) query = query.eq('county', emirate);
if (visa) query = query.eq('visa_sponsored', true);
const { data, count } = await query.order('created_at', { ascending: false }).range((page - 1) * perPage, page * perPage - 1);

$('#count').textContent = `${count || 0} verified job${count === 1 ? '' : 's'}`;
$('#results').innerHTML = data?.length
    ? data.map(j => jobCard(j)).join('') + pagination(count, perPage, page)
    : `<div class="card">${emptyState('bi-airplane', 'No UAE jobs match your search', 'New verified listings are added regularly. Check back soon.')}</div>`;
