import { init, sb, $, $$, esc, link, statusBadge, timeAgo, emptyState, showAlert, errText, spinner } from '../app.js';

const { user } = await init({ auth: true, roles: ['employer', 'admin'] });

async function render() {
    $('#main').innerHTML = `<div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h3 fw-bold mb-0">My job posts</h1>
            <a href="job-post.html" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Post a job</a></div>
        <div id="alerts"></div><div class="card" id="list">${spinner()}</div></div>`;
    const [{ data: jobs }, { data: newApps }] = await Promise.all([
        sb.from('jobs').select('*').eq('employer_id', user.id).order('created_at', { ascending: false }),
        sb.from('applications').select('job_id, jobs!inner(employer_id)').eq('status', 'pending').eq('jobs.employer_id', user.id),
    ]);
    if (!jobs?.length) {
        $('#list').innerHTML = emptyState('bi-briefcase', "You haven't posted any jobs yet", 'Post a job and start receiving applications from workers.', '<a class="btn btn-brand" href="job-post.html">Post your first job</a>');
        return;
    }
    const fresh = {};
    (newApps || []).forEach(a => { fresh[a.job_id] = (fresh[a.job_id] || 0) + 1; });
    $('#list').innerHTML = `<div class="table-responsive"><table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>Job</th><th>Type</th><th>Applicants</th><th>Status</th><th>Posted</th><th class="text-end">Actions</th></tr></thead><tbody>
        ${jobs.map(j => `<tr>
            <td><a class="fw-semibold text-decoration-none" href="${link('job.html', { id: j.id })}">${esc(j.title)}</a><div class="small text-body-secondary">${esc(j.category)} · ${esc(j.county)}</div></td>
            <td>${j.type === 'uae' ? '<span class="badge text-bg-primary">UAE</span>' : '<span class="badge text-bg-warning">Juakali</span>'}</td>
            <td><a href="${link('job.html', { id: j.id, tab: 'applicants' })}" class="text-decoration-none">${j.applicant_count}</a>${fresh[j.id] ? ` <span class="badge text-bg-danger">${fresh[j.id]} new</span>` : ''}</td>
            <td>${statusBadge(j.status)}</td>
            <td class="small text-body-secondary text-nowrap">${timeAgo(j.created_at)}</td>
            <td class="text-end text-nowrap">
                <a href="${link('job-post.html', { id: j.id })}" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>
                ${j.status === 'closed' ? `<button class="btn btn-sm btn-light" data-status="open" data-id="${j.id}" title="Reopen"><i class="bi bi-arrow-counterclockwise"></i></button>`
                  : j.status !== 'rejected' ? `<button class="btn btn-sm btn-light" data-status="closed" data-id="${j.id}" title="Close" data-confirm="Close this job?"><i class="bi bi-lock"></i></button>` : ''}
                <button class="btn btn-sm btn-light text-danger" data-delete="${j.id}" title="Delete" data-confirm="Delete this job and all its applications?"><i class="bi bi-trash"></i></button>
            </td></tr>`).join('')}
        </tbody></table></div>`;

    $$('[data-status]').forEach(b => b.addEventListener('click', async () => {
        const { error } = await sb.from('jobs').update({ status: b.dataset.status }).eq('id', b.dataset.id);
        if (error) return showAlert('danger', errText(error));
        render();
    }));
    $$('[data-delete]').forEach(b => b.addEventListener('click', async () => {
        const { error } = await sb.from('jobs').delete().eq('id', b.dataset.delete);
        if (error) return showAlert('danger', errText(error));
        render();
    }));
}
render();
