<div class="product__variations__radio-group product__variations__radio-group--<?= esc_attr(sanitize_title($args['attribute'])); ?>">
    <?php if (!empty($args['terms'])) { ?>
        <?php foreach ($args['terms'] as $term) { ?>
            <?php if (in_array($term->slug, $args['options'], true)) { ?>
                <input
                    type="radio"
                    id="<?= esc_attr($args['name'] . '-' . $term->slug); ?>"
                    name="<?= esc_attr($args['name']); ?>"
                    value="<?= esc_attr($term->slug); ?>"
                    <?php checked($args[ 'selected' ], $term->slug, false); ?>
                >
                <label for="<?= esc_attr($args['name'] . '-' . $term->slug); ?>" data-text="<?= esc_attr($term->name); ?>">

                    <?php $term_image_id = get_term_meta($term->term_id, 'image', true); ?>
                    <?php if ($term_image_id) { ?>
                        <div class="image">
                            <?= wp_get_attachment_image($term_image_id, 'thumbnail'); ?>
                        </div>
                    <?php } ?>

                    <?= esc_html($term->name); ?>
                </label>
            <?php } ?>
        <?php } ?>
    <?php } elseif (!empty($args['options'])) { ?>
        <?php foreach ($args['options'] as $option) { ?>
            <?php $checked = sanitize_title($args[ 'selected' ]) === $args[ 'selected' ] ? checked($args[ 'selected' ], sanitize_title($option), false) : checked($args[ 'selected' ], $option, false); ?>
            <input
                type="radio"
                id="<?= esc_attr($args['name'] . '-' . $option); ?>"
                name="<?= esc_attr($args['name']); ?>"
                value="<?= esc_attr($option); ?>"
                <?= $checked; ?>
            >
            <label for="<?= esc_attr($args['name'] . '-' . $option); ?>">
                <?= esc_html($option); ?>
            </label>
        <?php } ?>
    <?php } ?>
</div>
