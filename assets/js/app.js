// KaziHub core: Supabase client, sign-in state, layout and helpers.
import { createClient } from 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2.45.4/+esm';
import { SUPABASE_URL, SUPABASE_ANON_KEY } from './config.js';
import { categoryIcon } from './data.js';

export const configured = !!SUPABASE_URL && !SUPABASE_URL.startsWith('YOUR_') && !!SUPABASE_ANON_KEY && !SUPABASE_ANON_KEY.startsWith('YOUR_');
export const sb = configured ? createClient(SUPABASE_URL, SUPABASE_ANON_KEY) : null;
export const state = { user: null, profile: null };
export const $ = (sel, root = document) => root.querySelector(sel);
export const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

// ---------------------------------------------------------------- text & formatting

export function esc(value) {
    return String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

export function param(name, fallback = '') {
    return new URLSearchParams(location.search).get(name) ?? fallback;
}

export function link(page, params = {}) {
    const clean = Object.fromEntries(Object.entries(params).filter(([, v]) => v !== '' && v !== null && v !== undefined));
    const q = new URLSearchParams(clean).toString();
    return page + (q ? '?' + q : '');
}

export function money(amount, currency = 'KES') {
    if (amount === null || amount === undefined || amount === '') return '';
    return currency + ' ' + Math.round(Number(amount)).toLocaleString('en-KE');
}

export function payRange(job) {
    const cur = job.currency || 'KES';
    const period = { daily: ' / day', monthly: ' / month' }[job.pay_period] || '';
    const min = job.budget_min, max = job.budget_max;
    if (min && max && Number(min) !== Number(max)) {
        return `${cur} ${Math.round(min).toLocaleString('en-KE')} – ${Math.round(max).toLocaleString('en-KE')}${period}`;
    }
    if (min || max) return money(min || max, cur) + period;
    return 'Negotiable';
}

export function timeAgo(date) {
    if (!date) return '';
    const diff = (Date.now() - new Date(date).getTime()) / 1000;
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + ' min ago';
    if (diff < 86400) return Math.floor(diff / 3600) + ' hr ago';
    if (diff < 604800) { const d = Math.floor(diff / 86400); return d + ' day' + (d > 1 ? 's' : '') + ' ago'; }
    return new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

export function shortDate(date, withTime = false) {
    if (!date) return '';
    const opts = { day: 'numeric', month: 'short' };
    if (withTime) Object.assign(opts, { hour: '2-digit', minute: '2-digit' });
    else opts.year = 'numeric';
    return new Date(date).toLocaleString('en-GB', opts);
}

export const isOnline = lastSeen => !!lastSeen && Date.now() - new Date(lastSeen).getTime() < 10 * 60 * 1000;

export function excerpt(text, len = 140) {
    const t = String(text ?? '').replace(/\s+/g, ' ').trim();
    return t.length > len ? t.slice(0, len) + '…' : t;
}

export const skillsList = s => String(s ?? '').split(',').map(x => x.trim()).filter(Boolean);
export const place = (town, county) => [town, county].filter(Boolean).join(', ');
export const firstName = name => String(name ?? '').split(' ')[0];

function hash(str) {
    let h = 0;
    for (const ch of String(str)) h = (h * 31 + ch.charCodeAt(0)) | 0;
    return Math.abs(h);
}

export function avatar(person, size = 40) {
    const name = person?.name || '?';
    if (person?.avatar_url) {
        return `<img src="${esc(person.avatar_url)}" alt="${esc(name)}" class="rounded-circle object-fit-cover flex-shrink-0" width="${size}" height="${size}">`;
    }
    const parts = name.trim().split(/\s+/);
    const initials = ((parts[0] || '')[0] || '') + ((parts[1] || '')[0] || '');
    return `<span class="avatar-initials flex-shrink-0" style="width:${size}px;height:${size}px;font-size:${Math.round(size * 0.4)}px;background:hsl(${hash(name) % 360},55%,45%)">${esc(initials.toUpperCase() || '?')}</span>`;
}

export function stars(rating, showNumber = true) {
    rating = Number(rating) || 0;
    let html = `<span class="stars" title="${rating.toFixed(1)} out of 5">`;
    for (let i = 1; i <= 5; i++) {
        html += `<i class="bi ${rating >= i ? 'bi-star-fill' : rating >= i - 0.5 ? 'bi-star-half' : 'bi-star'}"></i>`;
    }
    if (showNumber) html += ` <span class="text-body-secondary small">${rating.toFixed(1)}</span>`;
    return html + '</span>';
}

export function statusBadge(status) {
    const map = {
        pending: 'warning', open: 'success', closed: 'secondary', rejected: 'danger', in_progress: 'primary',
        delivered: 'info', completed: 'success', cancelled: 'secondary', shortlisted: 'info', accepted: 'success',
        withdrawn: 'secondary', active: 'success', suspended: 'danger', new: 'warning', reviewed: 'secondary',
    };
    const label = String(status).replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    return `<span class="badge text-bg-${map[status] || 'secondary'}">${esc(label)}</span>`;
}

export function options(items, selected = '', assoc = false) {
    const entries = assoc ? Object.entries(items) : items.map(v => [v, v]);
    return entries.map(([v, label]) => `<option value="${esc(v)}"${String(v) === String(selected ?? '') ? ' selected' : ''}>${esc(label)}</option>`).join('');
}

export function distanceKm(lat1, lng1, lat2, lng2) {
    if ([lat1, lng1, lat2, lng2].some(v => v === null || v === undefined || v === '')) return null;
    const r = 6371, rad = d => d * Math.PI / 180;
    const dLat = rad(lat2 - lat1), dLng = rad(lng2 - lng1);
    const a = Math.sin(dLat / 2) ** 2 + Math.cos(rad(lat1)) * Math.cos(rad(lat2)) * Math.sin(dLng / 2) ** 2;
    return r * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

export function formatKm(km) {
    if (km === null || km === undefined) return '';
    if (km < 1) return 'under 1 km away';
    return (km < 10 ? km.toFixed(1) : Math.round(km)) + ' km away';
}

export function emptyState(icon, title, text = '', action = '') {
    return `<div class="empty-state"><i class="bi ${esc(icon)}"></i><h6 class="text-body">${esc(title)}</h6>${text ? `<p class="small mb-3">${esc(text)}</p>` : ''}${action}</div>`;
}

export const spinner = () => '<div class="text-center py-5"><div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading…</span></div></div>';

export function ratingPicker(name = 'rating') {
    let html = `<div class="rating-picker stars" role="radiogroup" aria-label="Rating"><input type="hidden" name="${esc(name)}" value="0">`;
    for (let i = 1; i <= 5; i++) html += `<i class="bi bi-star fs-3" data-value="${i}" role="radio" style="cursor:pointer" title="${i} star${i > 1 ? 's' : ''}"></i>`;
    return html + '</div>';
}

// ---------------------------------------------------------------- cards

export function serviceCard(s, rating) {
    const seller = s.seller || {};
    const hue = hash(s.category) % 360;
    const thumb = s.image_url
        ? `<img src="${esc(s.image_url)}" class="service-thumb" alt="">`
        : `<div class="service-thumb-placeholder" style="background:linear-gradient(135deg,hsl(${hue},55%,48%),hsl(${(hue + 40) % 360},60%,38%))"><i class="bi ${categoryIcon(s.category)}"></i></div>`;
    return `<div class="card h-100 card-hover position-relative">${thumb}
        <div class="card-body d-flex flex-column">
            <div class="d-flex align-items-center gap-2 mb-2">${avatar(seller, 26)}
                <span class="small fw-semibold text-truncate">${esc(seller.name)}</span>
                ${seller.is_verified ? '<i class="bi bi-patch-check-fill text-brand" title="Verified"></i>' : ''}</div>
            <a href="${link('service.html', { id: s.id })}" class="stretched-link stretched-title fw-semibold mb-2">${esc(s.title)}</a>
            <div class="small mb-2">${rating?.review_count ? `${stars(rating.rating)} <span class="text-body-secondary">(${rating.review_count})</span>` : '<span class="text-body-secondary">New seller</span>'}</div>
            <div class="mt-auto d-flex justify-content-between align-items-center pt-2 border-top">
                <span class="small text-body-secondary"><i class="bi bi-clock"></i> ${s.delivery_days} day${s.delivery_days > 1 ? 's' : ''}</span>
                <span class="small">From <strong>${money(s.price)}</strong></span>
            </div>
        </div></div>`;
}

export function jobCard(j, km = null) {
    const emp = j.employer || {};
    const uae = j.type === 'uae';
    return `<div class="card card-hover position-relative mb-3"><div class="card-body"><div class="d-flex gap-3">
        <div class="feature-icon flex-shrink-0 ${uae ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-warning-emphasis'}"><i class="bi ${categoryIcon(j.category)}"></i></div>
        <div class="flex-grow-1 min-w-0">
            <div class="d-flex flex-wrap justify-content-between gap-2">
                <a class="stretched-link stretched-title fw-bold" href="${link('job.html', { id: j.id })}">${esc(j.title)}</a>
                <span class="fw-semibold text-brand">${esc(payRange(j))}</span>
            </div>
            <div class="small text-body-secondary mb-2">
                ${esc(uae ? (j.company || emp.company_name || emp.name) : emp.name)}
                ${emp.is_verified ? '<i class="bi bi-patch-check-fill text-brand" title="Verified employer"></i>' : ''}
                · <i class="bi bi-geo-alt"></i> ${esc(place(j.town, j.county))}
                ${km !== null && km !== undefined ? ` · <strong class="text-body">${formatKm(km)}</strong>` : ''}
                · ${timeAgo(j.created_at)}
            </div>
            <p class="small mb-2 text-body-secondary">${esc(excerpt(j.description, 160))}</p>
            <div>
                <span class="chip">${esc(j.category)}</span>
                ${j.urgency === 'urgent' ? '<span class="chip bg-danger-subtle text-danger-emphasis"><i class="bi bi-lightning-fill"></i> Urgent</span>' : ''}
                ${uae && j.visa_sponsored ? '<span class="chip bg-primary-subtle text-primary-emphasis"><i class="bi bi-passport"></i> Visa sponsored</span>' : ''}
                ${uae && j.accommodation ? '<span class="chip bg-info-subtle text-info-emphasis"><i class="bi bi-house"></i> Accommodation</span>' : ''}
                ${j.status !== 'open' ? statusBadge(j.status) : ''}
                <span class="chip"><i class="bi bi-people"></i> ${j.applicant_count || 0} applied</span>
            </div>
        </div></div></div></div>`;
}

// Common selects with joined people
export const SERVICE_SELECT = '*, seller:profiles!services_user_id_fkey(id, name, avatar_url, is_verified, county)';
export const JOB_SELECT = '*, employer:profiles!jobs_employer_id_fkey(id, name, avatar_url, is_verified, company_name)';

export async function ratingsFor(ids) {
    const unique = [...new Set(ids.filter(Boolean))];
    if (!unique.length) return {};
    const { data } = await sb.from('user_ratings').select('*').in('reviewee_id', unique);
    return Object.fromEntries((data || []).map(r => [r.reviewee_id, r]));
}

// ---------------------------------------------------------------- feedback

export function flash(type, message) {
    const list = JSON.parse(sessionStorage.getItem('kh_flash') || '[]');
    list.push({ type, message });
    sessionStorage.setItem('kh_flash', JSON.stringify(list));
}

export function showAlert(type, message, target = '#alerts') {
    const box = $(target) || $('#flashArea');
    if (!box) return;
    box.insertAdjacentHTML('beforeend', `<div class="alert alert-${esc(type)} alert-dismissible fade show${box.id === 'flashArea' ? ' mt-3 mb-0' : ''}" role="alert">${esc(message)}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`);
    box.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
}

function showFlashes() {
    const list = JSON.parse(sessionStorage.getItem('kh_flash') || '[]');
    sessionStorage.removeItem('kh_flash');
    list.forEach(f => showAlert(f.type, f.message, '#flashArea'));
}

// Friendly text for database errors
export function errText(error) {
    const msg = error?.message || String(error || 'Something went wrong.');
    if (/row-level security|permission denied/i.test(msg)) return 'You are not allowed to do that.';
    if (/duplicate key/i.test(msg)) return 'That already exists.';
    if (/violates check constraint/i.test(msg)) return 'Please check the form: one of the values is not allowed.';
    if (/Failed to fetch|NetworkError/i.test(msg)) return 'Network problem. Check your internet connection and try again.';
    if (/Invalid login credentials/i.test(msg)) return 'Incorrect email or password.';
    if (/Email not confirmed/i.test(msg)) return 'Please confirm your email first. Check your inbox for the link.';
    return msg;
}

// Disable a button while an async action runs
export async function busy(button, fn) {
    const html = button?.innerHTML;
    if (button) { button.disabled = true; button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }
    try { return await fn(); } finally { if (button) { button.disabled = false; button.innerHTML = html; } }
}

export function go(url) { location.href = url; }

// Stop a page script quietly (e.g. after showing "not found")
export const halt = () => new Promise(() => {});

// ---------------------------------------------------------------- uploads

export async function uploadImage(bucket, file) {
    if (!file) return null;
    const allowed = { 'image/jpeg': 'jpg', 'image/png': 'png', 'image/webp': 'webp', 'image/gif': 'gif' };
    if (!allowed[file.type]) throw new Error('Please upload a JPG, PNG, WEBP or GIF image.');
    if (file.size > 2 * 1024 * 1024) throw new Error('Images must be smaller than 2 MB.');
    const path = `${state.user.id}/${Date.now()}-${Math.random().toString(36).slice(2, 8)}.${allowed[file.type]}`;
    const { error } = await sb.storage.from(bucket).upload(path, file, { contentType: file.type });
    if (error) throw error;
    return sb.storage.from(bucket).getPublicUrl(path).data.publicUrl;
}

// ---------------------------------------------------------------- layout

function headerHtml() {
    const me = state.profile;
    const page = location.pathname.split('/').pop() || 'index.html';
    const active = f => page === f ? ' active' : '';
    let right;
    if (me) {
        right = `
            ${me.role === 'employer' ? `<li class="nav-item"><a class="btn btn-brand btn-sm my-2 my-lg-0" href="job-post.html"><i class="bi bi-plus-lg"></i> Post a job</a></li>` : ''}
            ${me.role === 'worker' ? `<li class="nav-item"><a class="btn btn-outline-brand btn-sm my-2 my-lg-0" href="service-edit.html"><i class="bi bi-plus-lg"></i> New service</a></li>` : ''}
            <li class="nav-item"><a class="nav-link${active('dashboard.html')}" href="${me.role === 'admin' ? 'admin.html' : 'dashboard.html'}">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link position-relative${active('messages.html')}" href="messages.html" aria-label="Messages">
                <i class="bi bi-chat-dots fs-5 d-none d-lg-inline"></i><span class="d-lg-none">Messages</span>
                <span class="badge rounded-pill text-bg-danger nav-badge d-none" id="unreadBadge"></span></a></li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">${avatar(me, 32)} <span class="d-lg-none">${esc(me.name)}</span></a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><h6 class="dropdown-header">${esc(me.name)}<br><small class="text-body-secondary">${esc(me.role[0].toUpperCase() + me.role.slice(1))}</small></h6></li>
                    <li><a class="dropdown-item" href="${link('user.html', { id: me.id })}"><i class="bi bi-person me-2"></i>My public profile</a></li>
                    <li><a class="dropdown-item" href="profile.html"><i class="bi bi-gear me-2"></i>Edit profile</a></li>
                    <li><a class="dropdown-item" href="orders.html"><i class="bi bi-bag me-2"></i>Orders</a></li>
                    ${me.role === 'worker' ? `<li><a class="dropdown-item" href="my-services.html"><i class="bi bi-grid me-2"></i>My services</a></li>
                        <li><a class="dropdown-item" href="applications.html"><i class="bi bi-send me-2"></i>My applications</a></li>` : ''}
                    ${me.role !== 'worker' ? `<li><a class="dropdown-item" href="my-jobs.html"><i class="bi bi-briefcase me-2"></i>My job posts</a></li>` : ''}
                    ${me.role === 'admin' ? `<li><a class="dropdown-item" href="admin.html"><i class="bi bi-shield-lock me-2"></i>Admin panel</a></li>` : ''}
                    <li><hr class="dropdown-divider"></li>
                    <li><button class="dropdown-item text-danger" id="signOutBtn"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button></li>
                </ul>
            </li>`;
    } else {
        right = `<li class="nav-item"><a class="nav-link" href="login.html">Sign in</a></li>
            <li class="nav-item"><a class="btn btn-brand my-2 my-lg-0" href="register.html">Join KaziHub</a></li>`;
    }
    return `<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top"><div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.html"><span class="brand-mark"><i class="bi bi-briefcase-fill"></i></span><span class="fw-bold">Kazi<span class="text-brand">Hub</span></span></a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Menu"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto ms-lg-4 gap-lg-1">
                <li class="nav-item"><a class="nav-link${active('services.html')}" href="services.html">Freelance</a></li>
                <li class="nav-item"><a class="nav-link${active('juakali.html')}" href="juakali.html">Juakali Jobs</a></li>
                <li class="nav-item"><a class="nav-link${active('uae-jobs.html')}" href="uae-jobs.html">UAE Jobs</a></li>
            </ul>
            <ul class="navbar-nav align-items-lg-center gap-lg-2">${right}</ul>
        </div></div></nav><div class="container" id="flashArea"></div>`;
}

function footerHtml() {
    return `<footer class="site-footer mt-5"><div class="container py-5"><div class="row g-4">
        <div class="col-lg-4"><a class="d-flex align-items-center gap-2 text-white text-decoration-none mb-3" href="index.html"><span class="brand-mark"><i class="bi bi-briefcase-fill"></i></span><span class="fs-5 fw-bold">KaziHub</span></a>
            <p class="small mb-0">Connecting Kenyan talent with freelance gigs, local juakali work and verified opportunities in the UAE.</p></div>
        <div class="col-6 col-lg-2 offset-lg-1"><h6>Find work</h6><ul class="list-unstyled small">
            <li><a href="services.html">Freelance services</a></li><li><a href="juakali.html">Juakali jobs</a></li><li><a href="uae-jobs.html">UAE jobs</a></li></ul></div>
        <div class="col-6 col-lg-2"><h6>Hire</h6><ul class="list-unstyled small">
            <li><a href="job-post.html">Post a job</a></li><li><a href="juakali.html?view=workers">Workers near you</a></li><li><a href="services.html">Hire a freelancer</a></li></ul></div>
        <div class="col-6 col-lg-3"><h6>Support</h6><ul class="list-unstyled small">
            <li><a href="help.html">Help centre</a></li><li><a href="safety.html">Safety tips</a></li><li><a href="contact.html">Contact us</a></li></ul></div>
        </div><hr class="border-secondary my-4"><p class="small mb-0 text-center">&copy; ${new Date().getFullYear()} KaziHub. Made in Kenya.</p></div></footer>`;
}

export async function refreshUnread() {
    const badge = $('#unreadBadge');
    if (!badge || !state.user) return;
    const { count } = await sb.from('messages').select('id', { count: 'exact', head: true })
        .eq('receiver_id', state.user.id).eq('is_read', false);
    badge.textContent = count || '';
    badge.classList.toggle('d-none', !count);
}

function setupBanner() {
    return `<div class="container py-5"><div class="card border-warning"><div class="card-body p-4">
        <h1 class="h4 fw-bold"><i class="bi bi-plug text-accent"></i> Connect KaziHub to Supabase</h1>
        <p>This site needs a free Supabase project for accounts and data.</p>
        <ol class="mb-0"><li>Create a project at <a href="https://supabase.com" target="_blank" rel="noopener">supabase.com</a>.</li>
        <li>Run <code>supabase/schema.sql</code> in the Supabase SQL Editor.</li>
        <li>Put your Project URL and anon key in <code>assets/js/config.js</code> and push to GitHub.</li></ol>
    </div></div></div>`;
}

/**
 * Start a page. Options:
 *   auth: true -> must be signed in;  roles: ['worker'] -> limit to roles
 * Returns { user, profile } or never resolves if redirected.
 */
export async function init({ auth = false, roles = null } = {}) {
    const header = $('#site-header'), footer = $('#site-footer');
    if (footer) footer.innerHTML = footerHtml();
    if (!configured) {
        if (header) header.innerHTML = headerHtml();
        $('#main').innerHTML = setupBanner();
        return new Promise(() => {});
    }

    const { data: { session } } = await sb.auth.getSession();
    state.user = session?.user || null;
    if (state.user) {
        const { data } = await sb.from('profiles').select('*').eq('id', state.user.id).maybeSingle();
        state.profile = data;
        if (!data || data.status === 'suspended') {
            await sb.auth.signOut();
            state.user = state.profile = null;
            flash('danger', 'Your account is not active. Please contact support.');
        }
    }
    if (header) header.innerHTML = headerHtml();
    showFlashes();

    $('#signOutBtn')?.addEventListener('click', async () => {
        await sb.auth.signOut();
        flash('info', 'You have been signed out.');
        go('index.html');
    });

    if (auth && !state.user) {
        flash('warning', 'Please sign in to continue.');
        go(link('login.html', { next: location.pathname.split('/').pop() + location.search }));
        return new Promise(() => {});
    }
    if (roles && (!state.profile || !roles.includes(state.profile.role))) {
        flash('danger', 'You do not have access to that page.');
        go(state.profile?.role === 'admin' ? 'admin.html' : 'dashboard.html');
        return new Promise(() => {});
    }

    if (state.user) {
        refreshUnread();
        // Keep "last seen" fresh (at most every 2 minutes)
        const key = 'kh_seen_' + state.user.id;
        if (Date.now() - Number(localStorage.getItem(key) || 0) > 120000) {
            localStorage.setItem(key, Date.now());
            sb.from('profiles').update({ last_seen: new Date().toISOString() }).eq('id', state.user.id).then(() => {});
        }
        // Live unread badge
        sb.channel('unread-' + state.user.id)
            .on('postgres_changes', { event: 'INSERT', schema: 'public', table: 'messages', filter: `receiver_id=eq.${state.user.id}` }, () => refreshUnread())
            .subscribe();
    }
    return { user: state.user, profile: state.profile };
}

// ---------------------------------------------------------------- shared widgets

// "Use my location" buttons: data-geo-fill data-lat="#lat" data-lng="#lng" data-status="#geoStatus"
export function wireGeoButtons(root = document) {
    $$('[data-geo-fill]', root).forEach(btn => btn.addEventListener('click', () => {
        const status = $(btn.dataset.status || '#geoStatus');
        if (!navigator.geolocation) { if (status) status.textContent = 'Your browser does not support location.'; return; }
        if (status) status.textContent = 'Getting your location…';
        navigator.geolocation.getCurrentPosition(pos => {
            $(btn.dataset.lat).value = pos.coords.latitude.toFixed(6);
            $(btn.dataset.lng).value = pos.coords.longitude.toFixed(6);
            if (status) status.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> Location captured. Save to keep it.';
        }, () => { if (status) status.textContent = 'Could not get your location. Allow location access and try again.'; },
        { enableHighAccuracy: true, timeout: 10000 });
    }));
}

// "Near me": adds lat/lng to the URL and reloads
export function wireNearMe(root = document) {
    $$('[data-near-me]', root).forEach(btn => btn.addEventListener('click', e => {
        e.preventDefault();
        if (!navigator.geolocation) { alert('Your browser does not support location.'); return; }
        btn.classList.add('disabled');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Locating…';
        navigator.geolocation.getCurrentPosition(pos => {
            const u = new URL(location.href);
            u.searchParams.set('lat', pos.coords.latitude.toFixed(4));
            u.searchParams.set('lng', pos.coords.longitude.toFixed(4));
            u.searchParams.set('sort', 'near');
            u.searchParams.delete('page');
            location.href = u.toString();
        }, () => {
            btn.classList.remove('disabled');
            btn.innerHTML = '<i class="bi bi-geo-alt"></i> Near me';
            alert('Could not get your location. Please allow location access.');
        }, { timeout: 10000 });
    }));
}

export function wireRatingPickers(root = document) {
    $$('.rating-picker', root).forEach(picker => {
        const input = $('input[type=hidden]', picker);
        const icons = $$('i[data-value]', picker);
        const paint = v => icons.forEach(i => { i.className = (Number(i.dataset.value) <= v ? 'bi bi-star-fill' : 'bi bi-star') + ' fs-3'; });
        icons.forEach(i => {
            i.addEventListener('click', () => { input.value = i.dataset.value; paint(Number(i.dataset.value)); });
            i.addEventListener('mouseenter', () => paint(Number(i.dataset.value)));
        });
        picker.addEventListener('mouseleave', () => paint(Number(input.value) || 0));
    });
}

export function wirePreview(input, img) {
    input?.addEventListener('change', () => {
        if (input.files?.[0]) { img.src = URL.createObjectURL(input.files[0]); img.classList.remove('d-none'); }
    });
}

export function pagination(total, perPage, current) {
    const pages = Math.ceil(total / perPage);
    if (pages <= 1) return '';
    const u = new URL(location.href);
    let html = '<nav aria-label="Pages"><ul class="pagination justify-content-center mt-4">';
    for (let p = 1; p <= pages; p++) {
        if (p !== 1 && p !== pages && Math.abs(p - current) > 2) {
            if (Math.abs(p - current) === 3) html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
            continue;
        }
        u.searchParams.set('page', p);
        html += `<li class="page-item${p === current ? ' active' : ''}"><a class="page-link" href="${esc(u.search)}">${p}</a></li>`;
    }
    return html + '</ul></nav>';
}

export const currentPage = () => Math.max(1, parseInt(param('page', '1'), 10) || 1);

// Remove characters that have meaning in Supabase filter strings
export const cleanSearch = q => String(q || '').replace(/[,()%*\\:"]/g, ' ').trim().slice(0, 60);

export function setTitle(title) {
    document.title = title ? `${title} | KaziHub` : 'KaziHub – Find work. Find workers. Find success.';
}

export function profileCompletion(p) {
    let fields = ['name', 'county', 'town', 'bio', 'avatar_url'];
    if (p.role === 'worker') fields = fields.concat(['skills', 'experience_years', 'hourly_rate']);
    if (p.role === 'employer') fields.push('company_name');
    const done = fields.filter(f => p[f] !== null && p[f] !== undefined && p[f] !== '').length;
    return Math.round(done / fields.length * 100);
}

// Confirm dangerous actions globally: <button data-confirm="Sure?">
document.addEventListener('click', e => {
    const el = e.target.closest('[data-confirm]');
    if (el && !confirm(el.dataset.confirm)) { e.preventDefault(); e.stopImmediatePropagation(); }
}, true);
