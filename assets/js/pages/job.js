import { init, halt, sb, state, $, $$, esc, param, link, money, avatar, stars, statusBadge, timeAgo, place, payRange, firstName, formatKm,
    distanceKm, emptyState, ratingPicker, wireRatingPickers, showAlert, errText, busy, flash, setTitle, ratingsFor, JOB_SELECT } from '../app.js';
import { categoryIcon } from '../data.js';

const { user, profile } = await init();
const main = $('#main');
const id = Number(param('id'));

const { data: job } = id ? await sb.from('jobs').select(JOB_SELECT).eq('id', id).maybeSingle() : { data: null };
if (!job) {
    main.innerHTML = `<div class="container py-5">${emptyState('bi-briefcase', 'Job not found', 'This job may have been filled or removed.', '<a class="btn btn-brand" href="juakali.html">Browse jobs</a>')}</div>`;
    await halt();
}
setTitle(job.title);
const isOwner = user?.id === job.employer_id;
const isAdmin = profile?.role === 'admin';
const manage = isOwner || isAdmin;
const uae = job.type === 'uae';
const deadlinePassed = job.deadline && job.deadline < new Date().toISOString().slice(0, 10);
const tab = param('tab') === 'applicants' && manage ? 'applicants' : 'details';
const emp = job.employer || {};

const { data: myApp } = user ? await sb.from('applications').select('*').eq('job_id', job.id).eq('worker_id', user.id).maybeSingle() : { data: null };
const km = profile ? distanceKm(profile.latitude, profile.longitude, job.latitude, job.longitude) : null;

const statusNote = {
    pending: '<div class="alert alert-warning"><i class="bi bi-hourglass-split"></i> This job is waiting for admin approval and is not public yet.</div>',
    rejected: '<div class="alert alert-danger"><i class="bi bi-x-circle"></i> This listing was not approved. Edit it with the correct company details to submit it again, or <a href="contact.html">contact us</a>.</div>',
    closed: '<div class="alert alert-secondary">This job is closed and no longer accepts applications.</div>',
}[job.status] || '';

function sidebar() {
    if (isOwner) {
        return `<h6 class="fw-bold">Manage this job</h6><p class="small text-body-secondary">${job.applicant_count} application${job.applicant_count === 1 ? '' : 's'} so far.</p>
            <div class="d-grid gap-2">
                <a href="${link('job.html', { id: job.id, tab: 'applicants' })}" class="btn btn-brand">View applicants</a>
                <a href="${link('job-post.html', { id: job.id })}" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i> Edit job</a>
                ${['open', 'pending'].includes(job.status) ? '<button class="btn btn-outline-danger" id="closeJob" data-confirm="Close this job? It will stop accepting applications.">Close job</button>' : ''}
            </div>`;
    }
    let html;
    if (!user) {
        html = `<h6 class="fw-bold">Interested?</h6><p class="small text-body-secondary">Create a free worker account to apply.</p>
            <a href="register.html?role=worker" class="btn btn-brand w-100 mb-2">Join to apply</a>
            <a href="${link('login.html', { next: 'job.html?id=' + job.id })}" class="btn btn-outline-secondary w-100">Sign in</a>`;
    } else if (myApp) {
        html = `<h6 class="fw-bold">Your application</h6><p class="mb-2">${statusBadge(myApp.status)} <span class="small text-body-secondary">sent ${timeAgo(myApp.created_at)}</span></p>
            <a href="${link('messages.html', { with: job.employer_id })}" class="btn btn-outline-brand w-100 mb-2"><i class="bi bi-chat-dots"></i> Message employer</a>
            ${['pending', 'shortlisted'].includes(myApp.status) ? '<button class="btn btn-link text-danger btn-sm w-100" id="withdraw" data-confirm="Withdraw your application?">Withdraw application</button>' : ''}`;
    } else if (profile.role !== 'worker') {
        html = '<p class="small text-body-secondary mb-0">Only worker accounts can apply for jobs.</p>';
    } else if (job.status !== 'open' || deadlinePassed) {
        html = '<p class="small text-body-secondary mb-0">This job is no longer accepting applications.</p>';
    } else {
        html = `<h6 class="fw-bold">Apply for this job</h6><form id="applyForm">
            <div class="mb-2"><label class="form-label small" for="cover">Why are you a good fit?</label>
                <textarea class="form-control" id="cover" rows="5" minlength="20" maxlength="3000" required placeholder="Your experience, when you can start…"></textarea></div>
            <div class="mb-3"><label class="form-label small" for="pay">Your price (${esc(job.currency)}) <span class="text-body-secondary fw-normal">(optional)</span></label>
                <input type="number" min="0" class="form-control" id="pay"></div>
            <button class="btn btn-brand w-100">Send application</button></form>`;
    }
    return html + `<hr><div class="d-flex align-items-center gap-2 mb-2">${avatar(emp, 36)}<div class="small">
        <a href="${link('user.html', { id: job.employer_id })}" class="fw-semibold text-body text-decoration-none">${esc(emp.name || 'Employer')}</a><div class="text-body-secondary">Posted by</div></div></div>
        ${user ? `<button class="btn btn-link btn-sm text-danger p-0" data-bs-toggle="collapse" data-bs-target="#reportForm"><i class="bi bi-flag"></i> Report this listing</button>
            <form id="reportForm" class="collapse mt-2"><textarea class="form-control form-control-sm mb-2" id="reason" rows="3" minlength="10" maxlength="2000" required placeholder="e.g. They asked me to pay a fee"></textarea>
            <button class="btn btn-sm btn-outline-danger">Send report</button></form>` : ''}`;
}

main.innerHTML = `<div class="container py-4">
    <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="${uae ? 'uae-jobs.html' : 'juakali.html'}">${uae ? 'UAE jobs' : 'Juakali jobs'}</a></li>
        <li class="breadcrumb-item"><a href="${link(uae ? 'uae-jobs.html' : 'juakali.html', { category: job.category })}">${esc(job.category)}</a></li></ol></nav>
    ${statusNote}<div id="alerts"></div>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4"><div class="card-body p-4">
                <div class="d-flex gap-3 align-items-start">
                    <div class="feature-icon flex-shrink-0 ${uae ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-warning-emphasis'}"><i class="bi ${categoryIcon(job.category)}"></i></div>
                    <div class="flex-grow-1"><h1 class="h4 fw-bold mb-1">${esc(job.title)}</h1>
                        <div class="text-body-secondary small">${esc(uae ? job.company : emp.name)} ${emp.is_verified ? '<i class="bi bi-patch-check-fill text-brand" title="Verified employer"></i>' : ''} · Posted ${timeAgo(job.created_at)}</div></div>
                </div>
                <div class="row g-3 mt-2 small">
                    <div class="col-6 col-md-3"><div class="text-body-secondary">Pay</div><strong class="text-brand">${esc(payRange(job))}</strong></div>
                    <div class="col-6 col-md-3"><div class="text-body-secondary">Location</div><strong>${esc(place(job.town, job.county))}</strong>${km !== null ? `<div>${formatKm(km)}</div>` : ''}</div>
                    <div class="col-6 col-md-3"><div class="text-body-secondary">Workers needed</div><strong>${job.positions}</strong></div>
                    <div class="col-6 col-md-3"><div class="text-body-secondary">Apply by</div><strong>${job.deadline ? new Date(job.deadline).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Open'}</strong></div>
                </div>
                <div class="mt-3"><span class="chip">${esc(job.category)}</span>
                    ${job.urgency === 'urgent' ? '<span class="chip bg-danger-subtle text-danger-emphasis"><i class="bi bi-lightning-fill"></i> Urgent</span>' : ''}
                    ${uae ? `<span class="chip ${job.visa_sponsored ? 'bg-primary-subtle text-primary-emphasis' : ''}"><i class="bi bi-passport"></i> Visa ${job.visa_sponsored ? 'sponsored' : 'not sponsored'}</span>
                        <span class="chip ${job.accommodation ? 'bg-info-subtle text-info-emphasis' : ''}"><i class="bi bi-house"></i> Accommodation ${job.accommodation ? 'provided' : 'not provided'}</span>` : ''}</div>
            </div></div>
            ${manage ? `<ul class="nav nav-tabs">
                <li class="nav-item"><a class="nav-link ${tab === 'details' ? 'active' : ''}" href="${link('job.html', { id: job.id })}">Details</a></li>
                <li class="nav-item"><a class="nav-link ${tab === 'applicants' ? 'active' : ''}" href="${link('job.html', { id: job.id, tab: 'applicants' })}">Applicants <span class="badge text-bg-secondary">${job.applicant_count}</span></a></li></ul>` : ''}
            <div class="card ${manage ? 'border-top-0 rounded-top-0' : ''}" id="tabBody">
                ${tab === 'details' ? `<div class="card-body p-4"><h5 class="fw-bold">Job description</h5><p class="mb-0 pre-line">${esc(job.description)}</p></div>` : ''}
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card sidebar-filter shadow-sm"><div class="card-body p-4">${sidebar()}</div></div>
            ${uae ? '<div class="warning-box p-3 mt-3 small"><strong><i class="bi bi-shield-exclamation text-accent"></i> Never pay to apply.</strong> Do not send money for visas, medicals or “placement” before you receive a written contract. Report anyone who asks.</div>' : ''}
        </div>
    </div></div>`;

// ---- actions
$('#applyForm')?.addEventListener('submit', e => {
    e.preventDefault();
    const cover = $('#cover').value.trim();
    if (cover.length < 20) return showAlert('danger', 'Tell the employer why you are a good fit (at least 20 characters).');
    busy(e.submitter, async () => {
        const pay = $('#pay').value;
        const { error } = await sb.rpc('apply_job', { p_job_id: job.id, p_cover: cover, p_expected_pay: pay === '' ? null : Number(pay) });
        if (error) return showAlert('danger', errText(error));
        flash('success', 'Application sent! The employer will contact you here on KaziHub.');
        location.reload();
    });
});
$('#withdraw')?.addEventListener('click', e => busy(e.currentTarget, async () => {
    const { error } = await sb.rpc('application_action', { p_application_id: myApp.id, p_action: 'withdraw' });
    if (error) return showAlert('danger', errText(error));
    flash('info', 'Application withdrawn.');
    location.reload();
}));
$('#closeJob')?.addEventListener('click', e => busy(e.currentTarget, async () => {
    const { error } = await sb.from('jobs').update({ status: 'closed' }).eq('id', job.id);
    if (error) return showAlert('danger', errText(error));
    flash('success', 'Job closed. It no longer accepts applications.');
    location.reload();
}));
$('#reportForm')?.addEventListener('submit', e => {
    e.preventDefault();
    const reason = $('#reason').value.trim();
    if (reason.length < 10) return showAlert('danger', 'Please tell us what is wrong (at least 10 characters).');
    busy(e.submitter, async () => {
        const { error } = await sb.from('reports').insert({ job_id: job.id, reason });
        if (error) return showAlert('danger', errText(error));
        $('#reportForm').innerHTML = '<p class="small text-success mb-0"><i class="bi bi-check-circle"></i> Thank you. Our team will review this listing.</p>';
    });
});

// ---- applicants tab
if (tab === 'applicants') loadApplicants();

async function loadApplicants() {
    const { data: apps } = await sb.from('applications')
        .select('*, worker:profiles!applications_worker_id_fkey(id, name, avatar_url, is_verified, county, town)')
        .eq('job_id', job.id).order('created_at');
    const order = { accepted: 0, shortlisted: 1, pending: 2 };
    (apps || []).sort((a, b) => (order[a.status] ?? 3) - (order[b.status] ?? 3));
    if (!apps?.length) {
        $('#tabBody').innerHTML = emptyState('bi-people', 'No applications yet', 'Share your job link to reach more workers.');
        return;
    }
    const ids = apps.map(a => a.worker_id);
    const [ratings, { data: phones }, { data: reviews }] = await Promise.all([
        ratingsFor(ids),
        sb.from('profile_private').select('id, phone').in('id', ids),
        sb.from('reviews').select('application_id').in('application_id', apps.map(a => a.id)),
    ]);
    const phone = Object.fromEntries((phones || []).map(p => [p.id, p.phone]));
    const reviewed = new Set((reviews || []).map(r => r.application_id));

    $('#tabBody').innerHTML = apps.map(a => {
        const w = a.worker || { name: 'User' };
        const r = ratings[a.worker_id];
        const buttons = a.status === 'withdrawn' ? '' : [['shortlisted', 'Shortlist', 'btn-outline-info'], ['accepted', 'Accept', 'btn-brand'], ['rejected', 'Reject', 'btn-outline-danger']]
            .filter(([st]) => st !== a.status).map(([st, label, cls]) => `<button class="btn btn-sm ${cls}" data-app="${a.id}" data-status="${st}">${label}</button>`).join('');
        return `<div class="p-4 border-bottom">
            <div class="d-flex flex-wrap gap-3 justify-content-between">
                <div class="d-flex gap-3">${avatar(w, 52)}<div>
                    <a href="${link('user.html', { id: a.worker_id })}" class="fw-bold text-body text-decoration-none">${esc(w.name)}</a>
                    ${w.is_verified ? '<i class="bi bi-patch-check-fill text-brand"></i>' : ''} ${statusBadge(a.status)}
                    <div class="small text-body-secondary">${esc(place(w.town, w.county))} · applied ${timeAgo(a.created_at)}${r ? ' · ' + stars(r.rating) : ''}</div>
                    ${a.expected_pay ? `<div class="small">Expected pay: <strong>${money(a.expected_pay, job.currency)}</strong></div>` : ''}
                    ${a.status === 'accepted' && phone[a.worker_id] ? `<div class="small"><i class="bi bi-telephone"></i> <a href="tel:${esc(phone[a.worker_id])}">${esc(phone[a.worker_id])}</a></div>` : ''}
                </div></div>
                <div class="d-flex flex-wrap gap-1 align-items-start">
                    <a href="${link('messages.html', { with: a.worker_id })}" class="btn btn-sm btn-outline-secondary" title="Message"><i class="bi bi-chat-dots"></i></a>${buttons}
                </div>
            </div>
            ${a.cover_letter ? `<p class="small mt-3 mb-0 bg-body-tertiary rounded p-3 pre-line">${esc(a.cover_letter)}</p>` : ''}
            ${isOwner && a.status === 'accepted' && !reviewed.has(a.id) ? `<form class="mt-3 border rounded p-3" data-review="${a.id}" data-worker="${a.worker_id}">
                <div class="small fw-semibold mb-1">Job done? Rate ${esc(firstName(w.name))}'s work</div>${ratingPicker()}
                <textarea class="form-control form-control-sm my-2" name="comment" rows="2" maxlength="1000" placeholder="Optional comment"></textarea>
                <button class="btn btn-sm btn-brand">Save review</button></form>` : ''}
        </div>`;
    }).join('');

    wireRatingPickers($('#tabBody'));
    $$('[data-app]').forEach(b => b.addEventListener('click', () => busy(b, async () => {
        const { error } = await sb.rpc('application_action', { p_application_id: Number(b.dataset.app), p_action: b.dataset.status });
        if (error) return showAlert('danger', errText(error));
        loadApplicants();
    })));
    $$('form[data-review]').forEach(f => f.addEventListener('submit', e => {
        e.preventDefault();
        const rating = Number(f.querySelector('input[name=rating]').value);
        if (!rating) return showAlert('danger', 'Please choose a star rating.');
        busy(e.submitter, async () => {
            const { error } = await sb.from('reviews').insert({ reviewee_id: f.dataset.worker, application_id: Number(f.dataset.review), rating, comment: f.comment.value.trim() || null });
            if (error) return showAlert('danger', errText(error));
            showAlert('success', 'Review saved. Thank you!');
            loadApplicants();
        });
    }));
}
