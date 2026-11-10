<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="list-features__inner">
        <?php if (!empty($args['preheading']) || !empty($args['heading'])) { ?>
            <div class="list-features__header">
                <?php if (!empty($args['preheading'])) { ?>
                    <div class="list-features__preheading is-style-typestyle-h6"><?= wp_kses_post($args['preheading']); ?></div>
                <?php } ?>

                <?php if (!empty($args['heading'])) { ?>
                    <h2 class="list-features__heading"><?= wp_kses_post($args['heading']); ?></h2>
                <?php } ?>
            </div>
        <?php } ?>

        <div class="list-features__features">
            <?php foreach ($args['features'] as $feature) { ?>
                <div class="list-features__feature">
                    <?php if (!empty($feature['icon_data'])) { ?>
                        <div class="list-features__feature__icon">
                            <?= \Granola\Component::get('image', $feature['icon_data']); ?>
                        </div>
                    <?php } ?>

                    <div class="list-features__feature__content">
                        <div class="list-features__feature__title"><?= wp_kses_post($feature['title']); ?></div>

                        <?php if (!empty($feature['description'])) { ?>
                            <div class="list-features__feature__description"><?= esc_html($feature['description']); ?></div>
                        <?php } ?>

                        <?php if (!empty($feature['link'])) { ?>
                            <a
                                class="spec-features__link"
                                href="<?= esc_url($feature['link']['url']); ?>"
                                <?php if (!empty($feature['link']['target'])) { ?>
                                    target="<?= esc_attr($feature['link']['target']); ?>" rel="noopener"
                                <?php } ?>
                            ><?= esc_html($feature['link']['title']); ?></a>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
