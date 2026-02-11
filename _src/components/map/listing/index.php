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
        <?= \Granola\Component::get('link', $args['phone']); ?>
    <?php } ?>

    <?= \Granola\Component::get('element', [
        'el' => 'div',
        // nested 'element' for CSS :empty use.
        'content' => !empty($args['tag']) ? \Granola\Component::get('element', $args['tag']) : null,
        'classes' => [
            'map__listing__meta',
        ],
    ]); ?>

    <?= \Granola\Component::get('link', $args['link']); ?>
</div>
