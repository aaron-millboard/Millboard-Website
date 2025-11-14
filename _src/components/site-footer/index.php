<footer <?= \Granola\Helpers::build_attributes($args['attributes']) ?>>
    <div class="site-footer__inner">
        <div class="site-footer__top alignwide">
            <div class="site-footer__logo">
                <?= \Granola\Component::get('link', [
                    'url' => home_url('/'),
                    'content' => \Granola\Image::get('logo-alt.svg', [
                        'alt' => get_bloginfo('name'),
                    ]),
                    'content_filter' => false,
                ]); ?>
            </div>

            <?php if ($top_text = get_field('footer_text_top', 'option')) { ?>
                <div class="site-footer__top-text">
                    <?= wp_kses_post($top_text); ?>
                </div>
            <?php } ?>

            <?= \Granola\Component::get('menu', [
                'theme_location' => 'footer-1',
                'max_depth' => 1,
                'classes' => [
                    'site-footer__menu',
                    'site-footer__menu-1',
                ],
                'heading' => true,
            ]); ?>

            <?= \Granola\Component::get('menu', [
                'theme_location' => 'footer-2',
                'max_depth' => 1,
                'classes' => [
                    'site-footer__menu',
                    'site-footer__menu-2',
                ],
                'heading' => true,
            ]); ?>

            <div class="site-footer__right">
                <?php if (!empty($args['content']['images'])) { ?>
                    <div class="site-footer__images flex-grid">
                        <?php foreach ($args['content']['images'] as $image) { ?>
                            <?php if (!empty($image['link'])) { ?>
                                <?= \Granola\Component::get('link', array_merge($image['link'], [
                                    'classes' => ['site-footer__image', 'img-fit'],
                                    'content' => \Granola\Component::get('image', $image['image']),
                                    'content_filter' => false,
                                ])); ?>
                            <?php } else { ?>
                                <div class="site-footer__image img-fit">
                                    <?= \Granola\Component::get('image', $image['image']); ?>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                <?php  } ?>
            </div>
        </div>

        <div class="site-footer__bottom">
            <div class="site-footer__bottom__inner alignwide">
                <?php if ($bottom_text = get_field('footer_text_bottom', 'option')) { ?>
                    <div class="site-footer__bottom-text">
                        <?= wp_kses_post($bottom_text); ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</footer>
