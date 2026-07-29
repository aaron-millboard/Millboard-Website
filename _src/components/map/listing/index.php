<?php
// Badge glyph matching the location type's map pin, so card and map agree.
// Falls back to a generic pin for types without SVG artwork (e.g. showrooms).
$badge_icon_url = \Granola\Components\Map\marker_icon_url($args['marker'] ?? '', 'badge');

$listing_type_icon = $badge_icon_url
    ? sprintf(
        '<img class="map__listing__badge-icon" src="%s" alt="" width="12" height="12" loading="lazy" />',
        esc_url($badge_icon_url)
    )
    : '<svg class="map__listing__badge-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>';
?>
<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>

    <div class="map__listing__meta">
        <?php if (!empty($args['tag']['content'])) { ?>
            <span class="g-tag map__listing__type">
                <?= $listing_type_icon; ?>
                <?= esc_html($args['tag']['content']); ?>
            </span>
        <?php } ?>
        <span class="map__listing__distance"></span>
    </div>

    <h3 class="map__listing__title">
        <?= esc_html($args['title']); ?>
    </h3>

    <?php if (!empty($args['address'])) { ?>
        <p class="map__listing__address">
            <?= esc_html($args['address']); ?>
        </p>
    <?php } ?>

    <?php if (!empty($args['opening_today'])) { ?>
        <p class="map__listing__hours map__listing__hours--<?= esc_attr($args['opening_today_status'] ?? ''); ?>">
            <?= esc_html($args['opening_today']); ?>
        </p>
    <?php } ?>

    <?php if (!empty($args['holds_stock'])) { ?>
        <p class="map__listing__stock">
            <?= esc_html_x('Stock available', 'Map listing stock line', 'granola'); ?>
        </p>
    <?php } ?>

    <div class="map__listing__actions">
        <?php if (!empty($args['email'])) { ?>
            <a class="map__listing__action map__listing__action--icon" href="mailto:<?= esc_attr($args['email']); ?>" aria-label="<?= esc_attr__('Email', 'granola'); ?>" title="<?= esc_attr__('Email', 'granola'); ?>">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-10 6L2 7"></path></svg>
            </a>
        <?php } ?>

        <?php if (!empty($args['phone'])) { ?>
            <a class="map__listing__action map__listing__action--icon" href="tel:<?= esc_attr(preg_replace('/[^\d+]/', '', (string) $args['phone'])); ?>" aria-label="<?= esc_attr__('Phone', 'granola'); ?>" title="<?= esc_attr__('Phone', 'granola'); ?>">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
            </a>
        <?php } ?>

        <?php if (!empty($args['url'])) { ?>
            <a class="map__listing__link map__listing__action" href="<?= esc_url($args['url']); ?>">
                <?= esc_html_x('More info', 'Map listing detail link', 'granola'); ?>
            </a>
        <?php } ?>

        <?php if (!empty($args['directions_url'])) { ?>
            <a class="map__listing__action" href="<?= esc_url($args['directions_url']); ?>" target="_blank" rel="noopener noreferrer">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="3 11 22 2 13 21 11 13 3 11"></polygon></svg>
                <?= esc_html_x('Directions', 'Map listing directions link', 'granola'); ?>
            </a>
        <?php } ?>
    </div>
</div>
