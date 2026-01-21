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

            <div class="logo-grid__items-wrapper">

                <div class="logo-grid__items-prewrapper">

                    <div class="logo-grid__items">
                        <?php foreach ($args['items'] as $item) { ?>
                            <div class="logo-grid__item-wrapper">
                                <?php if (!empty($item['link'])) { ?>
                                    <?= \Granola\Component::get('link', array_merge($item['link'], [
                                        'classes' => ['logo-grid__item'],
                                        'content' => \Granola\Component::get('image', $item['image']),
                                        'content_filter' => false,
                                    ])); ?>
                                <?php } else { ?>
                                    <div class="logo-grid__item">
                                        <?= \Granola\Component::get('image', $item['image']); ?>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>

                    <?php
                    // Clone items for marquee layout only
                    if ($args['layout'] === 'marquee') {
                        ?>
                        <div class="logo-grid__items logo-grid__items--clone">
                        <?php foreach ($args['items'] as $index => $item) { ?>
                                <?php if (!empty($item['link'])) { ?>
                                    <?= \Granola\Component::get('link', array_merge($item['link'], [
                                        'classes' => ['logo-grid__item'],
                                        'content' => \Granola\Component::get('image', $item['image']),
                                        'content_filter' => false,
                                    ])); ?>
                                <?php } else { ?>
                                    <div class="logo-grid__item">
                                        <?= \Granola\Component::get('image', $item['image']); ?>
                                    </div>
                                <?php } ?>
                        <?php } ?>
                        </div>
                    <?php } ?>

                </div>

            </div>

        </div>
    </section>
<?php } ?>
