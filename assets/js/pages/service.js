import { init, halt, sb, state, $, esc, param, link, money, avatar, stars, place, firstName, excerpt, skillsList, timeAgo, emptyState,
    serviceCard, ratingsFor, showAlert, errText, busy, go, flash, setTitle, SERVICE_SELECT } from '../app.js';
import { categoryIcon } from '../data.js';

const { user } = await init();
const main = $('#main');
const id = Number(param('id'));

const { data: s } = id ? await sb.from('services').select(SERVICE_SELECT).eq('id', id).maybeSingle() : { data: null };
if (!s || !s.seller) {
    main.innerHTML = `<div class="container py-5">${emptyState('bi-search', 'Service not found', 'It may have been removed by the seller.', '<a class="btn btn-brand" href="services.html">Browse services</a>')}</div>`;
    await halt();
}
setTitle(s.title);
const isOwner = user?.id === s.user_id;

const [{ data: seller }, ratings, { data: reviews }, { data: more }] = await Promise.all([
    sb.from('profiles').select('*').eq('id', s.user_id).single(),
    ratingsFor([s.user_id]),
    sb.from('reviews').select('*, reviewer:profiles!reviews_reviewer_id_fkey(name, avatar_url)')
        .eq('reviewee_id', s.user_id).order('created_at', { ascending: false }).limit(10),
    sb.from('services').select(SERVICE_SELECT).eq('user_id', s.user_id).eq('is_active', true).neq('id', s.id).limit(2),
]);
const rating = ratings[s.user_id];
const hue = [...s.category].reduce((h, c) => (h * 31 + c.charCodeAt(0)) | 0, 0);

let orderBox;
if (isOwner) {
    orderBox = `<a href="${link('service-edit.html', { id: s.id })}" class="btn btn-outline-brand w-100"><i class="bi bi-pencil"></i> Edit this service</a>`;
} else if (!user) {
    orderBox = `<a href="${link('login.html', { next: 'service.html?id=' + s.id })}" class="btn btn-brand w-100 btn-lg">Sign in to order</a>`;
} else if (s.is_active) {
    orderBox = `<form id="orderForm">
        <label class="form-label" for="requirements">What do you need?</label>
        <textarea class="form-control mb-3" id="requirements" rows="4" required minlength="10" maxlength="3000" placeholder="Describe your project, deadlines and any files or links the seller needs."></textarea>
        <button class="btn btn-brand w-100 btn-lg">Order for ${money(s.price)}</button></form>
        <a href="${link('messages.html', { with: s.user_id })}" class="btn btn-outline-secondary w-100 mt-2"><i class="bi bi-chat-dots"></i> Ask a question first</a>
        <p class="small text-body-secondary mt-3 mb-0"><i class="bi bi-info-circle"></i> You'll agree payment with the seller once they accept. Never pay outside an agreed order.</p>`;
} else {
    orderBox = '<p class="small text-body-secondary mb-0">This service is paused.</p>';
}

main.innerHTML = `<div class="container py-4">
    <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="services.html">Services</a></li>
        <li class="breadcrumb-item"><a href="${link('services.html', { category: s.category })}">${esc(s.category)}</a></li></ol></nav>
    <div id="alerts"></div>
    <div class="row g-4">
        <div class="col-lg-8">
            <h1 class="h3 fw-bold">${esc(s.title)}</h1>
            <div class="d-flex align-items-center gap-2 mb-3">${avatar(seller, 36)}
                <a href="${link('user.html', { id: seller.id })}" class="fw-semibold text-decoration-none text-body">${esc(seller.name)}</a>
                ${seller.is_verified ? '<i class="bi bi-patch-check-fill text-brand" title="Verified"></i>' : ''}
                ${rating ? `<span class="small">${stars(rating.rating)} (${rating.review_count})</span>` : ''}</div>
            ${!s.is_active ? '<div class="alert alert-warning">This service is paused and hidden from search.</div>' : ''}
            ${s.image_url ? `<img src="${esc(s.image_url)}" class="img-fluid rounded-4 mb-4 w-100 object-fit-cover" style="max-height:420px" alt="">`
                : `<div class="service-thumb-placeholder rounded-4 mb-4" style="background:linear-gradient(135deg,hsl(${Math.abs(hue) % 360},55%,48%),hsl(${(Math.abs(hue) + 40) % 360},60%,38%));font-size:4rem"><i class="bi ${categoryIcon(s.category)}"></i></div>`}
            <div class="card mb-4"><div class="card-body p-4"><h5 class="fw-bold">About this service</h5><p class="mb-0 pre-line">${esc(s.description)}</p></div></div>
            <div class="card mb-4"><div class="card-body p-4"><h5 class="fw-bold mb-3">About the seller</h5><div class="d-flex gap-3">${avatar(seller, 64)}<div>
                <a href="${link('user.html', { id: seller.id })}" class="fw-bold text-body text-decoration-none">${esc(seller.name)}</a>
                <div class="small text-body-secondary mb-2">${esc(place(seller.town, seller.county))}</div>
                <p class="small mb-2">${esc(excerpt(seller.bio, 300))}</p>
                ${skillsList(seller.skills).slice(0, 8).map(k => `<span class="chip">${esc(k)}</span>`).join('')}</div></div></div></div>
            <div class="card"><div class="card-body p-4"><h5 class="fw-bold mb-3">Reviews for ${esc(firstName(seller.name))}</h5>
                ${(reviews || []).length ? reviews.map(r => `<div class="d-flex gap-3 py-3 border-bottom">${avatar(r.reviewer, 40)}<div>
                    <div class="fw-semibold">${esc(r.reviewer?.name)} <span class="small text-body-secondary fw-normal">· ${timeAgo(r.created_at)}</span></div>
                    ${stars(r.rating, false)}${r.comment ? `<p class="mb-0 mt-1 small">${esc(r.comment)}</p>` : ''}</div></div>`).join('')
                  : '<p class="text-body-secondary mb-0">No reviews yet. Be the first to order!</p>'}
            </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card sidebar-filter shadow-sm"><div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-2"><span class="text-body-secondary">Price</span><span class="fs-3 fw-bold">${money(s.price)}</span></div>
                <p class="small text-body-secondary mb-3"><i class="bi bi-clock"></i> Delivered in ${s.delivery_days} day${s.delivery_days > 1 ? 's' : ''}</p>
                ${orderBox}
            </div></div>
            ${(more || []).length ? `<h6 class="fw-bold mt-4 mb-3">More from ${esc(firstName(seller.name))}</h6>${more.map(m => `<div class="mb-3">${serviceCard(m, rating)}</div>`).join('')}` : ''}
        </div>
    </div></div>`;

$('#orderForm')?.addEventListener('submit', async e => {
    e.preventDefault();
    const text = $('#requirements').value.trim();
    if (text.length < 10) return showAlert('danger', 'Please describe what you need (at least 10 characters).');
    await busy(e.submitter, async () => {
        const { data: orderId, error } = await sb.rpc('place_order', { p_service_id: s.id, p_requirements: text });
        if (error) return showAlert('danger', errText(error));
        flash('success', 'Order placed! The seller has been notified and will accept it shortly.');
        go(link('order.html', { id: orderId }));
    });
});
