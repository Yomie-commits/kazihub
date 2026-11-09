import { init, sb, $, $$, esc, link, money, statusBadge, emptyState, showAlert, errText, spinner } from '../app.js';

const { user } = await init({ auth: true, roles: ['worker'] });
const main = $('#main');

async function render() {
    main.innerHTML = `<div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h3 fw-bold mb-0">My services</h1>
            <a href="service-edit.html" class="btn btn-brand"><i class="bi bi-plus-lg"></i> New service</a></div>
        <div id="alerts"></div><div class="card" id="list">${spinner()}</div></div>`;
    const [{ data: services }, { data: orders }] = await Promise.all([
        sb.from('services').select('*').eq('user_id', user.id).order('created_at', { ascending: false }),
        sb.from('orders').select('service_id, status').eq('seller_id', user.id),
    ]);
    if (!services?.length) {
        $('#list').innerHTML = emptyState('bi-grid', "You haven't listed any services yet", 'Create your first service so clients can hire you.', '<a class="btn btn-brand" href="service-edit.html">Create a service</a>');
        return;
    }
    const count = (sid, active) => (orders || []).filter(o => o.service_id === sid && (!active || ['pending', 'in_progress', 'delivered'].includes(o.status))).length;
    $('#list').innerHTML = `<div class="table-responsive"><table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>Service</th><th>Price</th><th>Orders</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
        ${services.map(s => `<tr>
            <td><a href="${link('service.html', { id: s.id })}" class="fw-semibold text-decoration-none">${esc(s.title)}</a><div class="small text-body-secondary">${esc(s.category)}</div></td>
            <td class="text-nowrap">${money(s.price)}</td>
            <td>${count(s.id)}${count(s.id, true) ? ` <span class="badge text-bg-primary">${count(s.id, true)} active</span>` : ''}</td>
            <td>${s.is_active ? statusBadge('active') : '<span class="badge text-bg-secondary">Paused</span>'}</td>
            <td class="text-end text-nowrap">
                <a href="${link('service-edit.html', { id: s.id })}" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>
                <button class="btn btn-sm btn-light" data-toggle="${s.id}" data-active="${s.is_active}" title="${s.is_active ? 'Pause' : 'Activate'}"><i class="bi ${s.is_active ? 'bi-pause' : 'bi-play'}"></i></button>
                <button class="btn btn-sm btn-light text-danger" data-delete="${s.id}" title="Delete" data-confirm="Delete this service? This cannot be undone."><i class="bi bi-trash"></i></button>
            </td></tr>`).join('')}
        </tbody></table></div>`;

    $$('[data-toggle]').forEach(b => b.addEventListener('click', async () => {
        const { error } = await sb.from('services').update({ is_active: b.dataset.active !== 'true' }).eq('id', b.dataset.toggle);
        if (error) return showAlert('danger', errText(error));
        render();
    }));
    $$('[data-delete]').forEach(b => b.addEventListener('click', async () => {
        const { error } = await sb.from('services').delete().eq('id', b.dataset.delete);
        if (error) return showAlert('danger', errText(error));
        render();
    }));
}
render();
