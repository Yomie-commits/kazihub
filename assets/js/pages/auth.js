// Sign in, register, forgot password and reset password pages.
import { init, sb, state, $, esc, param, link, showAlert, errText, busy, go, flash } from '../app.js';

const page = document.body.dataset.page;
await init();
const main = $('#main');
const here = new URL('.', location.href).href;   // site base, e.g. https://user.github.io/kazihub/

function safeNext() {
    const n = param('next');
    return /^[a-z0-9\-]+\.html(\?[^\s]*)?$/i.test(n) ? n : null;
}
function card(title, sub, body) {
    return `<div class="container py-5"><div class="row justify-content-center"><div class="col-md-8 col-lg-6"><div class="card shadow-sm"><div class="card-body p-4 p-md-5">
        <h1 class="h3 fw-bold mb-1">${title}</h1><p class="text-body-secondary mb-4">${sub}</p><div id="alerts"></div>${body}</div></div></div></div></div>`;
}

if (page === 'login') {
    if (state.user) go(state.profile?.role === 'admin' ? 'admin.html' : 'dashboard.html');
    main.innerHTML = card('Welcome back', 'New to KaziHub? <a href="register.html">Create an account</a>', `
        <form id="f"><div class="mb-3"><label class="form-label" for="email">Email</label><input type="email" class="form-control form-control-lg" id="email" autocomplete="email" required autofocus></div>
        <div class="mb-2"><label class="form-label" for="password">Password</label><input type="password" class="form-control form-control-lg" id="password" autocomplete="current-password" required></div>
        <div class="text-end mb-4"><a href="forgot-password.html" class="small">Forgot your password?</a></div>
        <button class="btn btn-brand btn-lg w-100">Sign in</button></form>`);
    $('#f').addEventListener('submit', e => {
        e.preventDefault();
        busy(e.submitter, async () => {
            const { data, error } = await sb.auth.signInWithPassword({ email: $('#email').value.trim(), password: $('#password').value });
            if (error) return showAlert('danger', errText(error));
            const { data: p } = await sb.from('profiles').select('name, role, status').eq('id', data.user.id).single();
            if (!p || p.status === 'suspended') {
                await sb.auth.signOut();
                return showAlert('danger', 'This account has been suspended. Please contact support.');
            }
            flash('success', `Welcome back, ${p.name}!`);
            go(safeNext() || (p.role === 'admin' ? 'admin.html' : 'dashboard.html'));
        });
    });
}

if (page === 'register') {
    if (state.user) go('dashboard.html');
    const role = param('role') === 'employer' ? 'employer' : 'worker';
    main.innerHTML = card('Create your account', 'Already have one? <a href="login.html">Sign in</a>', `
        <form id="f" novalidate>
            <div class="mb-3"><label class="form-label">I want to…</label><div class="row g-2">
                <div class="col-6"><input type="radio" class="btn-check" name="role" id="role-worker" value="worker" ${role === 'worker' ? 'checked' : ''}>
                    <label class="btn btn-outline-brand w-100 py-3" for="role-worker"><i class="bi bi-person-workspace d-block fs-4"></i>Find work</label></div>
                <div class="col-6"><input type="radio" class="btn-check" name="role" id="role-employer" value="employer" ${role === 'employer' ? 'checked' : ''}>
                    <label class="btn btn-outline-brand w-100 py-3" for="role-employer"><i class="bi bi-building d-block fs-4"></i>Hire people</label></div>
            </div></div>
            <div class="mb-3"><label class="form-label" for="name">Full name</label><input class="form-control" id="name" autocomplete="name" maxlength="120" required><div class="invalid-feedback">Enter your full name.</div></div>
            <div class="row g-3 mb-3">
                <div class="col-sm-7"><label class="form-label" for="email">Email</label><input type="email" class="form-control" id="email" autocomplete="email" required><div class="invalid-feedback">Enter a valid email address.</div></div>
                <div class="col-sm-5"><label class="form-label" for="phone">Phone <span class="text-body-secondary fw-normal">(optional)</span></label><input type="tel" class="form-control" id="phone" placeholder="07xx xxx xxx" autocomplete="tel"><div class="invalid-feedback">e.g. 0712 345 678</div></div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-sm-6"><label class="form-label" for="password">Password</label><input type="password" class="form-control" id="password" autocomplete="new-password" required><div class="invalid-feedback">8+ characters with a letter and a number.</div><div class="form-text">8+ characters, with a letter and a number.</div></div>
                <div class="col-sm-6"><label class="form-label" for="password2">Confirm password</label><input type="password" class="form-control" id="password2" autocomplete="new-password" required><div class="invalid-feedback">Passwords do not match.</div></div>
            </div>
            <div class="form-check mb-4"><input class="form-check-input" type="checkbox" id="terms"><label class="form-check-label small" for="terms">I agree to the KaziHub terms and the <a href="safety.html" target="_blank">safety guidelines</a>.</label><div class="invalid-feedback">Please accept the terms to continue.</div></div>
            <button class="btn btn-brand w-100 btn-lg">Create account</button>
        </form>`);
    $('#f').addEventListener('submit', e => {
        e.preventDefault();
        const v = { name: $('#name').value.trim(), email: $('#email').value.trim().toLowerCase(), phone: $('#phone').value.trim(), password: $('#password').value };
        const checks = {
            name: v.name.length >= 2, email: /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v.email), phone: !v.phone || /^\+?[0-9 ]{9,16}$/.test(v.phone),
            password: v.password.length >= 8 && /[A-Za-z]/.test(v.password) && /\d/.test(v.password),
            password2: v.password === $('#password2').value, terms: $('#terms').checked,
        };
        Object.entries(checks).forEach(([k, ok]) => $('#' + k).classList.toggle('is-invalid', !ok));
        if (Object.values(checks).includes(false)) return;
        busy(e.submitter, async () => {
            const { data, error } = await sb.auth.signUp({
                email: v.email, password: v.password,
                options: { data: { name: v.name, phone: v.phone, role: $('input[name=role]:checked').value }, emailRedirectTo: here + 'login.html' },
            });
            if (error) return showAlert('danger', errText(error));
            if (data.session) {
                flash('success', `Welcome to KaziHub, ${v.name}! Complete your profile so people can find and trust you.`);
                go('profile.html');
            } else {
                main.innerHTML = card('Check your email', '', `<p>We sent a confirmation link to <strong>${esc(v.email)}</strong>. Click it to activate your account, then sign in.</p>
                    <p class="small text-body-secondary mb-0">Can't find it? Check your spam folder.</p>`);
            }
        });
    });
}

if (page === 'forgot') {
    main.innerHTML = card('Reset your password', "Enter your email and we'll send you a reset link.", `
        <form id="f"><div class="mb-4"><label class="form-label" for="email">Email</label><input type="email" class="form-control form-control-lg" id="email" required autofocus></div>
        <button class="btn btn-brand btn-lg w-100">Send reset link</button></form>
        <p class="small mt-4 mb-0"><a href="login.html">Back to sign in</a></p>`);
    $('#f').addEventListener('submit', e => {
        e.preventDefault();
        busy(e.submitter, async () => {
            const { error } = await sb.auth.resetPasswordForEmail($('#email').value.trim(), { redirectTo: here + 'reset-password.html' });
            if (error) return showAlert('danger', errText(error));
            $('#f').innerHTML = '<div class="alert alert-success mb-0">If that email has an account, a reset link is on its way. Check your inbox (and spam).</div>';
        });
    });
}

if (page === 'reset') {
    main.innerHTML = card('Choose a new password', 'Enter it twice to confirm.', `
        <form id="f"><div class="mb-3"><label class="form-label" for="pw">New password</label><input type="password" class="form-control" id="pw" autocomplete="new-password" required></div>
        <div class="mb-4"><label class="form-label" for="pw2">Confirm password</label><input type="password" class="form-control" id="pw2" autocomplete="new-password" required></div>
        <button class="btn btn-brand w-100">Save new password</button></form>`);
    $('#f').addEventListener('submit', e => {
        e.preventDefault();
        const pw = $('#pw').value;
        if (pw.length < 8 || !/[A-Za-z]/.test(pw) || !/\d/.test(pw)) return showAlert('danger', 'Use 8+ characters with a letter and a number.');
        if (pw !== $('#pw2').value) return showAlert('danger', 'Passwords do not match.');
        busy(e.submitter, async () => {
            const { data: { session } } = await sb.auth.getSession();
            if (!session) return showAlert('danger', 'This reset link has expired. Please request a new one.');
            const { error } = await sb.auth.updateUser({ password: pw });
            if (error) return showAlert('danger', errText(error));
            flash('success', 'Password updated. You are signed in.');
            go('dashboard.html');
        });
    });
}
