import { init, sb, $, $$, esc, param, link, money, stars, statusBadge, timeAgo, shortDate, emptyState, ratingPicker, wireRatingPickers,
    showAlert, errText, busy, flash, setTitle } from '../app.js';

const { user } = await init({ auth: true });
const main = $('#main');
const id = Number(param('id'));

const ACTIONS = {
    accept: { who: 'seller', from: ['pending'], label: 'Accept order', cls: 'btn-brand', msg: 'Order accepted. Time to get to work!' },
    decline: { who: 'seller', from: ['pending'], label: 'Decline', cls: 'btn-outline-danger', msg: 'Order declined.', confirm: true },
    deliver: { who: 'seller', from: ['in_progress'], label: 'Mark as delivered', cls: 'btn-brand', msg: 'Marked as delivered. The buyer will review your work.' },
    complete: { who: 'buyer', from: ['delivered'], label: 'Approve & complete', cls: 'btn-brand', msg: 'Order completed. Please leave a review!' },
    revision: { who: 'buyer', from: ['delivered'], label: 'Request a revision', cls: 'btn-outline-secondary', msg: 'Revision requested. Message the seller with details.' },
    cancel: { who: 'buyer', from: ['pending'], label: 'Cancel order', cls: 'btn-outline-danger', msg: 'Order cancelled.', confirm: true },
};

async function render() {
    const { data: o } = await sb.from('orders').select('*, buyer:profiles!orders_buyer_id_fkey(id, name), seller:profiles!orders_seller_id_fkey(id, name)').eq('id', id).maybeSingle();
    if (!o) {
        main.innerHTML = `<div class="container py-5">${emptyState('bi-bag', 'Order not found', '', '<a class="btn btn-brand" href="orders.html">All orders</a>')}</div>`;
        return;
    }
    setTitle('Order #' + o.id);
    const role = o.buyer_id === user.id ? 'buyer' : 'seller';
    const otherId = role === 'buyer' ? o.seller_id : o.buyer_id;
    const { data: review } = await sb.from('reviews').select('*').eq('order_id', o.id).maybeSingle();
    const steps = [['pending', 'Placed'], ['in_progress', 'In progress'], ['delivered', 'Delivered'], ['completed', 'Completed']];
    const cur = steps.findIndex(s => s[0] === o.status);

    main.innerHTML = `<div class="container py-4">
        <a href="orders.html" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> All orders</a>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2 mb-4">
            <h1 class="h3 fw-bold mb-0">Order #${o.id} ${statusBadge(o.status)}</h1>
            <a href="${link('messages.html', { with: otherId })}" class="btn btn-outline-brand"><i class="bi bi-chat-dots"></i> Message ${role === 'buyer' ? 'seller' : 'buyer'}</a>
        </div>
        <div id="alerts"></div>
        <div class="row g-4">
            <div class="col-lg-8">
                ${o.status !== 'cancelled' ? `<div class="card mb-4"><div class="card-body"><div class="d-flex justify-content-between text-center small">
                    ${steps.map(([, label], i) => `<div class="flex-fill"><div class="mx-auto mb-1 rounded-circle d-flex align-items-center justify-content-center ${i <= cur ? 'bg-brand text-white' : 'bg-body-secondary text-body-secondary'}" style="width:34px;height:34px">${i < cur ? '<i class="bi bi-check-lg"></i>' : i + 1}</div>${label}</div>`).join('')}
                </div></div></div>` : ''}
                <div class="card mb-4"><div class="card-body p-4">
                    <h5 class="fw-bold mb-1">${esc(o.title)}</h5>
                    ${o.service_id ? `<a class="small" href="${link('service.html', { id: o.service_id })}">View service</a>` : ''}
                    <h6 class="fw-bold mt-4">Buyer's requirements</h6><p class="mb-0 pre-line">${esc(o.requirements)}</p>
                </div></div>
                ${o.status === 'completed' && role === 'buyer' && !review ? `<form class="card mb-4" id="reviewForm"><div class="card-body p-4">
                    <h5 class="fw-bold">Rate ${esc(o.seller?.name)}</h5><div class="mb-3">${ratingPicker()}</div>
                    <textarea class="form-control mb-3" id="comment" rows="3" maxlength="1000" placeholder="How was your experience?"></textarea>
                    <button class="btn btn-brand">Submit review</button></div></form>` : ''}
                ${review ? `<div class="card mb-4"><div class="card-body p-4"><h6 class="fw-bold">Buyer's review</h6>${stars(review.rating)}${review.comment ? `<p class="mb-0 mt-2">${esc(review.comment)}</p>` : ''}</div></div>` : ''}
            </div>
            <div class="col-lg-4">
                <div class="card mb-3"><div class="card-body"><dl class="row small mb-0">
                    <dt class="col-5">Price</dt><dd class="col-7 fw-bold">${money(o.price)}</dd>
                    <dt class="col-5">Buyer</dt><dd class="col-7"><a href="${link('user.html', { id: o.buyer_id })}">${esc(o.buyer?.name || 'User')}</a></dd>
                    <dt class="col-5">Seller</dt><dd class="col-7"><a href="${link('user.html', { id: o.seller_id })}">${esc(o.seller?.name || 'User')}</a></dd>
                    <dt class="col-5">Placed</dt><dd class="col-7">${shortDate(o.created_at, true)}</dd>
                    <dt class="col-5">Updated</dt><dd class="col-7 mb-0">${timeAgo(o.updated_at)}</dd>
                </dl></div></div>
                <div class="d-grid gap-2">
                    ${Object.entries(ACTIONS).filter(([, a]) => a.who === role && a.from.includes(o.status))
                        .map(([k, a]) => `<button class="btn ${a.cls}" data-action="${k}" ${a.confirm ? 'data-confirm="Are you sure?"' : ''}>${a.label}</button>`).join('')}
                </div>
            </div>
        </div></div>`;

    $$('[data-action]').forEach(b => b.addEventListener('click', () => busy(b, async () => {
        const { error } = await sb.rpc('order_action', { p_order_id: o.id, p_action: b.dataset.action });
        if (error) return showAlert('danger', errText(error));
        flash('success', ACTIONS[b.dataset.action].msg);
        location.reload();
    })));

    wireRatingPickers();
    $('#reviewForm')?.addEventListener('submit', e => {
        e.preventDefault();
        const rating = Number($('#reviewForm input[name=rating]').value);
        if (!rating) return showAlert('danger', 'Please choose a star rating.');
        busy(e.submitter, async () => {
            const { error } = await sb.from('reviews').insert({ reviewee_id: o.seller_id, order_id: o.id, rating, comment: $('#comment').value.trim() || null });
            if (error) return showAlert('danger', errText(error));
            flash('success', 'Thanks for your review!');
            location.reload();
        });
    });
}
render();
