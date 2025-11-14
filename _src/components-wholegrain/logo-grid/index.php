<?php if (!empty($args['items'])) { ?>
    <section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
        <div class="logo-grid__inner">
            <?php if (!empty($args['heading']) || !empty($args['subheading'])) { ?>
                <div class="logo-grid__header">
                    <?php if (!empty($args['heading'])) { ?>
                        <?= \Granola\Component::get('heading', [
                            'content' => $args['heading'],
                            'classes' => ['logo-grid__heading'],
                        ]); ?>
                    <?php } ?>

                    <?php if (!empty($args['subheading'])) { ?>
                        <div class="logo-grid__subheading">
                            <?= wp_kses_post($args['subheading']) ?>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>

            <div class="logo-grid__items">
                <?php foreach ($args['items'] as $item) { ?>
                    <?php if (!empty($item['link'])) { ?>
                        <?= \Granola\Component::get('link', array_merge($item['link'], [
                            'classes' => ['logo-grid__item', 'img-fit'],
                            'content' => \Granola\Component::get('image', $item['image']),
                            'content_filter' => false,
                        ])); ?>
                    <?php } else { ?>
                        <div class="logo-grid__item img-fit">
                            <?= \Granola\Component::get('image', $item['image']); ?>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
    </section>
<?php } ?>
