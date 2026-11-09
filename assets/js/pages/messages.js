import { init, sb, state, $, esc, param, link, avatar, timeAgo, shortDate, excerpt, isOnline, emptyState, showAlert, errText, refreshUnread, setTitle } from '../app.js';

const { user } = await init({ auth: true });
const withId = param('with');
let partner = null;
if (withId && withId !== user.id) {
    ({ data: partner } = await sb.from('profiles').select('id, name, avatar_url, role, is_verified, last_seen, status').eq('id', withId).maybeSingle());
}
if (partner) setTitle('Chat with ' + partner.name);

$('#main').innerHTML = `<div class="container py-4">
    <h1 class="h3 fw-bold mb-3">Messages</h1><div id="alerts"></div>
    <div class="card overflow-hidden"><div class="row g-0">
        <div class="col-md-4 border-end ${partner ? 'd-none d-md-block' : ''}"><div class="list-group list-group-flush chat-list" id="convos"></div></div>
        <div class="col-md-8 ${partner ? '' : 'd-none d-md-block'}" id="thread"></div>
    </div></div></div>`;

async function loadConvos() {
    const { data: convos } = await sb.rpc('my_conversations');
    const list = convos || [];
    let html = '';
    if (partner && !list.some(c => c.partner_id === partner.id)) {
        html += `<a href="#" class="list-group-item list-group-item-action active d-flex gap-2 align-items-center py-3">${avatar(partner, 42)}
            <div class="fw-semibold">${esc(partner.name)}<div class="small text-body-secondary fw-normal">New conversation</div></div></a>`;
    }
    html += list.map(c => `<a href="${link('messages.html', { with: c.partner_id })}" class="list-group-item list-group-item-action d-flex gap-2 align-items-center py-3 ${partner?.id === c.partner_id ? 'active' : ''}">
        <div class="position-relative">${avatar(c, 42)}${isOnline(c.last_seen) ? '<span class="online-dot position-absolute bottom-0 end-0"></span>' : ''}</div>
        <div class="flex-grow-1 min-w-0">
            <div class="d-flex justify-content-between"><span class="fw-semibold text-truncate">${esc(c.name)}</span><small class="text-body-secondary text-nowrap ms-2">${timeAgo(c.last_at)}</small></div>
            <div class="d-flex justify-content-between"><small class="text-body-secondary text-truncate">${c.last_from_me ? 'You: ' : ''}${esc(excerpt(c.last_body, 60))}</small>
                ${c.unread > 0 ? `<span class="badge rounded-pill text-bg-danger ms-2">${c.unread}</span>` : ''}</div>
        </div></a>`).join('');
    $('#convos').innerHTML = html || emptyState('bi-chat-dots', 'No messages yet', 'Start a conversation from someone’s profile, a service or a job.');
}

let lastId = 0;
function bubble(m) {
    return `<div class="bubble ${m.sender_id === user.id ? 'me' : 'them'}" data-id="${m.id}">${esc(m.body)}<time>${shortDate(m.created_at, true)}</time></div>`;
}
function append(list) {
    const win = $('#chatWindow');
    if (!win) return;
    const atBottom = win.scrollHeight - win.scrollTop - win.clientHeight < 80;
    let added = false;
    list.forEach(m => {
        if (m.id <= lastId || win.querySelector(`[data-id="${m.id}"]`)) return;
        $('#chatEmpty')?.remove();
        win.insertAdjacentHTML('beforeend', bubble(m));
        lastId = Math.max(lastId, m.id);
        added = true;
    });
    if (added && (atBottom || list[list.length - 1]?.sender_id === user.id)) win.scrollTop = win.scrollHeight;
}

async function loadThread() {
    if (!partner) {
        $('#thread').innerHTML = `<div class="d-flex align-items-center justify-content-center h-100 py-5">${emptyState('bi-chat-left-text', 'Select a conversation')}</div>`;
        return;
    }
    $('#thread').innerHTML = `
        <div class="d-flex align-items-center gap-2 p-3 border-bottom bg-white">
            <a href="messages.html" class="btn btn-sm btn-light d-md-none" aria-label="Back"><i class="bi bi-arrow-left"></i></a>${avatar(partner, 40)}
            <div><a href="${link('user.html', { id: partner.id })}" class="fw-semibold text-body text-decoration-none">${esc(partner.name)}</a>
                ${partner.is_verified ? '<i class="bi bi-patch-check-fill text-brand"></i>' : ''}
                <div class="small text-body-secondary">${isOnline(partner.last_seen) ? '<span class="online-dot"></span> Online' : 'Last seen ' + (timeAgo(partner.last_seen) || 'a while ago')}</div></div>
        </div>
        <div class="chat-window p-3" id="chatWindow"><p class="text-center text-body-secondary small my-5" id="chatEmpty">Say hello 👋 Keep payments and agreements on KaziHub for your safety.</p></div>
        ${partner.status === 'active' ? `<form class="d-flex gap-2 p-3 border-top bg-white" id="chatForm">
            <textarea class="form-control" id="chatInput" rows="1" placeholder="Write a message…" required maxlength="4000" style="resize:none"></textarea>
            <button class="btn btn-brand px-3" aria-label="Send"><i class="bi bi-send-fill"></i></button></form>`
          : '<div class="p-3 border-top small text-body-secondary">This account is no longer active.</div>'}`;

    const { data } = await sb.from('messages').select('*')
        .or(`and(sender_id.eq.${user.id},receiver_id.eq.${partner.id}),and(sender_id.eq.${partner.id},receiver_id.eq.${user.id})`)
        .order('id', { ascending: false }).limit(200);
    append((data || []).reverse());
    await sb.rpc('mark_read', { p_partner: partner.id });
    refreshUnread();
    loadConvos();

    const input = $('#chatInput'), form = $('#chatForm');
    input?.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); } });
    form?.addEventListener('submit', async e => {
        e.preventDefault();
        const body = input.value.trim();
        if (!body) return;
        input.value = '';
        const { data: msg, error } = await sb.from('messages').insert({ receiver_id: partner.id, body }).select().single();
        if (error) { input.value = body; return showAlert('danger', errText(error)); }
        append([msg]);
        loadConvos();
    });
    input?.focus();
}

await loadConvos();
await loadThread();

// Live updates: new messages to me
sb.channel('inbox-' + user.id)
    .on('postgres_changes', { event: 'INSERT', schema: 'public', table: 'messages', filter: `receiver_id=eq.${user.id}` }, async ({ new: m }) => {
        if (partner && m.sender_id === partner.id) {
            append([m]);
            await sb.rpc('mark_read', { p_partner: partner.id });
            refreshUnread();
        }
        loadConvos();
    })
    .subscribe();

// Fallback refresh every 15 s in case the live connection drops
setInterval(async () => {
    if (!partner) return loadConvos();
    const { data } = await sb.from('messages').select('*').eq('sender_id', partner.id).eq('receiver_id', user.id).gt('id', lastId).order('id');
    if (data?.length) { append(data); sb.rpc('mark_read', { p_partner: partner.id }).then(refreshUnread); }
}, 15000);
