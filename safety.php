<?php
require __DIR__ . '/includes/init.php';

$tips = [
    ['bi-cash-coin', 'Never pay to get a job', 'Real employers and licensed agencies do not charge workers application, “processing”, medical or visa fees before a written offer. If someone asks, stop and report them.'],
    ['bi-patch-check', 'Check the agency', 'For overseas jobs, confirm the recruitment agency is licensed by Kenya\'s National Employment Authority (NEA) before sharing documents.'],
    ['bi-chat-square-text', 'Keep it on KaziHub', 'Agree on work, price and timelines in KaziHub messages. Written records help us resolve disputes.'],
    ['bi-person-vcard', 'Protect your documents', 'Only share copies of your ID or passport after you have a written offer, and never hand over your original passport to an agent in Kenya.'],
    ['bi-geo', 'Meet safely for local jobs', 'For juakali work, tell a friend where you are going, confirm the job details in messages first, and trust your instincts.'],
    ['bi-flag', 'Report anything suspicious', 'Use the Report button on any listing, or contact us. Our team reviews every report.'],
];

$pageTitle = 'Safety tips';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head py-5 mb-4 text-center">
    <div class="container">
        <i class="bi bi-shield-check text-brand display-5"></i>
        <h1 class="fw-bold mt-2">Stay safe on KaziHub</h1>
        <p class="text-body-secondary mb-0">Simple rules that protect you from scams and bad deals.</p>
    </div>
</div>
<div class="container">
    <div class="row g-4">
        <?php foreach ($tips as [$icon, $title, $text]): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100"><div class="card-body p-4">
                    <div class="feature-icon bg-brand-soft text-brand mb-3"><i class="bi <?= $icon ?>"></i></div>
                    <h5 class="fw-bold"><?= e($title) ?></h5>
                    <p class="text-body-secondary small mb-0"><?= e($text) ?></p>
                </div></div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="warning-box p-4 mt-5 text-center">
        <h5 class="fw-bold">Think you've been targeted by a scam?</h5>
        <p class="small mb-3">Stop all payments, keep your messages, and tell us right away.</p>
        <a href="<?= e(url('contact.php', ['subject' => 'Report a problem'])) ?>" class="btn btn-accent">Report a problem</a>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
