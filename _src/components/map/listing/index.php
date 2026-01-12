<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <h3 class="map__listing__title">
        <?= esc_html($args['title']); ?>
    </h3>

    <span class="map__listing__phone">
        <?= esc_html($args['phone']); ?>
    </span>

    <?= \Granola\Component::get('link', [
        'content' => __('Contact installer', 'granola'),
        'url' => $args['website'],
    ]); ?>
</div>
