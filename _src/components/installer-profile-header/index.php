<?php
$is_advanced = !empty($args['is_advanced']);
$has_cover = !empty($args['cover_image']);

// -----------------------------------------------------------------------------
// Shared icon + fragment builders (kept as strings so both layouts can reuse
// them). All inline SVGs are sized in block.scss to survive the theme's global
// `svg { width: 100% }` reset.
// -----------------------------------------------------------------------------
$icon_arrow = '<svg class="installer-profile-header__btn-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>';
$icon_phone = '<svg class="installer-profile-header__btn-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.6a2 2 0 0 1-.5 2.1L8 9.6a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.8.3 1.7.5 2.6.6a2 2 0 0 1 1.7 2Z"></path></svg>';

// Buttons.
$buttons_html = '';
if (!empty($args['buttons'])) {
    $buttons_html .= '<div class="installer-profile-header__actions">';
    foreach ($args['buttons'] as $button) {
        $label = '<span>' . esc_html($button['text']) . '</span>';
        if (($button['icon'] ?? '') === 'phone') {
            $inner = $icon_phone . $label;
        } elseif (($button['icon'] ?? '') === 'arrow') {
            $inner = $label . $icon_arrow;
        } else {
            $inner = $label;
        }
        $buttons_html .= sprintf(
            '<a class="installer-profile-header__btn installer-profile-header__btn--%s" href="%s">%s</a>',
            esc_attr($button['variant']),
            esc_url($button['href']),
            $inner
        );
    }
    $buttons_html .= '</div>';
}

// Location line.
$location_html = '';
if (!empty($args['address_text'])) {
    $location_html = '<p class="installer-profile-header__location"><svg class="installer-profile-header__pin" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="2.6"></circle></svg><span>' . esc_html($args['address_text']) . '</span></p>';
}

// Badge (official image if supplied, otherwise a tier text badge).
if (!empty($args['badge_image'])) {
    $badge_html = '<span class="installer-profile-header__badge installer-profile-header__badge--image">' . wp_get_attachment_image($args['badge_image'], 'medium', false, ['class' => 'installer-profile-header__badge-img']) . '</span>';
} else {
    $badge_html = '<span class="installer-profile-header__badge"><span class="installer-profile-header__badge-brand">Millboard</span><span class="installer-profile-header__badge-tier">' . esc_html($args['tier']) . ' Installer</span></span>';
}
?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="installer-profile-header__breadcrumbs">
        <?= \Granola\Component::get('breadcrumbs'); ?>
    </div>

    <?php if ($is_advanced) { ?>

        <?php if ($has_cover) { ?>
            <div class="installer-profile-header__hero-media">
                <?= wp_get_attachment_image($args['cover_image'], 'full', false, ['class' => 'installer-profile-header__hero-img', 'loading' => 'eager']); ?>
                <span class="installer-profile-header__hero-scrim" aria-hidden="true"></span>
                <span class="installer-profile-header__verified">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20 6 9 17l-5-5"></path></svg>
                    <span><?= esc_html($args['verified_label']); ?></span>
                </span>
            </div>
        <?php } ?>

        <div class="installer-profile-header__card-wrap<?= $has_cover ? ' installer-profile-header__card-wrap--overlap' : ''; ?>">
            <div class="installer-profile-header__card">
                <p class="installer-profile-header__eyebrow"><?= esc_html($args['tier_label']); ?></p>

                <div class="installer-profile-header__card-badge">
                    <?= $badge_html; ?>
                </div>

                <div class="installer-profile-header__card-main">
                    <h1 class="installer-profile-header__title"><?= esc_html($args['title']); ?></h1>
                    <?php if (!empty($args['tagline'])) { ?>
                        <p class="installer-profile-header__tagline"><?= esc_html($args['tagline']); ?></p>
                    <?php } ?>
                    <?= $location_html; ?>

                    <?php if (!empty($args['stats'])) { ?>
                        <div class="installer-profile-header__stats">
                            <?php foreach ($args['stats'] as $stat) { ?>
                                <div class="installer-profile-header__stat">
                                    <span class="installer-profile-header__stat-value"><?= esc_html($stat['value']); ?></span>
                                    <span class="installer-profile-header__stat-label">
                                        <?= esc_html($stat['label']); ?>
                                        <?php if (!empty($stat['sublabel'])) { ?>
                                            <span class="installer-profile-header__stat-sublabel"><?= esc_html($stat['sublabel']); ?></span>
                                        <?php } ?>
                                    </span>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>

                    <?= $buttons_html; ?>
                </div>
            </div>
        </div>

    <?php } else { ?>

        <div class="installer-profile-header__inner">
            <div class="installer-profile-header__body">
                <p class="installer-profile-header__eyebrow"><?= esc_html($args['tier_label']); ?></p>
                <h1 class="installer-profile-header__title"><?= esc_html($args['title']); ?></h1>
                <?php if (!empty($args['tagline'])) { ?>
                    <p class="installer-profile-header__tagline"><?= esc_html($args['tagline']); ?></p>
                <?php } ?>
                <?= $location_html; ?>

                <?php if (!empty($args['has_rating'])) { ?>
                    <?php
                    $rating_inner = '<span class="installer-profile-header__stars" aria-hidden="true">';
                    for ($i = 0; $i < 5; $i++) {
                        $rating_inner .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" focusable="false"><path d="M12 2.4l2.85 6.5 7.05.6-5.35 4.65 1.6 6.9L12 17.9 5.9 21.55l1.6-6.9L2.1 9.5l7.05-.6z"></path></svg>';
                    }
                    $rating_inner .= '</span>';
                    $rating_inner .= '<span class="installer-profile-header__rating-value">' . esc_html($args['rating']) . '</span>';
                    if (!empty($args['review_count'])) {
                        /* translators: %s: number of reviews. */
                        $rating_inner .= '<span class="installer-profile-header__rating-count">' . esc_html(sprintf(\__('· %s reviews', 'granola'), $args['review_count'])) . '</span>';
                    }
                    ?>
                    <?php if (!empty($args['reviews_url'])) { ?>
                        <a class="installer-profile-header__rating" href="<?= esc_url($args['reviews_url']); ?>"><?= $rating_inner; ?></a>
                    <?php } else { ?>
                        <div class="installer-profile-header__rating"><?= $rating_inner; ?></div>
                    <?php } ?>
                <?php } ?>

                <?= $buttons_html; ?>
            </div>

            <?php if ($has_cover) { ?>
                <div class="installer-profile-header__media">
                    <?= wp_get_attachment_image($args['cover_image'], 'large', false, ['class' => 'installer-profile-header__cover', 'loading' => 'eager']); ?>
                    <?= $badge_html; ?>
                </div>
            <?php } ?>
        </div>

    <?php } ?>
</section>
