<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <h3 class="map__listing__title">
        <?= esc_html($args['title']); ?>
    </h3>

    <?php if (!empty($args['address'])) { ?>
        <span class="map__listing__address">
            <?= esc_html($args['address']); ?>
        </span>
    <?php } ?>

    <?php if (!empty($args['opening_today'])) { ?>
        <span class="map__listing__hours">
            <?= esc_html($args['opening_today']); ?>
        </span>
    <?php } ?>

    <div class="map__listing__meta">
        <?php if (!empty($args['tag'])) {
            echo \Granola\Component::get('element', $args['tag']);
        } ?>

        <?php if (!empty($args['has_display']) && ($args['post_type'] ?? '') === 'distributor') { ?>
            <span class="g-tag map__listing__display-badge">
                <?= esc_html_x('Display', 'Map listing display badge', 'granola'); ?>
            </span>
        <?php } ?>
    </div>

    <?= \Granola\Component::get('link', $args['link']); ?>
</div>
