import { init, sb, $, $$, esc, link, avatar, statusBadge, timeAgo, payRange, emptyState, showAlert, errText, busy, spinner, cleanSearch, options, JOB_SELECT } from '../app.js';

const { user } = await init({ auth: true, roles: ['admin'] });
const main = $('#main');
const tabs = { overview: 'Overview', jobs: 'Jobs', users: 'Users', reports: 'Reports', inbox: 'Contact inbox' };
const tab = () => (location.hash.slice(1) in tabs ? location.hash.slice(1) : 'overview');

async function render() {
    const { data: s } = await sb.rpc('admin_stats');
    const badge = { jobs: s?.pending_jobs ? `<span class="badge text-bg-warning">${s.pending_jobs}</span>` : '',
        reports: s?.new_reports ? `<span class="badge text-bg-danger">${s.new_reports}</span>` : '',
        inbox: s?.new_contacts ? `<span class="badge text-bg-info">${s.new_contacts}</span>` : '' };
    main.innerHTML = `<div class="container py-4">
        <h1 class="h3 fw-bold mb-3"><i class="bi bi-shield-lock text-brand"></i> Admin</h1>
        <ul class="nav nav-pills admin-nav mb-4 gap-1 flex-nowrap overflow-auto">
            ${Object.entries(tabs).map(([k, v]) => `<li class="nav-item"><a class="nav-link text-nowrap ${tab() === k ? 'active' : ''}" href="#${k}">${v} ${badge[k] || ''}</a></li>`).join('')}
        </ul><div id="alerts"></div><div id="panel">${spinner()}</div></div>`;
    await ({ overview, jobs, users, reports, inbox })[tab()](s);
}
window.addEventListener('hashchange', render);

async function act(fn) {
    const { error } = await fn();
    if (error) { showAlert('danger', errText(error)); return false; }
    await render();
    return true;
}

async function overview(s) {
    const [{ data: pending }, { data: latest }] = await Promise.all([
        sb.from('jobs').select(JOB_SELECT).eq('status', 'pending').order('created_at').limit(10),
        sb.rpc('admin_list_users', { p_search: '', p_role: '' }),
    ]);
    const tiles = [['Workers', s.workers, 'bi-person-workspace', 'success'], ['Employers', s.employers, 'bi-building', 'primary'], ['Open jobs', s.open_jobs, 'bi-briefcase', 'warning'],
        ['Active services', s.services, 'bi-grid', 'info'], ['Orders', s.orders, 'bi-bag', 'secondary'], ['Applications', s.applications, 'bi-send', 'danger']];
    $('#panel').innerHTML = `<div class="row g-3 mb-4">${tiles.map(([l, v, i, c]) => `<div class="col-6 col-md-4 col-xl-2"><div class="card stat-card h-100"><div class="card-body">
            <div class="stat-icon bg-${c}-subtle text-${c}-emphasis mb-2"><i class="bi ${i}"></i></div><div class="stat-value">${Number(v).toLocaleString()}</div><div class="small text-body-secondary">${l}</div></div></div></div>`).join('')}</div>
        <div class="row g-4">
            <div class="col-lg-7"><div class="card"><div class="card-header bg-white d-flex justify-content-between"><strong>Jobs waiting for approval</strong><a class="small" href="#jobs">All</a></div>
                ${pending?.length ? `<ul class="list-group list-group-flush">${pending.map(j => `<li class="list-group-item"><div class="d-flex justify-content-between gap-2">
                    <div class="min-w-0"><a href="${link('job.html', { id: j.id })}" class="fw-semibold">${esc(j.title)}</a>
                        <div class="small text-body-secondary">${esc(j.company || j.employer?.name)} · ${esc(j.county)} · ${esc(payRange(j))} · by ${esc(j.employer?.name)}${j.employer?.is_verified ? ' ✔' : ''}</div></div>
                    <div class="d-flex gap-1 align-items-start"><button class="btn btn-sm btn-brand" data-job="${j.id}" data-action="approve">Approve</button><button class="btn btn-sm btn-outline-danger" data-job="${j.id}" data-action="reject">Reject</button></div>
                    </div></li>`).join('')}</ul>` : '<div class="card-body small text-body-secondary">Nothing to review. 🎉</div>'}
            </div></div>
            <div class="col-lg-5"><div class="card"><div class="card-header bg-white d-flex justify-content-between"><strong>Newest users</strong><a class="small" href="#users">All</a></div>
                <ul class="list-group list-group-flush">${(latest || []).slice(0, 8).map(u => `<li class="list-group-item d-flex gap-2 align-items-center">${avatar(u, 34)}
                    <div class="flex-grow-1 min-w-0"><a class="small fw-semibold" href="${link('user.html', { id: u.id })}">${esc(u.name)}</a><div class="small text-body-secondary text-truncate">${esc(u.email)}</div></div>
                    <span class="badge text-bg-light">${esc(u.role)}</span></li>`).join('')}</ul></div></div>
        </div>`;
    wireJobButtons();
}

function wireJobButtons() {
    $$('[data-job]').forEach(b => b.addEventListener('click', () => busy(b, () =>
        act(() => b.dataset.action === 'delete'
            ? sb.from('jobs').delete().eq('id', b.dataset.job)
            : sb.rpc('admin_set_job_status', { p_job: Number(b.dataset.job), p_action: b.dataset.action })))));
}

async function jobs() {
    const f = JSON.parse(sessionStorage.getItem('kh_admin_jobs') || '{"status":"","type":"","q":""}');
    let q = sb.from('jobs').select(JOB_SELECT).order('created_at', { ascending: false }).limit(200);
    if (f.status) q = q.eq('status', f.status);
    if (f.type) q = q.eq('type', f.type);
    const term = cleanSearch(f.q);
    if (term) q = q.or(`title.ilike.%${term}%,company.ilike.%${term}%`);
    const { data } = await q;
    const list = (data || []).sort((a, b) => (a.status === 'pending' ? 0 : 1) - (b.status === 'pending' ? 0 : 1));
    $('#panel').innerHTML = `<form class="row g-2 mb-3" id="filter">
            <div class="col-md-5"><input class="form-control" name="q" value="${esc(f.q)}" placeholder="Search title or company"></div>
            <div class="col-6 col-md-3"><select class="form-select" name="status"><option value="">All statuses</option>${options({ pending: 'Pending', open: 'Open', closed: 'Closed', rejected: 'Rejected' }, f.status, true)}</select></div>
            <div class="col-6 col-md-2"><select class="form-select" name="type"><option value="">All types</option>${options({ juakali: 'Juakali', uae: 'UAE' }, f.type, true)}</select></div>
            <div class="col-md-2"><button class="btn btn-brand w-100">Filter</button></div></form>
        <div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Job</th><th>Employer</th><th>Type</th><th>Status</th><th>Posted</th><th class="text-end">Actions</th></tr></thead><tbody>
            ${list.length ? list.map(j => `<tr>
                <td><a href="${link('job.html', { id: j.id })}" class="fw-semibold">${esc(j.title)}</a><div class="small text-body-secondary">${esc(j.company || j.category)} · ${esc(j.county)} · ${esc(payRange(j))}</div></td>
                <td class="small"><a href="${link('user.html', { id: j.employer_id })}">${esc(j.employer?.name || 'User')}</a>${j.employer?.is_verified ? ' <i class="bi bi-patch-check-fill text-brand"></i>' : ''}</td>
                <td>${j.type === 'uae' ? '<span class="badge text-bg-primary">UAE</span>' : '<span class="badge text-bg-warning">Juakali</span>'}</td>
                <td>${statusBadge(j.status)}</td><td class="small text-nowrap text-body-secondary">${timeAgo(j.created_at)}</td>
                <td class="text-end text-nowrap">
                    ${j.status !== 'open' ? `<button class="btn btn-sm btn-brand" data-job="${j.id}" data-action="approve" title="Approve"><i class="bi bi-check-lg"></i></button>` : ''}
                    ${j.status === 'pending' ? `<button class="btn btn-sm btn-outline-danger" data-job="${j.id}" data-action="reject" title="Reject"><i class="bi bi-x-lg"></i></button>` : ''}
                    ${j.status === 'open' ? `<button class="btn btn-sm btn-light" data-job="${j.id}" data-action="close" title="Close"><i class="bi bi-lock"></i></button>` : ''}
                    <button class="btn btn-sm btn-light text-danger" data-job="${j.id}" data-action="delete" title="Delete" data-confirm="Delete this job permanently?"><i class="bi bi-trash"></i></button>
                </td></tr>`).join('') : '<tr><td colspan="6" class="text-center text-body-secondary py-4">No jobs found.</td></tr>'}
            </tbody></table></div></div>`;
    $('#filter').addEventListener('submit', e => {
        e.preventDefault();
        sessionStorage.setItem('kh_admin_jobs', JSON.stringify(Object.fromEntries(new FormData(e.target))));
        render();
    });
    wireJobButtons();
}

async function users() {
    const f = JSON.parse(sessionStorage.getItem('kh_admin_users') || '{"q":"","role":""}');
    const { data } = await sb.rpc('admin_list_users', { p_search: f.q.trim(), p_role: f.role });
    $('#panel').innerHTML = `<form class="row g-2 mb-3" id="filter">
            <div class="col-md-6"><input class="form-control" name="q" value="${esc(f.q)}" placeholder="Search name, email or phone"></div>
            <div class="col-6 col-md-4"><select class="form-select" name="role"><option value="">All roles</option>${options({ worker: 'Workers', employer: 'Employers', admin: 'Admins' }, f.role, true)}</select></div>
            <div class="col-6 col-md-2"><button class="btn btn-brand w-100">Filter</button></div></form>
        <div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>User</th><th>Role</th><th>Phone</th><th>County</th><th>Joined</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
            ${(data || []).length ? data.map(u => `<tr>
                <td><div class="d-flex gap-2 align-items-center">${avatar(u, 34)}<div><a class="fw-semibold" href="${link('user.html', { id: u.id })}">${esc(u.name)}</a>${u.is_verified ? ' <i class="bi bi-patch-check-fill text-brand"></i>' : ''}<div class="small text-body-secondary">${esc(u.email)}</div></div></div></td>
                <td><span class="badge text-bg-light">${esc(u.role)}</span></td><td class="small">${esc(u.phone || '')}</td><td class="small">${esc(u.county || '')}</td>
                <td class="small text-nowrap text-body-secondary">${new Date(u.created_at).toLocaleDateString('en-GB')}</td><td>${statusBadge(u.status)}</td>
                <td class="text-end text-nowrap">${u.id === user.id ? '<span class="small text-body-secondary">You</span>' : `
                    <button class="btn btn-sm ${u.is_verified ? 'btn-light' : 'btn-outline-brand'}" data-user="${u.id}" data-action="verify">${u.is_verified ? 'Unverify' : 'Verify'}</button>
                    <button class="btn btn-sm ${u.status === 'suspended' ? 'btn-outline-success' : 'btn-outline-danger'}" data-user="${u.id}" data-action="suspend" ${u.status === 'suspended' ? '' : 'data-confirm="Suspend this user? Their jobs and services will be hidden."'}>${u.status === 'suspended' ? 'Reactivate' : 'Suspend'}</button>
                    <a class="btn btn-sm btn-light" href="${link('messages.html', { with: u.id })}" title="Message"><i class="bi bi-chat-dots"></i></a>`}</td></tr>`).join('')
              : '<tr><td colspan="7" class="text-center text-body-secondary py-4">No users found.</td></tr>'}
            </tbody></table></div></div>`;
    $('#filter').addEventListener('submit', e => {
        e.preventDefault();
        sessionStorage.setItem('kh_admin_users', JSON.stringify(Object.fromEntries(new FormData(e.target))));
        render();
    });
    $$('[data-user]').forEach(b => b.addEventListener('click', () => busy(b, () => act(() => sb.rpc('admin_set_user', { p_user: b.dataset.user, p_action: b.dataset.action })))));
}

async function reports() {
    const { data } = await sb.from('reports').select('*, reporter:profiles!reports_reporter_id_fkey(name), job:jobs(id, title, status, type, employer_id)')
        .order('created_at', { ascending: false }).limit(200);
    const list = (data || []).sort((a, b) => (a.status === 'new' ? 0 : 1) - (b.status === 'new' ? 0 : 1));
    $('#panel').innerHTML = `<div class="card">${list.length ? `<ul class="list-group list-group-flush">${list.map(r => `<li class="list-group-item p-3"><div class="d-flex flex-wrap justify-content-between gap-2">
            <div>${statusBadge(r.status)} <a class="fw-semibold" href="${link('job.html', { id: r.job_id })}">${esc(r.job?.title || 'Job')}</a>
                <span class="small text-body-secondary">(${esc((r.job?.type || '').toUpperCase())}, job is ${esc(r.job?.status)})</span>
                <p class="mb-1 mt-2">${esc(r.reason)}</p><div class="small text-body-secondary">Reported by ${esc(r.reporter?.name || 'user')} · ${timeAgo(r.created_at)}</div></div>
            <div class="d-flex gap-1 align-items-start">
                ${r.job?.status !== 'closed' ? `<button class="btn btn-sm btn-outline-danger" data-job="${r.job_id}" data-action="close">Close job</button>` : ''}
                ${r.status === 'new' ? `<button class="btn btn-sm btn-light" data-report="${r.id}">Mark reviewed</button>` : ''}
            </div></div></li>`).join('')}</ul>` : emptyState('bi-flag', 'No reports', 'Reported listings will appear here.')}</div>`;
    wireJobButtons();
    $$('[data-report]').forEach(b => b.addEventListener('click', () => busy(b, () => act(() => sb.from('reports').update({ status: 'reviewed' }).eq('id', b.dataset.report)))));
}

async function inbox() {
    const { data } = await sb.from('contact_messages').select('*').order('created_at', { ascending: false }).limit(200);
    const list = (data || []).sort((a, b) => (a.status === 'new' ? 0 : 1) - (b.status === 'new' ? 0 : 1));
    $('#panel').innerHTML = `<div class="card">${list.length ? `<ul class="list-group list-group-flush">${list.map(m => `<li class="list-group-item p-3"><div class="d-flex flex-wrap justify-content-between gap-2">
            <div class="min-w-0">${m.status === 'new' ? statusBadge('new') : ''} <strong>${esc(m.subject)}</strong>
                <div class="small text-body-secondary">${esc(m.name)} · <a href="mailto:${esc(m.email)}">${esc(m.email)}</a> · ${timeAgo(m.created_at)}</div>
                <p class="mb-0 mt-2 pre-line">${esc(m.message)}</p></div>
            <div class="d-flex gap-1 align-items-start">
                <a class="btn btn-sm btn-outline-brand" href="mailto:${esc(m.email)}?subject=${encodeURIComponent('Re: ' + m.subject)}">Reply by email</a>
                ${m.status === 'new' ? `<button class="btn btn-sm btn-light" data-contact="${m.id}" data-action="read">Mark handled</button>` : ''}
                <button class="btn btn-sm btn-light text-danger" data-contact="${m.id}" data-action="delete" data-confirm="Delete this message?"><i class="bi bi-trash"></i></button>
            </div></div></li>`).join('')}</ul>` : emptyState('bi-inbox', 'Inbox is empty')}</div>`;
    $$('[data-contact]').forEach(b => b.addEventListener('click', () => busy(b, () => act(() => b.dataset.action === 'delete'
        ? sb.from('contact_messages').delete().eq('id', b.dataset.contact)
        : sb.from('contact_messages').update({ status: 'reviewed' }).eq('id', b.dataset.contact)))));
}

render();
