import { init, sb, $, esc, param, link, money, avatar, statusBadge, timeAgo, emptyState, spinner } from '../app.js';

const { user, profile } = await init({ auth: true });
let tab = param('tab') || (profile.role === 'worker' ? 'selling' : 'buying');
if (profile.role !== 'worker') tab = 'buying';

$('#main').innerHTML = `<div class="container py-4">
    <h1 class="h3 fw-bold mb-3">Orders</h1>
    <ul class="nav nav-tabs">
        <li class="nav-item"><a class="nav-link ${tab === 'buying' ? 'active' : ''}" href="orders.html?tab=buying">Services I bought</a></li>
        ${profile.role === 'worker' ? `<li class="nav-item"><a class="nav-link ${tab === 'selling' ? 'active' : ''}" href="orders.html?tab=selling">Orders I received</a></li>` : ''}
    </ul>
    <div class="card border-top-0 rounded-top-0" id="list">${spinner()}</div></div>`;

const mine = tab === 'selling' ? 'seller_id' : 'buyer_id';
const other = tab === 'selling' ? 'buyer:profiles!orders_buyer_id_fkey(name, avatar_url)' : 'seller:profiles!orders_seller_id_fkey(name, avatar_url)';
const { data: orders } = await sb.from('orders').select('*, ' + other).eq(mine, user.id).order('updated_at', { ascending: false });

if (!orders?.length) {
    $('#list').innerHTML = emptyState('bi-bag', 'No orders yet', tab === 'selling' ? 'Orders from clients will appear here.' : 'Find a freelancer to get something done.', '<a class="btn btn-brand" href="services.html">Browse services</a>');
} else {
    $('#list').innerHTML = `<div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>#</th><th>Service</th><th>${tab === 'selling' ? 'Buyer' : 'Seller'}</th><th>Price</th><th>Status</th><th>Updated</th></tr></thead><tbody>
        ${orders.map(o => { const p = o.buyer || o.seller || {}; return `<tr style="cursor:pointer" onclick="location.href='${link('order.html', { id: o.id })}'">
            <td class="text-body-secondary">${o.id}</td>
            <td><a class="fw-semibold text-decoration-none" href="${link('order.html', { id: o.id })}">${esc(o.title)}</a></td>
            <td><div class="d-flex align-items-center gap-2">${avatar(p, 28)}${esc(p.name)}</div></td>
            <td class="text-nowrap">${money(o.price)}</td><td>${statusBadge(o.status)}</td>
            <td class="small text-body-secondary text-nowrap">${timeAgo(o.updated_at)}</td></tr>`; }).join('')}
        </tbody></table></div>`;
}
