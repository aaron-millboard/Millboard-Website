<header <?= \Granola\Helpers::build_attributes($args['attributes']) ?>>
    <div class="site-header__inner">

        <div class="site-header__top-navigation">
            <?= \Granola\Component::get('menu', [
                'theme_location' => 'top',
                'menu_id' => 'top-menu', // Required for 'aria-controls' in burger component.
                'classes' => [
                    'site-header__navigation site-header__navigation--top',
                ],
            ]); ?>

            <?= \Granola\Component::get('language/switcher'); ?>
        </div>

        <div class="site-header__top">
            <?= \Granola\Component::get('link', [
                'url' => home_url('/'),
                'classes' => ['site-header__logo'],
                'content' => \Granola\Image::get('logo.svg', [
                    'alt' => get_bloginfo('name'),
                    'loading' => false,
                    'attributes' => [
                        'data-spai-eager' => class_exists('\\ShortPixelAI') ? 'true' : null,
                    ],
                ]),
                'content_filter' => false,
            ]); ?>

            <div class="site-header__buttons">
                <?= \Granola\Component::get('link', [
                    'url' => get_site_url(null, '/my-account'),
                    'classes' => ['site-header__account-link'],
                    'content' => '<span class="visually-hidden">' . esc_html__('My account', 'granola') . '</span>',
                    'el' => 'a'
                ]); ?>

                <?= \Granola\Component::get('link', [
                    'url' => get_site_url(null, '/basket'),
                    'classes' => ['site-header__basket-link'],
                    'content' => $args['content']['basket_button_content'],
                    'el' => 'a'
                ]); ?>

                <button
                    class="site-header__search-toggler g-button"
                    aria-expanded="false"
                    aria-controls="site-header-search-form">
                    <span class="visually-hidden">
                        <?= esc_html__('Expand the search field', 'granola'); ?>
                    </span>
                </button>

                <?= \Granola\Component::get('burger', [
                    'classes' => [
                        'site-header__burger',
                        'js-site-header-toggle',
                    ],
                    'attributes' => [
                        'aria-label' => __('Main menu button', 'granola'),
                        'aria-controls' => 'main-menu',
                        'aria-expanded' => 'false',
                    ],
                ]); ?>
            </div>
        </div>

        <div class="site-header__bottom">
            <?= \Granola\Component::get('menu', [
                'theme_location' => 'header',
                'menu_id' => 'main-menu', // Required for 'aria-controls' in burger component.
                'classes' => [
                    'site-header__navigation',
                ],
            ]); ?>

            <div class="site-header__mobile-links">
                <?= \Granola\Component::get('link', [
                    'url' => get_site_url(null, '/my-account'),
                    'classes' => ['site-header__mobile-links__account'],
                    'content' => esc_html__('Your account', 'granola'),
                    'el' => 'a'
                ]); ?>

                <?php if($args['help_center_link']): ?>
                    <?= \Granola\Component::get('link', [
                        'url' => $args['help_center_link']['url'],
                        'classes' => ['site-header__mobile-links__help-center'],
                        'content' => esc_html__('Help center', 'granola'),
                        'el' => 'a'
                    ]); ?>
                <?php endif; ?>

                <?= \Granola\Component::get('language/switcher'); ?>
            </div>
        </div>
    </div>

    <?= \Granola\Component::get('header-search', [
        'id' => 'site-header-search-form',
        'classes' => [
            'js-expandable-element',
        ],
    ]); ?>
</header>
