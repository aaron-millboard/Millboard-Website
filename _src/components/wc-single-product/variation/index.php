<div class="product__variations__radio-group product__variations__radio-group--<?php echo esc_attr(sanitize_title($args['attribute'])); ?>">

    <?php
    if (!empty($args['terms'])) {
        foreach ($args['terms'] as $term) {
            if (in_array($term->slug, $args['options'], true)) {
                $checked = checked($args[ 'selected' ], $term->slug, false);
                ?>
                    <input
                        type="radio"
                        id="<?php echo esc_attr($args['name'] . '-' . $term->slug); ?>"
                        name="<?php echo esc_attr($args['name']); ?>"
                        value="<?php echo esc_attr($term->slug); ?>"
                        <?php echo $checked; ?>
                    >
                    <label for="<?php echo esc_attr($args['name'] . '-' . $term->slug); ?>">

                        <?php
                            $term_image_id = get_term_meta($term->term_id, 'image', true);
                        if ($term_image_id) {
                            echo '<div class="image">';
                                echo wp_get_attachment_image($term_image_id, 'thumbnail');
                            echo '</div>';
                        }
                        ?>
                        
                        <?php echo esc_html($term->name); ?>
                    </label>
                <?php
            }
        }
    } else {
        foreach ($args['options'] as $option) {
            $checked = sanitize_title($args[ 'selected' ]) === $args[ 'selected' ] ? checked($args[ 'selected' ], sanitize_title($option), false) : checked($args[ 'selected' ], $option, false);
            ?>
                <input
                    type="radio"
                    id="<?php echo esc_attr($args['name'] . '-' . $option); ?>"
                    name="<?php echo esc_attr($args['name']); ?>"
                    value="<?php echo esc_attr($option); ?>"
                    <?php echo $checked; ?>
                >
                <label for="<?php echo esc_attr($args['name'] . '-' . $option); ?>">
                    <?php echo esc_html($option); ?>
                </label>
            <?php
        }
    }
    ?>

</div>