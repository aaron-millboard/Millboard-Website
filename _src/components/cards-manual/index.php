<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div <?= \Granola\Helpers::build_attributes($args['inner_attributes']); ?>>
        <?php if (!empty($args['heading']) || !empty($args['subheading'])) { ?>
            <div class="cards__header nflm">
                <?php if (!empty($args['heading']) && is_array($args['heading'])) { ?>
                    <?= \Granola\Component::get('heading', $args['heading']); ?>
                <?php } ?>

                <?php if (!empty($args['subheading'])) { ?>
                    <div class="cards__subheading is-style-typestyle-large">
                        <?= wp_kses_post($args['subheading']); ?>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>

        <?= $args['innerblocks_tag']; ?>

        <?php if (!empty($args['buttons'])) { ?>
            <div class="cards__footer">
                <div class="cards__buttons flex-column">
                    <?php foreach ($args['buttons'] as $button) { ?>
                        <?= \Granola\Component::get('link', $button); ?>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
</section>
