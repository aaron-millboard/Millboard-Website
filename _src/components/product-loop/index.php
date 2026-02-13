<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?php if (!empty($args['heading']) || !empty($args['subheading'])) { ?>
        <div class="product-loop__header nflm">
            <?php if (!empty($args['heading']) && is_array($args['heading'])) { ?>
                <?= \Granola\Component::get('heading', $args['heading']); ?>
            <?php } ?>

            <?php if (!empty($args['subheading'])) { ?>
                <div class="product-loop__subheading is-style-typestyle-meta">
                    <?= wp_kses_post($args['subheading']); ?>
                </div>
            <?php } ?>
        </div>
    <?php } ?>

    <?php if (!empty($args['cards_args']['items'])) { ?>
        <?= \Granola\Component::get('cards-automatic', $args['cards_args']); ?>
    <?php } else { ?>
        <?= \Granola\Component::get('no-content', [
            'object' => $args['object'],
        ]); ?>
    <?php } ?>
</div>
