<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <h3 class="map__listing__title">
        <?= esc_html($args['title']); ?>
    </h3>

    <?php if (!empty($args['address'])) { ?>
        <span class="map__listing__address">
            <?= esc_html($args['address']); ?>
        </span>
    <?php } ?>

    <?php if (!empty($args['phone'])) { ?>
        <span class="map__listing__phone">
            <?= esc_html($args['phone']); ?>
        </span>
    <?php } ?>

    <?= \Granola\Component::get('link', $args['link']); ?>
</div>
