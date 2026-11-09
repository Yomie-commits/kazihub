import { init, sb, state, $, esc, param, options, showAlert, errText, busy } from '../app.js';

await init();
const me = state.profile;
const subjects = ['General question', 'Verification request', 'Report a problem', 'Partnership', 'Other'];
const subject = subjects.includes(param('subject')) ? param('subject') : 'General question';

$('#main').innerHTML = `<div class="container py-5"><div class="row g-4 g-lg-5 justify-content-center">
    <div class="col-lg-4"><h1 class="h3 fw-bold">Contact us</h1>
        <p class="text-body-secondary">Questions, verification requests or problems with a listing: we're here to help.</p>
        <ul class="list-unstyled small">
            <li class="mb-2"><i class="bi bi-geo-alt text-brand me-2"></i>Nairobi, Kenya</li>
            <li class="mb-2"><i class="bi bi-clock text-brand me-2"></i>Mon–Sat, 8am–6pm EAT</li>
            <li class="mb-2"><i class="bi bi-question-circle text-brand me-2"></i><a href="help.html">Read the help centre</a></li>
        </ul></div>
    <div class="col-lg-6"><div id="alerts"></div><form id="f" class="card" novalidate><div class="card-body p-4"><div class="row g-3">
        <div class="d-none"><label>Website <input id="website" tabindex="-1" autocomplete="off"></label></div>
        <div class="col-md-6"><label class="form-label" for="name">Name</label><input class="form-control" id="name" maxlength="120" value="${esc(me?.name || '')}" required><div class="invalid-feedback">Enter your name.</div></div>
        <div class="col-md-6"><label class="form-label" for="email">Email</label><input type="email" class="form-control" id="email" maxlength="190" value="${esc(state.user?.email || '')}" required><div class="invalid-feedback">Enter a valid email.</div></div>
        <div class="col-12"><label class="form-label" for="subject">Subject</label><select class="form-select" id="subject">${options(subjects, subject)}</select></div>
        <div class="col-12"><label class="form-label" for="message">Message</label><textarea class="form-control" id="message" rows="6" maxlength="5000" required></textarea><div class="invalid-feedback">Your message is too short.</div></div>
    </div></div><div class="card-footer bg-white p-3 text-end"><button class="btn btn-brand px-4">Send message</button></div></form></div>
</div></div>`;

$('#f').addEventListener('submit', e => {
    e.preventDefault();
    if ($('#website').value) return;   // bot trap
    const v = { name: $('#name').value.trim(), email: $('#email').value.trim(), subject: $('#subject').value, message: $('#message').value.trim() };
    const checks = { name: v.name.length >= 2, email: /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v.email), message: v.message.length >= 10 };
    Object.entries(checks).forEach(([k, ok]) => $('#' + k).classList.toggle('is-invalid', !ok));
    if (Object.values(checks).includes(false)) return;
    const last = Number(sessionStorage.getItem('kh_contact') || 0);
    if (Date.now() - last < 60000) return showAlert('warning', 'Please wait a minute before sending another message.');
    busy(e.submitter, async () => {
        const { error } = await sb.from('contact_messages').insert(v);
        if (error) return showAlert('danger', errText(error));
        sessionStorage.setItem('kh_contact', Date.now());
        $('#f').outerHTML = '<div class="alert alert-success">Thanks! We received your message and will get back to you soon.</div>';
    });
});
