</main>

<footer class="site-footer mt-5">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-4">
                <a class="d-flex align-items-center gap-2 text-white text-decoration-none mb-3" href="<?= e(url('index.php')) ?>">
                    <span class="brand-mark"><i class="bi bi-briefcase-fill"></i></span>
                    <span class="fs-5 fw-bold">KaziHub</span>
                </a>
                <p class="small mb-0">Connecting Kenyan talent with freelance gigs, local juakali work and verified opportunities in the UAE.</p>
            </div>
            <div class="col-6 col-lg-2 offset-lg-1">
                <h6 class="text-white">Find work</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= e(url('services.php')) ?>">Freelance services</a></li>
                    <li><a href="<?= e(url('juakali.php')) ?>">Juakali jobs</a></li>
                    <li><a href="<?= e(url('uae-jobs.php')) ?>">UAE jobs</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="text-white">Hire</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= e(url('job-post.php')) ?>">Post a job</a></li>
                    <li><a href="<?= e(url('juakali.php', ['view' => 'workers'])) ?>">Workers near you</a></li>
                    <li><a href="<?= e(url('services.php')) ?>">Hire a freelancer</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-3">
                <h6 class="text-white">Support</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= e(url('help.php')) ?>">Help centre</a></li>
                    <li><a href="<?= e(url('safety.php')) ?>">Safety tips</a></li>
                    <li><a href="<?= e(url('contact.php')) ?>">Contact us</a></li>
                </ul>
            </div>
        </div>
        <hr class="border-secondary my-4">
        <p class="small mb-0 text-center">&copy; <?= date('Y') ?> KaziHub. Made in Kenya.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($useMap)): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php endif; ?>
<script src="<?= e(url('assets/js/app.js')) ?>?v=1"></script>
<?php if (!empty($pageScript)) echo $pageScript; ?>
</body>
</html>
