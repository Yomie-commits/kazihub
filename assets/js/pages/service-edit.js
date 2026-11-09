import { init, sb, state, $, esc, param, link, options, showAlert, errText, busy, go, flash, uploadImage, wirePreview, setTitle } from '../app.js';
import { SERVICE_CATEGORIES } from '../data.js';

const { user } = await init({ auth: true, roles: ['worker'] });
const id = Number(param('id'));
let service = null;
if (id) {
    ({ data: service } = await sb.from('services').select('*').eq('id', id).eq('user_id', user.id).maybeSingle());
    if (!service) { flash('danger', 'Service not found.'); go('my-services.html'); }
}
const f = service || { title: '', category: '', description: '', price: '', delivery_days: 3, image_url: null };
setTitle(service ? 'Edit service' : 'Create a service');

$('#main').innerHTML = `<div class="container py-4"><div class="row justify-content-center"><div class="col-lg-8">
    <a href="my-services.html" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> My services</a>
    <h1 class="h3 fw-bold mt-2 mb-4">${service ? 'Edit service' : 'Create a service'}</h1>
    <div id="alerts"></div>
    <form id="serviceForm" class="card" novalidate><div class="card-body p-4">
        <div class="mb-3"><label class="form-label" for="title">Service title</label>
            <input class="form-control" id="title" maxlength="150" value="${esc(f.title)}" placeholder="I will…" required>
            <div class="invalid-feedback">Title should be 10–150 characters, e.g. “I will design a modern logo for your business”.</div></div>
        <div class="row g-3 mb-3">
            <div class="col-md-6"><label class="form-label" for="category">Category</label>
                <select class="form-select" id="category" required><option value="">Choose…</option>${options(SERVICE_CATEGORIES, f.category)}</select>
                <div class="invalid-feedback">Choose a category.</div></div>
            <div class="col-6 col-md-3"><label class="form-label" for="price">Price (KES)</label>
                <input type="number" min="100" step="50" class="form-control" id="price" value="${esc(f.price !== '' ? Math.round(f.price) : '')}" required>
                <div class="invalid-feedback">At least KES 100.</div></div>
            <div class="col-6 col-md-3"><label class="form-label" for="delivery">Delivery (days)</label>
                <input type="number" min="1" max="90" class="form-control" id="delivery" value="${esc(f.delivery_days)}" required>
                <div class="invalid-feedback">1–90 days.</div></div>
        </div>
        <div class="mb-3"><label class="form-label" for="description">Description</label>
            <textarea class="form-control" id="description" rows="8" maxlength="5000" required placeholder="What's included, what you need from the client, how revisions work…">${esc(f.description)}</textarea>
            <div class="invalid-feedback">Describe your service in at least 50 characters.</div></div>
        <div class="mb-2"><label class="form-label" for="image">Cover image <span class="text-body-secondary fw-normal">(optional, max 2 MB)</span></label>
            <input type="file" class="form-control" id="image" accept="image/*">
            <img id="imgPreview" src="${esc(f.image_url || '')}" class="img-fluid rounded mt-2 ${f.image_url ? '' : 'd-none'}" style="max-height:200px" alt=""></div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between p-3">
        <a href="my-services.html" class="btn btn-light">Cancel</a>
        <button class="btn btn-brand px-4">${service ? 'Save changes' : 'Publish service'}</button>
    </div></form>
</div></div></div>`;

wirePreview($('#image'), $('#imgPreview'));

$('#serviceForm').addEventListener('submit', async e => {
    e.preventDefault();
    const v = {
        title: $('#title').value.trim(), category: $('#category').value, description: $('#description').value.trim(),
        price: Number($('#price').value), delivery_days: Number($('#delivery').value),
    };
    const checks = {
        title: v.title.length >= 10 && v.title.length <= 150, category: SERVICE_CATEGORIES.includes(v.category),
        description: v.description.length >= 50, price: v.price >= 100, delivery: Number.isInteger(v.delivery_days) && v.delivery_days >= 1 && v.delivery_days <= 90,
    };
    Object.entries(checks).forEach(([k, ok]) => $('#' + k).classList.toggle('is-invalid', !ok));
    if (Object.values(checks).includes(false)) return;

    await busy(e.submitter, async () => {
        try {
            const file = $('#image').files[0];
            if (file) v.image_url = await uploadImage('services', file);
            const res = service
                ? await sb.from('services').update(v).eq('id', service.id).select('id').single()
                : await sb.from('services').insert(v).select('id').single();
            if (res.error) throw res.error;
            flash('success', service ? 'Service updated.' : 'Your service is live! Share the link to get your first order.');
            go(link('service.html', { id: res.data.id }));
        } catch (err) {
            showAlert('danger', errText(err));
        }
    });
});
