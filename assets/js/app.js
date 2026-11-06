/* KaziHub front-end helpers */
(function () {
    'use strict';

    // "Use my location" buttons: <button data-geo-fill data-lat="#lat" data-lng="#lng" data-status="#geoStatus">
    document.querySelectorAll('[data-geo-fill]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var status = document.querySelector(btn.dataset.status || '#geoStatus');
            if (!navigator.geolocation) {
                if (status) status.textContent = 'Your browser does not support location.';
                return;
            }
            if (status) status.textContent = 'Getting your location…';
            navigator.geolocation.getCurrentPosition(function (pos) {
                document.querySelector(btn.dataset.lat).value = pos.coords.latitude.toFixed(7);
                document.querySelector(btn.dataset.lng).value = pos.coords.longitude.toFixed(7);
                if (status) status.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> Location saved (' +
                    pos.coords.latitude.toFixed(4) + ', ' + pos.coords.longitude.toFixed(4) + ')';
            }, function () {
                if (status) status.textContent = 'Could not get your location. Allow location access and try again.';
            }, { enableHighAccuracy: true, timeout: 10000 });
        });
    });

    // "Near me" links: add lat/lng to the current URL
    document.querySelectorAll('[data-near-me]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (!navigator.geolocation) { alert('Your browser does not support location.'); return; }
            btn.classList.add('disabled');
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Locating…';
            navigator.geolocation.getCurrentPosition(function (pos) {
                var u = new URL(window.location.href);
                u.searchParams.set('lat', pos.coords.latitude.toFixed(5));
                u.searchParams.set('lng', pos.coords.longitude.toFixed(5));
                u.searchParams.set('sort', 'near');
                u.searchParams.delete('page');
                window.location.href = u.toString();
            }, function () {
                btn.classList.remove('disabled');
                btn.innerHTML = '<i class="bi bi-geo-alt"></i> Near me';
                alert('Could not get your location. Please allow location access.');
            }, { timeout: 10000 });
        });
    });

    // Confirm dangerous actions: <button data-confirm="Are you sure?">
    document.addEventListener('submit', function (e) {
        var btn = e.submitter;
        var msg = (btn && btn.dataset.confirm) || e.target.dataset.confirm;
        if (msg && !window.confirm(msg)) e.preventDefault();
    });

    // Image preview: <input type=file data-preview="#img">
    document.querySelectorAll('input[type=file][data-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            var img = document.querySelector(input.dataset.preview);
            if (img && input.files && input.files[0]) {
                img.src = URL.createObjectURL(input.files[0]);
                img.classList.remove('d-none');
            }
        });
    });

    // Star rating picker
    document.querySelectorAll('.rating-picker').forEach(function (picker) {
        var input = picker.querySelector('input[type=hidden]');
        var icons = picker.querySelectorAll('i[data-value]');
        function paint(v) {
            icons.forEach(function (i) {
                i.className = (parseInt(i.dataset.value, 10) <= v ? 'bi bi-star-fill' : 'bi bi-star') + ' fs-3';
            });
        }
        icons.forEach(function (i) {
            i.addEventListener('click', function () { input.value = i.dataset.value; paint(+i.dataset.value); });
            i.addEventListener('mouseenter', function () { paint(+i.dataset.value); });
        });
        picker.addEventListener('mouseleave', function () { paint(+input.value || 0); });
        paint(+input.value || 0);
    });

    // Job type toggle on post-job form
    var typeRadios = document.querySelectorAll('input[name=type]');
    function toggleType() {
        var checked = document.querySelector('input[name=type]:checked');
        if (!checked) return;
        document.querySelectorAll('[data-for-type]').forEach(function (el) {
            var show = el.dataset.forType.split(',').indexOf(checked.value) !== -1;
            el.classList.toggle('d-none', !show);
            el.querySelectorAll('input,select,textarea').forEach(function (f) { f.disabled = !show; });
        });
    }
    typeRadios.forEach(function (r) { r.addEventListener('change', toggleType); });
    toggleType();
})();
