import { init, halt, sb, $, esc, param, link, avatar, stars, money, place, firstName, skillsList, isOnline, timeAgo, distanceKm, formatKm,
    emptyState, serviceCard, jobCard, statusBadge, setTitle, SERVICE_SELECT, JOB_SELECT } from '../app.js';

const { user, profile: me } = await init();
const main = $('#main');
const id = param('id');

const { data: u } = id ? await sb.from('profiles').select('*').eq('id', id).maybeSingle() : { data: null };
if (!u) {
    main.innerHTML = `<div class="container py-5">${emptyState('bi-person-x', 'Profile not found', 'This account does not exist or is no longer active.')}</div>`;
    await halt();
}
setTitle(u.name);

const [{ data: rating }, { data: reviews }, { data: services }, { data: jobs }] = await Promise.all([
    sb.from('user_ratings').select('*').eq('reviewee_id', u.id).maybeSingle(),
    sb.from('reviews').select('*, reviewer:profiles!reviews_reviewer_id_fkey(name, avatar_url)').eq('reviewee_id', u.id).order('created_at', { ascending: false }).limit(20),
    u.role === 'worker' ? sb.from('services').select(SERVICE_SELECT).eq('user_id', u.id).eq('is_active', true).order('created_at', { ascending: false }) : { data: [] },
    u.role !== 'worker' ? sb.from('jobs').select(JOB_SELECT).eq('employer_id', u.id).eq('status', 'open').order('created_at', { ascending: false }).limit(5) : { data: [] },
]);
const km = me ? distanceKm(me.latitude, me.longitude, u.latitude, u.longitude) : null;
const isMe = user?.id === u.id;

main.innerHTML = `<div class="container py-4"><div class="row g-4">
    <div class="col-lg-4"><div class="card text-center sidebar-filter"><div class="card-body p-4">
        <div class="position-relative d-inline-block mb-3">${avatar(u, 112)}${isOnline(u.last_seen) ? '<span class="online-dot position-absolute" style="bottom:8px;right:8px;width:16px;height:16px" title="Online"></span>' : ''}</div>
        <h1 class="h4 fw-bold mb-1">${esc(u.name)} ${u.is_verified ? '<i class="bi bi-patch-check-fill text-brand fs-5" title="Verified"></i>' : ''}</h1>
        <div class="text-body-secondary small mb-2">${u.role === 'worker' ? 'Worker / Freelancer' : u.role === 'admin' ? 'KaziHub team' : esc(u.company_name || 'Employer')}</div>
        ${rating ? `<div class="mb-2">${stars(rating.rating)} <span class="small text-body-secondary">(${rating.review_count} review${rating.review_count > 1 ? 's' : ''})</span></div>` : ''}
        ${u.status === 'suspended' ? `<div class="mb-2">${statusBadge('suspended')}</div>` : ''}
        ${u.role === 'worker' ? `<span class="badge rounded-pill ${u.is_available ? 'text-bg-success' : 'text-bg-secondary'} mb-3">${u.is_available ? 'Available for work' : 'Not available right now'}</span>` : ''}
        <ul class="list-unstyled text-start small border-top pt-3 mb-3">
            ${u.county ? `<li class="mb-2"><i class="bi bi-geo-alt me-2"></i>${esc(place(u.town, u.county))}${km !== null ? ' · ' + formatKm(km) : ''}</li>` : ''}
            ${u.hourly_rate ? `<li class="mb-2"><i class="bi bi-cash me-2"></i>${money(u.hourly_rate)} / hour</li>` : ''}
            ${u.experience_years !== null ? `<li class="mb-2"><i class="bi bi-award me-2"></i>${u.experience_years} years experience</li>` : ''}
            <li class="mb-2"><i class="bi bi-calendar3 me-2"></i>Member since ${new Date(u.created_at).toLocaleDateString('en-GB', { month: 'short', year: 'numeric' })}</li>
            <li><i class="bi bi-clock-history me-2"></i>${isOnline(u.last_seen) ? 'Online now' : 'Last seen ' + (timeAgo(u.last_seen) || 'a while ago')}</li>
        </ul>
        ${isMe ? '<a href="profile.html" class="btn btn-outline-brand w-100">Edit profile</a>'
          : user ? `<a href="${link('messages.html', { with: u.id })}" class="btn btn-brand w-100"><i class="bi bi-chat-dots"></i> Message ${esc(firstName(u.name))}</a>`
          : `<a href="${link('login.html', { next: 'user.html?id=' + u.id })}" class="btn btn-brand w-100">Sign in to contact</a>`}
    </div></div></div>
    <div class="col-lg-8">
        <div class="card mb-4"><div class="card-body p-4"><h5 class="fw-bold">About</h5>
            <p class="mb-3 pre-line">${u.bio ? esc(u.bio) : '<span class="text-body-secondary">No bio yet.</span>'}</p>
            ${skillsList(u.skills).length ? `<h6 class="fw-bold">Skills</h6><div>${skillsList(u.skills).map(s => `<span class="chip">${esc(s)}</span>`).join('')}</div>` : ''}
        </div></div>
        ${services?.length ? `<h5 class="fw-bold mb-3">Services by ${esc(firstName(u.name))}</h5><div class="row g-3 mb-4">${services.map(s => `<div class="col-md-6">${serviceCard(s, rating)}</div>`).join('')}</div>` : ''}
        ${jobs?.length ? `<h5 class="fw-bold mb-3">Open jobs</h5>${jobs.map(j => jobCard(j)).join('')}` : ''}
        <div class="card"><div class="card-body p-4"><h5 class="fw-bold mb-3">Reviews ${rating ? `<span class="text-body-secondary fw-normal fs-6">(${rating.review_count})</span>` : ''}</h5>
            ${reviews?.length ? reviews.map(r => `<div class="d-flex gap-3 py-3 border-bottom">${avatar(r.reviewer, 40)}<div>
                <div class="fw-semibold">${esc(r.reviewer?.name || 'User')} <span class="small text-body-secondary fw-normal">· ${timeAgo(r.created_at)}</span></div>
                ${stars(r.rating, false)}${r.comment ? `<p class="mb-0 mt-1 small">${esc(r.comment)}</p>` : ''}</div></div>`).join('') : '<p class="text-body-secondary mb-0">No reviews yet.</p>'}
        </div></div>
    </div>
</div></div>`;
