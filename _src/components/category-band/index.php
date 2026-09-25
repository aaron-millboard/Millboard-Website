<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="category-band__inner">
        <?php if (!empty($args['image_id'])) { ?>
            <div class="category-band__media" aria-hidden="true">
                <?= \Granola\Component::get('image', [
                    'attachment_id' => $args['image_id'],
                    'size' => 'large',
                    'alt' => '',
                    'classes' => ['category-band__image'],
                ]); ?>
            </div>
        <?php } ?>

        <div class="category-band__content">
            <div class="category-band__text nflm">
                <hr class="category-band__rule" aria-hidden="true">

                <h2 class="category-band__heading"><?= \esc_html($args['heading']); ?></h2>

                <?php if (!empty($args['intro'])) { ?>
                    <div class="category-band__intro"><?= \wp_kses_post(\wpautop($args['intro'])); ?></div>
                <?php } ?>
            </div>

            <?php if (!empty($args['buttons'])) { ?>
                <div class="category-band__buttons">
                    <?php foreach ($args['buttons'] as $button) { ?>
                        <?= \Granola\Component::get('link', $button); ?>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>
</section>
