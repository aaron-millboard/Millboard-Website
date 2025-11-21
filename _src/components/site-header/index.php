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

            <button
                class="site-header__search-toggler site-header__search-toggler--mobile g-button"
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

        <div class="site-header__bottom">
            <?= \Granola\Component::get('menu', [
                'theme_location' => 'header',
                'menu_id' => 'main-menu', // Required for 'aria-controls' in burger component.
                'classes' => [
                    'site-header__navigation',
                ],
            ]); ?>
            
            <?php /*

            <button
                class="site-header__search-toggler site-header__search-toggler--desktop g-button g-button--square"
                aria-expanded="false"
                aria-controls="site-header-search-form">
                <span class="visually-hidden">
                    <?= esc_html__('Expand the search field', 'granola'); ?>
                </span>
            </button>

            <?php if (!empty($args['content']['call_to_action_1'])) { ?>
                <div class="site-header__widgets">
                    <?= \Granola\Component::get('link', $args['content']['call_to_action_1']); ?>
                </div>
            <?php } ?>

            */ ?>

        </div>

        <?= \Granola\Component::get('header-search', [
            'id' => 'site-header-search-form',
            'classes' => ['js-expandable-element'],
        ]); ?>
    </div>
</header>
