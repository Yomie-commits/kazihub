<?php
/**
 * Reusable card renderers.
 */

/** $job needs: id,type,title,category,location,budget_*,pay_period,currency,created_at,is_verified,employer_name,client_name,visa_support,accommodation, optional distance */
function job_card(array $job): string
{
    ob_start(); ?>
    <div class="card card-hover h-100 position-relative">
        <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                <?= job_type_badge($job['type']) ?>
                <small class="text-muted text-nowrap"><?= e(time_ago($job['created_at'])) ?></small>
            </div>
            <h5 class="card-title fs-6 fw-bold mb-1">
                <a href="job.php?id=<?= (int)$job['id'] ?>" class="stretched-link text-reset text-decoration-none"><?= e($job['title']) ?></a>
            </h5>
            <div class="small-muted mb-2">
                <?= e($job['type'] === 'uae' && $job['employer_name'] ? $job['employer_name'] : ($job['client_name'] ?? '')) ?>
                <?php if ($job['type'] === 'uae' && $job['is_verified']): ?>
                    <i class="bi bi-patch-check-fill verified ms-1" title="Verified employer"></i>
                <?php endif; ?>
            </div>
            <div class="small text-muted mb-3">
                <span class="me-3"><i class="bi bi-tag me-1"></i><?= e($job['category']) ?></span>
                <?php if ($job['location']): ?><span><i class="bi bi-geo-alt me-1"></i><?= e($job['location']) ?></span><?php endif; ?>
                <?php if (isset($job['distance'])): ?>
                    <span class="badge bg-warning-subtle text-warning-emphasis ms-1"><?= number_format($job['distance'], 1) ?> km away</span>
                <?php endif; ?>
            </div>
            <div class="mt-auto d-flex justify-content-between align-items-center">
                <span class="fw-bold text-primary"><?= e(budget_label($job)) ?></span>
                <span class="small">
                    <?php if ($job['visa_support']): ?><span class="badge bg-light text-dark border" title="Visa provided"><i class="bi bi-passport"></i> Visa</span><?php endif; ?>
                    <?php if ($job['accommodation']): ?><span class="badge bg-light text-dark border" title="Accommodation provided"><i class="bi bi-house"></i> Housing</span><?php endif; ?>
                </span>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
}

/** $s needs: id,title,price,image,category,user_id,name,avatar,rating_avg,rating_total,is_verified */
function service_card(array $s): string
{
    ob_start(); ?>
    <div class="card card-hover h-100 overflow-hidden position-relative">
        <?php if (!empty($s['image'])): ?>
            <img src="<?= e($s['image']) ?>" class="service-thumb" alt="">
        <?php else: ?>
            <div class="service-thumb-placeholder"><i class="bi bi-stars"></i></div>
        <?php endif; ?>
        <div class="card-body d-flex flex-column">
            <div class="d-flex align-items-center gap-2 mb-2">
                <?= avatar($s, 28) ?>
                <span class="small fw-semibold text-truncate"><?= e($s['name']) ?></span>
                <?php if ($s['is_verified']): ?><i class="bi bi-patch-check-fill verified small" title="Verified"></i><?php endif; ?>
            </div>
            <a href="service.php?id=<?= (int)$s['id'] ?>" class="stretched-link text-reset text-decoration-none fw-semibold mb-2"><?= e($s['title']) ?></a>
            <div class="small mb-3">
                <?php if ($s['rating_total']): ?>
                    <i class="bi bi-star-fill text-warning"></i> <strong><?= e(number_format((float)$s['rating_avg'], 1)) ?></strong>
                    <span class="text-muted">(<?= (int)$s['rating_total'] ?>)</span>
                <?php else: ?>
                    <span class="text-muted">New seller</span>
                <?php endif; ?>
            </div>
            <div class="mt-auto border-top pt-2 d-flex justify-content-between align-items-center">
                <span class="small text-muted text-uppercase">From</span>
                <span class="fw-bold"><?= e(money($s['price'])) ?></span>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
}

/** SQL fragment used to list services with seller + rating. */
function services_select_sql(): string
{
    return 'SELECT s.*, u.name, u.avatar, u.is_verified,
                   (SELECT AVG(rating) FROM reviews r WHERE r.reviewee_id = u.id) AS rating_avg,
                   (SELECT COUNT(*) FROM reviews r WHERE r.reviewee_id = u.id) AS rating_total
            FROM services s JOIN users u ON u.id = s.user_id';
}

/** SQL fragment used to list jobs with poster name. */
function jobs_select_sql(): string
{
    return 'SELECT j.*, COALESCE(u.company_name, u.name) AS client_name
            FROM jobs j JOIN users u ON u.id = j.client_id';
}
