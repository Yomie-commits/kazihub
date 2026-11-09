import { init, sb, $, esc, link, statusBadge, timeAgo, place, emptyState, spinner } from '../app.js';

const { user } = await init({ auth: true, roles: ['worker'] });

$('#main').innerHTML = `<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h3 fw-bold mb-0">My applications</h1>
        <a href="juakali.html" class="btn btn-outline-brand">Find more jobs</a></div>
    <div class="card" id="list">${spinner()}</div></div>`;

const { data: apps } = await sb.from('applications')
    .select('*, job:jobs(id, title, type, county, town, status, employer_id, employer:profiles!jobs_employer_id_fkey(name))')
    .eq('worker_id', user.id).order('created_at', { ascending: false });

$('#list').innerHTML = !apps?.length
    ? emptyState('bi-send', "You haven't applied for any jobs yet", 'Browse juakali and UAE jobs and apply in one click.', '<a class="btn btn-brand" href="juakali.html">Browse jobs</a>')
    : `<div class="table-responsive"><table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>Job</th><th>Employer</th><th>Status</th><th>Applied</th><th></th></tr></thead><tbody>
        ${apps.map(a => { const j = a.job || {}; return `<tr>
            <td><a class="fw-semibold text-decoration-none" href="${link('job.html', { id: a.job_id })}">${esc(j.title || 'Job removed')}</a>
                <div class="small text-body-secondary">${j.type === 'uae' ? 'UAE' : 'Juakali'} · ${esc(place(j.town, j.county))}${j.status === 'closed' ? ' · job closed' : ''}</div></td>
            <td>${esc(j.employer?.name || '')}</td><td>${statusBadge(a.status)}</td>
            <td class="small text-body-secondary text-nowrap">${timeAgo(a.created_at)}</td>
            <td class="text-end">${j.employer_id ? `<a class="btn btn-sm btn-light" href="${link('messages.html', { with: j.employer_id })}" title="Message employer"><i class="bi bi-chat-dots"></i></a>` : ''}</td></tr>`; }).join('')}
        </tbody></table></div>`;
