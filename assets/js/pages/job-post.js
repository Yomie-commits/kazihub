import { init, sb, $, $$, esc, param, link, options, showAlert, errText, busy, go, flash, wireGeoButtons, setTitle } from '../app.js';
import { JUAKALI_CATEGORIES, UAE_CATEGORIES, KENYA_COUNTIES, UAE_EMIRATES } from '../data.js';

const { user, profile } = await init({ auth: true, roles: ['employer', 'admin'] });
const id = Number(param('id'));
let job = null;
if (id) {
    let q = sb.from('jobs').select('*').eq('id', id);
    if (profile.role !== 'admin') q = q.eq('employer_id', user.id);
    ({ data: job } = await q.maybeSingle());
    if (!job) { flash('danger', 'Job not found.'); go('my-jobs.html'); }
}
const f = job || {
    type: param('type') === 'uae' ? 'uae' : 'juakali', title: '', category: '', description: '', county: profile.county || '', town: '',
    latitude: '', longitude: '', budget_min: '', budget_max: '', pay_period: 'fixed', urgency: 'normal', company: profile.company_name || '',
    visa_sponsored: false, accommodation: false, positions: 1, deadline: '',
};
setTitle(job ? 'Edit job' : 'Post a job');
const num = v => (v === null || v === undefined || v === '') ? '' : Math.round(v);
const today = new Date().toISOString().slice(0, 10);

$('#main').innerHTML = `<div class="container py-4"><div class="row justify-content-center"><div class="col-lg-8">
    <a href="my-jobs.html" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> My job posts</a>
    <h1 class="h3 fw-bold mt-2 mb-4">${job ? 'Edit job' : 'Post a job'}</h1>
    <div id="alerts"></div>
    <form id="jobForm" class="card" novalidate><div class="card-body p-4">
        <label class="form-label">Job type</label>
        <div class="row g-2 mb-4">
            <div class="col-6"><input type="radio" class="btn-check" name="type" id="type-juakali" value="juakali" ${f.type === 'juakali' ? 'checked' : ''} ${job ? 'disabled' : ''}>
                <label class="btn btn-outline-brand w-100 py-3" for="type-juakali"><i class="bi bi-tools d-block fs-4"></i>Juakali (Kenya)</label></div>
            <div class="col-6"><input type="radio" class="btn-check" name="type" id="type-uae" value="uae" ${f.type === 'uae' ? 'checked' : ''} ${job ? 'disabled' : ''}>
                <label class="btn btn-outline-brand w-100 py-3" for="type-uae"><i class="bi bi-airplane d-block fs-4"></i>UAE job</label></div>
        </div>
        <div class="alert alert-info small" data-for-type="uae"><i class="bi bi-shield-check"></i> UAE jobs are reviewed by our team before they appear, and again after every edit. Include the real employer or your NEA-licensed agency name. Listings that ask workers for fees will be removed.</div>

        <div class="mb-3"><label class="form-label" for="title">Job title</label>
            <input class="form-control" id="title" maxlength="150" value="${esc(f.title)}" placeholder="e.g. Fix leaking kitchen sink" required>
            <div class="invalid-feedback">Title should be 5–150 characters.</div></div>
        <div class="row g-3 mb-3">
            <div class="col-md-6" data-for-type="juakali"><label class="form-label" for="category_juakali">Category</label>
                <select class="form-select" id="category_juakali"><option value="">Choose…</option>${options(JUAKALI_CATEGORIES, f.category)}</select><div class="invalid-feedback">Choose a category.</div></div>
            <div class="col-md-6" data-for-type="uae"><label class="form-label" for="category_uae">Industry</label>
                <select class="form-select" id="category_uae"><option value="">Choose…</option>${options(UAE_CATEGORIES, f.category)}</select><div class="invalid-feedback">Choose an industry.</div></div>
            <div class="col-md-6" data-for-type="uae"><label class="form-label" for="company">Hiring company / agency</label>
                <input class="form-control" id="company" maxlength="150" value="${esc(f.company)}"><div class="invalid-feedback">Enter the hiring company or licensed agency name.</div></div>
            <div class="col-md-6" data-for-type="juakali"><label class="form-label" for="urgency">Urgency</label>
                <select class="form-select" id="urgency">${options({ normal: 'Normal', urgent: 'Urgent (today / tomorrow)' }, f.urgency, true)}</select></div>
        </div>
        <div class="mb-3"><label class="form-label" for="description">Description</label>
            <textarea class="form-control" id="description" rows="6" maxlength="5000" required placeholder="What needs to be done, skills or tools required, working hours…">${esc(f.description)}</textarea>
            <div class="invalid-feedback">Describe the job in at least 30 characters.</div></div>

        <h6 class="fw-bold mt-4">Location</h6>
        <div class="row g-3 mb-3">
            <div class="col-md-6" data-for-type="juakali"><label class="form-label" for="county">County</label>
                <select class="form-select" id="county"><option value="">Choose…</option>${options(KENYA_COUNTIES, f.type === 'juakali' ? f.county : '')}</select><div class="invalid-feedback">Choose a county.</div></div>
            <div class="col-md-6" data-for-type="uae"><label class="form-label" for="emirate">Emirate</label>
                <select class="form-select" id="emirate"><option value="">Choose…</option>${options(UAE_EMIRATES, f.type === 'uae' ? f.county : '')}</select><div class="invalid-feedback">Choose an emirate.</div></div>
            <div class="col-md-6"><label class="form-label" for="town">Town / area</label><input class="form-control" id="town" maxlength="100" value="${esc(f.town)}" placeholder="e.g. Westlands"></div>
            <div class="col-12" data-for-type="juakali">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-geo-fill data-lat="#latitude" data-lng="#longitude" data-status="#geoStatus"><i class="bi bi-crosshair"></i> Use my current location (job site)</button>
                <span id="geoStatus" class="small text-body-secondary ms-2">${f.latitude ? '<i class="bi bi-check-circle-fill text-success"></i> Location set' : 'Helps nearby workers find this job'}</span>
                <input type="hidden" id="latitude" value="${esc(f.latitude ?? '')}"><input type="hidden" id="longitude" value="${esc(f.longitude ?? '')}">
            </div>
        </div>

        <h6 class="fw-bold mt-4">Pay</h6>
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-4"><label class="form-label" for="budget_min">Minimum <span class="text-body-secondary fw-normal" id="cur">(KES)</span></label>
                <input type="number" min="0" class="form-control" id="budget_min" value="${num(f.budget_min)}"></div>
            <div class="col-6 col-md-4"><label class="form-label" for="budget_max">Maximum</label>
                <input type="number" min="0" class="form-control" id="budget_max" value="${num(f.budget_max)}"><div class="invalid-feedback">Maximum must be more than minimum.</div></div>
            <div class="col-md-4"><label class="form-label" for="pay_period">Paid</label>
                <select class="form-select" id="pay_period">${options({ fixed: 'Per job (fixed)', daily: 'Per day', monthly: 'Per month' }, f.pay_period, true)}</select></div>
        </div>
        <div class="row g-3">
            <div class="col-6 col-md-4"><label class="form-label" for="positions">Workers needed</label>
                <input type="number" min="1" max="500" class="form-control" id="positions" value="${esc(f.positions)}"><div class="invalid-feedback">Enter 1–500.</div></div>
            <div class="col-6 col-md-4"><label class="form-label" for="deadline">Apply by <span class="text-body-secondary fw-normal">(optional)</span></label>
                <input type="date" class="form-control" id="deadline" value="${esc(f.deadline || '')}" min="${today}"><div class="invalid-feedback">Deadline must be today or later.</div></div>
            <div class="col-md-4 d-flex flex-column justify-content-end" data-for-type="uae">
                <div class="form-check"><input class="form-check-input" type="checkbox" id="visa_sponsored" ${f.visa_sponsored ? 'checked' : ''}><label class="form-check-label" for="visa_sponsored">Visa sponsored</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" id="accommodation" ${f.accommodation ? 'checked' : ''}><label class="form-check-label" for="accommodation">Accommodation provided</label></div>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between p-3">
        <a href="my-jobs.html" class="btn btn-light">Cancel</a>
        <button class="btn btn-brand px-4">${job ? 'Save changes' : 'Post job'}</button>
    </div></form>
</div></div></div>`;

const currentType = () => job ? job.type : $('input[name=type]:checked').value;
function toggleType() {
    const t = currentType();
    $$('[data-for-type]').forEach(el => el.classList.toggle('d-none', el.dataset.forType !== t));
    $('#cur').textContent = t === 'uae' ? '(AED)' : '(KES)';
}
$$('input[name=type]').forEach(r => r.addEventListener('change', toggleType));
toggleType();
wireGeoButtons();

$('#jobForm').addEventListener('submit', async e => {
    e.preventDefault();
    const type = currentType();
    const val = s => $(s).value.trim();
    const numOrNull = s => val(s) === '' ? null : Number(val(s));
    const d = {
        title: val('#title'), category: type === 'uae' ? val('#category_uae') : val('#category_juakali'), description: val('#description'),
        county: type === 'uae' ? val('#emirate') : val('#county'), town: val('#town') || null,
        budget_min: numOrNull('#budget_min'), budget_max: numOrNull('#budget_max'), pay_period: val('#pay_period'),
        positions: Number(val('#positions')), deadline: val('#deadline') || null,
    };
    if (type === 'juakali') {
        d.urgency = val('#urgency');
        d.latitude = numOrNull('#latitude');
        d.longitude = numOrNull('#longitude');
    } else {
        d.company = val('#company');
        d.visa_sponsored = $('#visa_sponsored').checked;
        d.accommodation = $('#accommodation').checked;
    }
    const catSel = type === 'uae' ? '#category_uae' : '#category_juakali';
    const placeSel = type === 'uae' ? '#emirate' : '#county';
    const checks = [
        ['#title', d.title.length >= 5 && d.title.length <= 150],
        [catSel, (type === 'uae' ? UAE_CATEGORIES : JUAKALI_CATEGORIES).includes(d.category)],
        ['#description', d.description.length >= 30],
        [placeSel, (type === 'uae' ? UAE_EMIRATES : KENYA_COUNTIES).includes(d.county)],
        ['#budget_max', d.budget_min === null || d.budget_max === null || d.budget_max >= d.budget_min],
        ['#positions', Number.isInteger(d.positions) && d.positions >= 1 && d.positions <= 500],
        ['#deadline', !d.deadline || d.deadline >= today],
        ['#company', type !== 'uae' || (d.company || '').length >= 2],
    ];
    checks.forEach(([sel, ok]) => $(sel).classList.toggle('is-invalid', !ok));
    if (checks.some(([, ok]) => !ok)) return;

    await busy(e.submitter, async () => {
        const res = job
            ? await sb.from('jobs').update(d).eq('id', job.id).select('id, status').single()
            : await sb.from('jobs').insert({ ...d, type }).select('id, status').single();
        if (res.error) return showAlert('danger', errText(res.error));
        const pending = res.data.status === 'pending';
        flash('success', job
            ? (pending ? 'Job updated and sent for review. It will be visible again once approved.' : 'Job updated.')
            : (pending ? 'Thanks! Your UAE job has been sent to our team for review. We usually approve listings within 24 hours.' : 'Your job is live. Workers nearby can now apply.'));
        go(link('job.html', { id: res.data.id }));
    });
});
