<?php
require __DIR__ . '/includes/init.php';

$faqs = [
    'Getting started' => [
        ['Is KaziHub free?', 'Yes. Creating an account, listing services, posting jobs and applying for jobs are all free.'],
        ['What is the difference between a worker and an employer account?', 'Workers offer services and apply for jobs. Employers post juakali or UAE jobs and hire workers. Both can order freelance services.'],
        ['How do I get the verified badge?', 'Complete your profile, then send us a verification request from the Contact page. Our team will check your ID and contact details.'],
    ],
    'Freelance services' => [
        ['How do orders work?', 'The buyer places an order and describes what they need. The seller accepts, does the work and marks it delivered. The buyer then approves it (or asks for a revision) and leaves a review.'],
        ['How do I get paid?', 'For now, agree on payment (for example M-Pesa) with your client in KaziHub messages once the order is accepted. Keep all agreements in writing on KaziHub so we can help if something goes wrong.'],
    ],
    'Juakali jobs' => [
        ['How does “Near me” work?', 'Your browser shares your location once, and we sort jobs and workers by distance. Other users only ever see an approximate distance, never your exact position.'],
        ['How do I show that I am available?', 'Use the availability switch on your dashboard. Available workers appear in the “Available workers” list for employers nearby.'],
    ],
    'UAE jobs' => [
        ['Why do UAE jobs need approval?', 'To protect workers from fake overseas recruiters, every UAE listing is reviewed by our team before it is published, and again whenever it is edited.'],
        ['Will I be asked to pay?', 'No. Genuine employers do not ask workers to pay to apply. If anyone asks you for a fee, do not pay, and report the listing using the Report button.'],
    ],
];

$pageTitle = 'Help centre';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head py-5 mb-4 text-center">
    <div class="container">
        <h1 class="fw-bold">How can we help?</h1>
        <p class="text-body-secondary mb-0">Answers to the most common questions about KaziHub.</p>
    </div>
</div>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php $n = 0; foreach ($faqs as $section => $items): ?>
                <h5 class="fw-bold mt-4 mb-3"><?= e($section) ?></h5>
                <div class="accordion mb-3" id="acc<?= $n ?>">
                    <?php foreach ($items as $i => [$question, $answer]): $id = 'q' . $n . '_' . $i; ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $id ?>"><?= e($question) ?></button>
                            </h2>
                            <div id="<?= $id ?>" class="accordion-collapse collapse" data-bs-parent="#acc<?= $n ?>">
                                <div class="accordion-body text-body-secondary"><?= e($answer) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php $n++; endforeach; ?>
            <div class="card bg-brand-soft border-0 mt-5">
                <div class="card-body text-center p-4">
                    <h5 class="fw-bold">Still need help?</h5>
                    <a href="<?= e(url('contact.php')) ?>" class="btn btn-brand">Contact us</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
