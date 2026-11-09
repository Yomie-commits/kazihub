import { init, sb, $, esc, link, avatar, options, profileCompletion, showAlert, errText, busy, flash, uploadImage, wireGeoButtons } from '../app.js';
import { KENYA_COUNTIES } from '../data.js';

const { user, profile: me } = await init({ auth: true });
const { data: priv } = await sb.from('profile_private').select('phone').eq('id', user.id).maybeSingle();
const completion = profileCompletion(me);
const worker = me.role === 'worker';

$('#main').innerHTML = `<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div><h1 class="h3 fw-bold mb-0">Your profile</h1><p class="text-body-secondary mb-0">A complete profile gets more work. <a href="${link('user.html', { id: me.id })}">See your public profile</a></p></div>
        <div style="min-width:220px"><div class="d-flex justify-content-between small"><span>Profile strength</span><strong>${completion}%</strong></div>
            <div class="progress" style="height:8px"><div class="progress-bar bg-brand" style="width:${completion}%"></div></div></div>
    </div>
    <div id="alerts"></div>
    <div class="row g-4">
        <div class="col-lg-8"><form id="profileForm" class="card" novalidate><div class="card-body p-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div id="avatarWrap">${avatar(me, 80)}</div>
                <img id="avatarPreview" class="rounded-circle object-fit-cover d-none" width="80" height="80" alt="">
                <div><label class="form-label mb-1" for="avatar">Profile photo</label>
                    <input class="form-control form-control-sm" type="file" id="avatar" accept="image/*">
                    <div class="form-text">A clear photo of your face builds trust. Max 2 MB.</div></div>
            </div>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="name">Full name</label><input class="form-control" id="name" maxlength="120" value="${esc(me.name)}" required><div class="invalid-feedback">Enter your full name.</div></div>
                <div class="col-md-6"><label class="form-label" for="phone">Phone <span class="text-body-secondary fw-normal">(private)</span></label>
                    <input class="form-control" id="phone" value="${esc(priv?.phone || '')}" placeholder="07xx xxx xxx"><div class="invalid-feedback">Enter a valid phone number.</div>
                    <div class="form-text">Only shown to employers who accept you for a job.</div></div>
                <div class="col-md-6"><label class="form-label" for="county">County</label><select class="form-select" id="county"><option value="">Choose…</option>${options(KENYA_COUNTIES, me.county)}</select></div>
                <div class="col-md-6"><label class="form-label" for="town">Town / area</label><input class="form-control" id="town" maxlength="100" value="${esc(me.town || '')}" placeholder="e.g. Kasarani"></div>
                <div class="col-12"><label class="form-label">Location <span class="text-body-secondary fw-normal">(for “near me” matching)</span></label>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-geo-fill data-lat="#latitude" data-lng="#longitude" data-status="#geoStatus"><i class="bi bi-crosshair"></i> Use my current location</button>
                        <span id="geoStatus" class="small text-body-secondary">${me.latitude !== null ? '<i class="bi bi-check-circle-fill text-success"></i> Location set' : 'Not set'}</span></div>
                    <input type="hidden" id="latitude" value="${esc(me.latitude ?? '')}"><input type="hidden" id="longitude" value="${esc(me.longitude ?? '')}">
                    <div class="form-text">Saved to about 1 km, so others only see a rough distance, never your exact home.</div></div>
                <div class="col-12"><label class="form-label" for="bio">About you</label>
                    <textarea class="form-control" id="bio" rows="4" maxlength="1500" placeholder="${worker ? 'Describe your experience, what you do best and where you work.' : 'Tell workers about you or your business.'}">${esc(me.bio || '')}</textarea></div>
                ${worker ? `
                <div class="col-12"><label class="form-label" for="skills">Skills</label><input class="form-control" id="skills" value="${esc(me.skills || '')}" placeholder="e.g. Plumbing, Tiling, Pipe fitting">
                    <div class="form-text">Separate skills with commas. Up to 15.</div></div>
                <div class="col-md-6"><label class="form-label" for="experience">Years of experience</label><input type="number" min="0" max="60" class="form-control" id="experience" value="${esc(me.experience_years ?? '')}"></div>
                <div class="col-md-6"><label class="form-label" for="rate">Rate per hour (KES)</label><input type="number" min="0" step="50" class="form-control" id="rate" value="${me.hourly_rate !== null ? Math.round(me.hourly_rate) : ''}"></div>
                <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="available" ${me.is_available ? 'checked' : ''}><label class="form-check-label" for="available">I'm available for work now</label></div></div>`
                : `<div class="col-12"><label class="form-label" for="company">Company / business name <span class="text-body-secondary fw-normal">(optional)</span></label><input class="form-control" id="company" maxlength="150" value="${esc(me.company_name || '')}"></div>`}
            </div>
        </div><div class="card-footer bg-white text-end p-3"><button class="btn btn-brand px-4">Save profile</button></div></form></div>
        <div class="col-lg-4">
            <div class="card mb-4"><div class="card-body"><h6 class="fw-bold">Verification</h6>
                ${me.is_verified ? '<p class="small mb-0"><i class="bi bi-patch-check-fill text-brand"></i> Your account is verified.</p>'
                  : '<p class="small text-body-secondary">Verified accounts get a badge and appear higher in search. Send us a message and we\'ll check your ID.</p><a class="btn btn-sm btn-outline-brand" href="contact.html?subject=Verification+request">Request verification</a>'}
            </div></div>
            <form id="pwForm" class="card"><div class="card-body"><h6 class="fw-bold mb-3">Change password</h6>
                <div class="mb-2"><input type="password" class="form-control" id="newPw" placeholder="New password" autocomplete="new-password" required></div>
                <div class="mb-3"><input type="password" class="form-control" id="newPw2" placeholder="Confirm new password" autocomplete="new-password" required></div>
                <button class="btn btn-outline-secondary w-100">Update password</button></div></form>
        </div>
    </div></div>`;

wireGeoButtons();
$('#avatar').addEventListener('change', e => {
    const file = e.target.files[0];
    if (!file) return;
    $('#avatarPreview').src = URL.createObjectURL(file);
    $('#avatarPreview').classList.remove('d-none');
    $('#avatarWrap').classList.add('d-none');
});

$('#profileForm').addEventListener('submit', async e => {
    e.preventDefault();
    const name = $('#name').value.trim(), phone = $('#phone').value.trim();
    const okName = name.length >= 2, okPhone = !phone || /^\+?[0-9 ]{9,16}$/.test(phone);
    $('#name').classList.toggle('is-invalid', !okName);
    $('#phone').classList.toggle('is-invalid', !okPhone);
    if (!okName || !okPhone) return;

    const d = { name, county: $('#county').value || null, town: $('#town').value.trim() || null, bio: $('#bio').value.trim() || null };
    const lat = $('#latitude').value, lng = $('#longitude').value;
    if (lat !== '' && lng !== '') { d.latitude = Number(lat); d.longitude = Number(lng); }
    if (worker) {
        const skills = [...new Set($('#skills').value.split(',').map(s => s.trim().slice(0, 40)).filter(Boolean))].slice(0, 15);
        d.skills = skills.join(', ') || null;
        d.experience_years = $('#experience').value === '' ? null : Math.max(0, Math.min(60, parseInt($('#experience').value, 10)));
        d.hourly_rate = $('#rate').value === '' ? null : Math.max(0, Number($('#rate').value));
        d.is_available = $('#available').checked;
    } else {
        d.company_name = $('#company').value.trim() || null;
    }
    await busy(e.submitter, async () => {
        try {
            const file = $('#avatar').files[0];
            if (file) d.avatar_url = await uploadImage('avatars', file);
            const { error } = await sb.from('profiles').update(d).eq('id', user.id);
            if (error) throw error;
            const { error: pErr } = await sb.from('profile_private').upsert({ id: user.id, phone: phone || null });
            if (pErr) throw pErr;
            flash('success', 'Profile saved.');
            location.reload();
        } catch (err) {
            showAlert('danger', errText(err));
        }
    });
});

$('#pwForm').addEventListener('submit', async e => {
    e.preventDefault();
    const pw = $('#newPw').value;
    if (pw.length < 8 || !/[A-Za-z]/.test(pw) || !/\d/.test(pw)) return showAlert('danger', 'New password needs 8+ characters with a letter and a number.');
    if (pw !== $('#newPw2').value) return showAlert('danger', 'New passwords do not match.');
    await busy(e.submitter, async () => {
        const { error } = await sb.auth.updateUser({ password: pw });
        if (error) return showAlert('danger', errText(error));
        e.target.reset();
        showAlert('success', 'Password updated.');
    });
});
