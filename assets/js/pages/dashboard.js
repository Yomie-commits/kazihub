import { init, sb, state, $, esc, link, money, avatar, statusBadge, timeAgo, firstName, jobCard, skillsList, distanceKm, emptyState,
    profileCompletion, showAlert, errText, go, ratingsFor, JOB_SELECT, spinner } from '../app.js';

const { user, profile: me } = await init({ auth: true });
if (me.role === 'admin') go('admin.html');
const uid = user.id;
const completion = profileCompletion(me);
const main = $('#main');
main.innerHTML = spinner();

function listCard(title, allHref, items, emptyText) {
    return `<div class="card mb-4"><div class="card-header bg-white d-flex justify-content-between align-items-center"><strong>${title}</strong>${allHref ? `<a href="${allHref}" class="small">All</a>` : ''}</div>
        <ul class="list-group list-group-flush">${items.length ? items.join('') : `<li class="list-group-item small text-body-secondary">${emptyText}</li>`}</ul></div>`;
}
const orderItem = (o, who) => `<a href="${link('order.html', { id: o.id })}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
    <div class="min-w-0"><div class="small fw-semibold text-truncate">${esc(o.title)}</div><div class="small text-body-secondary">${esc(o[who]?.name || '')} · ${money(o.price)}</div></div>${statusBadge(o.status)}</a>`;
const statTile = ([label, value, icon, color, href]) => `<div class="col-6 col-lg-3"><a href="${href}" class="card stat-card card-hover text-decoration-none h-100"><div class="card-body d-flex align-items-center gap-3">
    <div class="stat-icon bg-${color}-subtle text-${color}-emphasis"><i class="bi ${icon}"></i></div><div><div class="stat-value">${esc(value)}</div><div class="small text-body-secondary">${label}</div></div></div></a></div>`;

let stats, body;
if (me.role === 'worker') {
    const [{ data: sold }, { data: apps }, ratings, { data: jobs }] = await Promise.all([
        sb.from('orders').select('*, buyer:profiles!orders_buyer_id_fkey(name)').eq('seller_id', uid).order('updated_at', { ascending: false }),
        sb.from('applications').select('*, job:jobs(title)').eq('worker_id', uid).order('created_at', { ascending: false }),
        ratingsFor([uid]),
        sb.from('jobs').select(JOB_SELECT).eq('type', 'juakali').eq('status', 'open').order('created_at', { ascending: false }).limit(100),
    ]);
    const o = sold || [], a = apps || [];
    const earned = o.filter(x => x.status === 'completed').reduce((s, x) => s + Number(x.price), 0);
    const r = ratings[uid];
    stats = [
        ['Active orders', o.filter(x => ['pending', 'in_progress', 'delivered'].includes(x.status)).length, 'bi-bag', 'primary', 'orders.html?tab=selling'],
        ['Applications', a.filter(x => ['pending', 'shortlisted'].includes(x.status)).length, 'bi-send', 'warning', 'applications.html'],
        ['Earned (KES)', Math.round(earned).toLocaleString('en-KE'), 'bi-cash-stack', 'success', 'orders.html?tab=selling'],
        ['Rating', r ? `${r.rating} ★` : '–', 'bi-star', 'info', link('user.html', { id: uid })],
    ];
    // Recommended: not yet applied, matching skills first, then nearest
    const applied = new Set(a.map(x => x.job_id));
    const skills = skillsList(me.skills).map(s => s.toLowerCase());
    let rec = (jobs || []).filter(j => !applied.has(j.id)).map(j => ({
        ...j, km: distanceKm(me.latitude, me.longitude, j.latitude, j.longitude),
        match: skills.some(s => `${j.title} ${j.category} ${j.description}`.toLowerCase().includes(s)),
    }));
    rec.sort((x, y) => (y.match - x.match) || ((x.km ?? Infinity) - (y.km ?? Infinity)));
    rec = rec.slice(0, 4);
    body = `<div class="col-lg-7"><div class="d-flex justify-content-between align-items-center mb-2"><h5 class="fw-bold mb-0">Recommended jobs for you</h5><a href="juakali.html" class="small">Browse all</a></div>
            ${rec.length ? rec.map(j => jobCard(j, j.km)).join('') : `<div class="card">${emptyState('bi-tools', 'No new jobs right now', 'Check back soon, or add more skills to your profile.')}</div>`}</div>
        <div class="col-lg-5">
            ${listCard('Recent orders', 'orders.html?tab=selling', o.slice(0, 5).map(x => orderItem(x, 'buyer')), 'No orders yet. <a href="my-services.html">Manage your services</a>')}
            ${listCard('My applications', 'applications.html', a.slice(0, 5).map(x => `<a href="${link('job.html', { id: x.job_id })}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <div class="min-w-0"><div class="small fw-semibold text-truncate">${esc(x.job?.title || 'Job')}</div><div class="small text-body-secondary">${timeAgo(x.created_at)}</div></div>${statusBadge(x.status)}</a>`), "You haven't applied for any jobs yet.")}
        </div>`;
} else {
    const [{ data: jobs }, { data: apps }, { data: bought }] = await Promise.all([
        sb.from('jobs').select('*').eq('employer_id', uid).order('created_at', { ascending: false }),
        sb.from('applications').select('*, worker:profiles!applications_worker_id_fkey(name, avatar_url), job:jobs!inner(title, employer_id)')
            .eq('job.employer_id', uid).neq('status', 'withdrawn').order('created_at', { ascending: false }).limit(50),
        sb.from('orders').select('*, seller:profiles!orders_seller_id_fkey(name)').eq('buyer_id', uid).order('updated_at', { ascending: false }),
    ]);
    const j = jobs || [], a = apps || [], b = bought || [];
    const fresh = {};
    a.filter(x => x.status === 'pending').forEach(x => { fresh[x.job_id] = (fresh[x.job_id] || 0) + 1; });
    stats = [
        ['Open jobs', j.filter(x => x.status === 'open').length, 'bi-briefcase', 'primary', 'my-jobs.html'],
        ['New applicants', a.filter(x => x.status === 'pending').length, 'bi-people', 'warning', 'my-jobs.html'],
        ['Pending review', j.filter(x => x.status === 'pending').length, 'bi-hourglass-split', 'info', 'my-jobs.html'],
        ['Active orders', b.filter(x => ['pending', 'in_progress', 'delivered'].includes(x.status)).length, 'bi-bag', 'success', 'orders.html'],
    ];
    body = `<div class="col-lg-7">
            ${listCard('Your jobs', 'my-jobs.html', j.slice(0, 5).map(x => `<a href="${link('job.html', { id: x.id, tab: 'applicants' })}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <div class="min-w-0"><div class="fw-semibold text-truncate">${esc(x.title)}</div><div class="small text-body-secondary">${x.type === 'uae' ? 'UAE' : 'Juakali'} · ${x.applicant_count} applicant${x.applicant_count === 1 ? '' : 's'}${fresh[x.id] ? ` · <span class="text-danger fw-semibold">${fresh[x.id]} new</span>` : ''}</div></div>${statusBadge(x.status)}</a>`),
                'No jobs posted yet. <a href="job-post.html">Post your first job</a>')}
            ${listCard('Services you ordered', 'orders.html', b.slice(0, 5).map(x => orderItem(x, 'seller')), 'Need something done online? <a href="services.html">Hire a freelancer</a>.')}
        </div>
        <div class="col-lg-5">
            ${listCard('Latest applicants', '', a.slice(0, 6).map(x => `<a href="${link('job.html', { id: x.job_id, tab: 'applicants' })}" class="list-group-item list-group-item-action d-flex gap-2 align-items-center">
                ${avatar(x.worker, 36)}<div class="flex-grow-1 min-w-0"><div class="small fw-semibold">${esc(x.worker?.name || '')}</div><div class="small text-body-secondary text-truncate">${esc(x.job?.title)}</div></div>${statusBadge(x.status)}</a>`), 'Applicants will appear here.')}
            <div class="card"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-geo-alt text-brand"></i> Need someone today?</h6>
                <p class="small text-body-secondary">See verified workers who are available near you right now.</p>
                <a href="juakali.html?view=workers" class="btn btn-outline-brand btn-sm">Find available workers</a></div></div>
        </div>`;
}

const { count: unread } = await sb.from('messages').select('id', { count: 'exact', head: true }).eq('receiver_id', uid).eq('is_read', false);

main.innerHTML = `<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">${avatar(me, 56)}<div><h1 class="h4 fw-bold mb-0">Hi, ${esc(firstName(me.name))} 👋</h1>
            <span class="text-body-secondary small">${me.role === 'worker' ? 'Worker / Freelancer' : 'Employer'} dashboard</span></div></div>
        <div class="d-flex gap-2">${me.role === 'worker'
            ? `<button class="btn ${me.is_available ? 'btn-success' : 'btn-outline-secondary'}" id="availBtn"><i class="bi ${me.is_available ? 'bi-toggle-on' : 'bi-toggle-off'}"></i> ${me.is_available ? 'Available for work' : 'Not available'}</button>
               <a href="service-edit.html" class="btn btn-brand"><i class="bi bi-plus-lg"></i> New service</a>`
            : '<a href="job-post.html" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Post a job</a>'}</div>
    </div>
    <div id="alerts"></div>
    ${completion < 100 ? `<div class="card bg-brand-soft border-0 mb-4"><div class="card-body d-flex flex-wrap align-items-center gap-3">
        <i class="bi bi-person-check fs-3 text-brand"></i><div class="flex-grow-1"><strong>Your profile is ${completion}% complete.</strong>
        <div class="small text-body-secondary">Add a photo, location${me.role === 'worker' ? ', skills and rate' : ''} so people trust you and can find you nearby.</div>
        <div class="progress mt-2" style="height:6px;max-width:360px"><div class="progress-bar bg-brand" style="width:${completion}%"></div></div></div>
        <a href="profile.html" class="btn btn-brand">Complete profile</a></div></div>` : ''}
    <div class="row g-3 mb-4">${stats.map(statTile).join('')}</div>
    ${unread ? `<div class="alert alert-info d-flex align-items-center justify-content-between"><span><i class="bi bi-envelope"></i> You have ${unread} unread message${unread > 1 ? 's' : ''}.</span><a href="messages.html" class="btn btn-sm btn-info">Open inbox</a></div>` : ''}
    <div class="row g-4">${body}</div></div>`;

$('#availBtn')?.addEventListener('click', async () => {
    const { error } = await sb.from('profiles').update({ is_available: !me.is_available }).eq('id', uid);
    if (error) return showAlert('danger', errText(error));
    location.reload();
});
