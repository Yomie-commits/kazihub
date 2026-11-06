<?php
/**
 * Reusable HTML pieces.
 */

/** SQL select for services with seller info + rating. */
const SERVICE_SELECT = "SELECT s.*, u.name AS seller_name, u.avatar AS seller_avatar, u.is_verified AS seller_verified,
        u.county AS seller_county, u.last_seen AS seller_last_seen,
        (SELECT AVG(r.rating) FROM reviews r WHERE r.reviewee_id = u.id) AS seller_rating,
        (SELECT COUNT(*) FROM reviews r WHERE r.reviewee_id = u.id) AS seller_reviews
    FROM services s JOIN users u ON u.id = s.user_id";

/** SQL select for jobs with employer info + applicant count. */
const JOB_SELECT = "SELECT j.*, u.name AS employer_name, u.avatar AS employer_avatar, u.is_verified AS employer_verified,
        u.company_name AS employer_company,
        (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS applicant_count
    FROM jobs j JOIN users u ON u.id = j.employer_id";

function category_icon(string $category): string
{
    $icons = [
        'Graphic Design' => 'palette', 'Web Development' => 'code-slash', 'Mobile Apps' => 'phone',
        'Writing & Translation' => 'pencil', 'Digital Marketing' => 'graph-up-arrow',
        'Social Media Management' => 'instagram', 'Video & Animation' => 'camera-reels',
        'Virtual Assistance' => 'headset', 'Data Entry & Annotation' => 'table', 'Photography' => 'camera',
        'Music & Audio' => 'music-note-beamed', 'Accounting & Finance' => 'calculator', 'Tutoring' => 'mortarboard',
        'Plumbing' => 'droplet', 'Electrical' => 'lightning-charge', 'Carpentry' => 'hammer',
        'Masonry & Construction' => 'bricks', 'Welding & Metalwork' => 'fire', 'Painting' => 'paint-bucket',
        'Mechanic' => 'wrench', 'Cleaning & Laundry' => 'stars', 'House Help' => 'house-heart',
        'Tailoring' => 'scissors', 'Hair & Beauty' => 'brush', 'Moving & Loading' => 'truck',
        'Gardening' => 'flower1', 'Boda Boda & Delivery' => 'bicycle', 'Security' => 'shield-check',
        'Catering' => 'cup-hot', 'Hospitality' => 'building', 'Construction' => 'cone-striped',
        'Driving' => 'car-front', 'Domestic Work' => 'house-heart', 'Retail & Sales' => 'shop',
        'Healthcare' => 'heart-pulse', 'Cleaning' => 'stars', 'Office & Admin' => 'briefcase', 'IT' => 'pc-display',
    ];
    return 'bi-' . ($icons[$category] ?? 'grid');
}

function service_card(array $s): string
{
    ob_start();
    $hue = crc32($s['category']) % 360;
    ?>
    <div class="card h-100 card-hover position-relative">
        <?php if (!empty($s['image'])): ?>
            <img src="<?= e(url($s['image'])) ?>" class="service-thumb" alt="">
        <?php else: ?>
            <div class="service-thumb-placeholder" style="background:linear-gradient(135deg,hsl(<?= $hue ?>,55%,48%),hsl(<?= ($hue + 40) % 360 ?>,60%,38%))">
                <i class="bi <?= category_icon($s['category']) ?>"></i>
            </div>
        <?php endif; ?>
        <div class="card-body d-flex flex-column">
            <div class="d-flex align-items-center gap-2 mb-2">
                <?= avatar(['name' => $s['seller_name'], 'avatar' => $s['seller_avatar']], 26) ?>
                <span class="small fw-semibold text-truncate"><?= e($s['seller_name']) ?></span>
                <?php if ($s['seller_verified']): ?><i class="bi bi-patch-check-fill text-brand" title="Verified"></i><?php endif; ?>
            </div>
            <a href="<?= e(url('service.php', ['id' => $s['id']])) ?>" class="stretched-link stretched-title fw-semibold mb-2"><?= e($s['title']) ?></a>
            <div class="small mb-2">
                <?php if ($s['seller_reviews'] > 0): ?>
                    <?= stars((float) $s['seller_rating']) ?> <span class="text-body-secondary">(<?= (int) $s['seller_reviews'] ?>)</span>
                <?php else: ?>
                    <span class="text-body-secondary">New seller</span>
                <?php endif; ?>
            </div>
            <div class="mt-auto d-flex justify-content-between align-items-center pt-2 border-top">
                <span class="small text-body-secondary"><i class="bi bi-clock"></i> <?= (int) $s['delivery_days'] ?> day<?= $s['delivery_days'] > 1 ? 's' : '' ?></span>
                <span class="small">From <strong><?= e(money($s['price'])) ?></strong></span>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function job_card(array $j, ?float $km = null): string
{
    ob_start();
    $isUae = $j['type'] === 'uae';
    ?>
    <div class="card card-hover position-relative mb-3">
        <div class="card-body">
            <div class="d-flex gap-3">
                <div class="feature-icon flex-shrink-0 <?= $isUae ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-warning-emphasis' ?>">
                    <i class="bi <?= category_icon($j['category']) ?>"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <a class="stretched-link stretched-title fw-bold fs-6" href="<?= e(url('job.php', ['id' => $j['id']])) ?>"><?= e($j['title']) ?></a>
                        <span class="fw-semibold text-brand"><?= e(pay_range($j)) ?></span>
                    </div>
                    <div class="small text-body-secondary mb-2">
                        <?= e($isUae ? ($j['company'] ?: $j['employer_company'] ?: $j['employer_name']) : $j['employer_name']) ?>
                        <?php if ($j['employer_verified']): ?><i class="bi bi-patch-check-fill text-brand" title="Verified employer"></i><?php endif; ?>
                        · <i class="bi bi-geo-alt"></i> <?= e(trim(($j['town'] ? $j['town'] . ', ' : '') . $j['county'], ', ')) ?>
                        <?php if ($km !== null): ?> · <strong class="text-body"><?= e(format_km($km)) ?></strong><?php endif; ?>
                        · <?= e(time_ago($j['created_at'])) ?>
                    </div>
                    <p class="small mb-2 text-body-secondary"><?= e(excerpt($j['description'], 160)) ?></p>
                    <div>
                        <span class="chip"><?= e($j['category']) ?></span>
                        <?php if ($j['urgency'] === 'urgent'): ?><span class="chip bg-danger-subtle text-danger-emphasis"><i class="bi bi-lightning-fill"></i> Urgent</span><?php endif; ?>
                        <?php if ($isUae && $j['visa_sponsored']): ?><span class="chip bg-primary-subtle text-primary-emphasis"><i class="bi bi-passport"></i> Visa sponsored</span><?php endif; ?>
                        <?php if ($isUae && $j['accommodation']): ?><span class="chip bg-info-subtle text-info-emphasis"><i class="bi bi-house"></i> Accommodation</span><?php endif; ?>
                        <?php if ($j['status'] !== 'open'): ?><?= status_badge($j['status']) ?><?php endif; ?>
                        <span class="chip"><i class="bi bi-people"></i> <?= (int) $j['applicant_count'] ?> applied</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function empty_state(string $icon, string $title, string $text = '', string $actionHtml = ''): string
{
    return '<div class="empty-state"><i class="bi ' . e($icon) . '"></i><h6 class="text-body">' . e($title) . '</h6>'
        . ($text ? '<p class="small mb-3">' . e($text) . '</p>' : '') . $actionHtml . '</div>';
}

function rating_picker(string $name = 'rating', int $value = 0): string
{
    $html = '<div class="rating-picker stars" role="radiogroup" aria-label="Rating"><input type="hidden" name="' . e($name) . '" value="' . $value . '">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<i class="bi bi-star fs-3" data-value="' . $i . '" role="radio" style="cursor:pointer" title="' . $i . ' star' . ($i > 1 ? 's' : '') . '"></i>';
    }
    return $html . '</div>';
}
