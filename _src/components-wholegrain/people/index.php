<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div <?= \Granola\Helpers::build_attributes($args['inner_attributes']); ?>>
        <?php if (!empty($args['heading']) || !empty($args['subheading'])) { ?>
            <div class="people__header nflm">
                <?php if (!empty($args['subheading'])) { ?>
                    <div class="people__subheading is-style-typestyle-meta">
                        <?= wp_kses_post($args['subheading']); ?>
                    </div>
                <?php } ?>

                <?php if (!empty($args['heading']) && is_array($args['heading'])) { ?>
                    <?= \Granola\Component::get('heading', $args['heading']); ?>
                <?php } ?>

                <?php if (!empty($args['paragraph'])) { ?>
                    <div class="people__paragraph">
                        <?= wp_kses_post($args['paragraph']); ?>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>

        <?= $args['innerblocks_tag']; ?>
    </div>
</div>
