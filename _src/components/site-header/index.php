<?php

/**
 * Site header.
 *
 * Two rows on wide viewports: a Mist utility strip that collapses away on
 * scroll, then the main row with the wordmark centred inside the primary nav.
 * Below the `site-header` breakpoint the same markup becomes a 64px bar and a
 * full-height drawer -- nothing is rendered twice, the grid areas just move.
 *
 * The primary menu renders in two halves so the wordmark can sit between them.
 * In the drawer the halves stack and read as one list, in menu order.
 *
 * Three siblings, in wide-viewport order: the utility strip, the main row, and
 * the drawer-only account links. The compact header is a flex column and
 * reorders them, which is why none of them nests inside another.
 */

?>
<header <?= \Granola\Helpers::build_attributes($args['attributes']) ?>>

    <?php
    // -------------------------------------------------------------------------
    // Utility strip. Mist, 44px, and gone once the page has scrolled 90px.
    //
    // A sibling of the main row, not a child of it: the row is capped at 1440px
    // and guttered, and the Mist band has to reach both edges of the viewport
    // regardless. Its own inner wrapper carries the cap instead.
    //
    // First in the DOM, which is where it belongs on a wide viewport. In the
    // drawer it moves below the nav, and its own children are reordered so the
    // samples button comes first.
    // -------------------------------------------------------------------------
    ?>
    <div class="site-header__utility">
        <div class="site-header__utility-inner">
            <div class="site-header__utility-group site-header__utility-group--start">
                <?= \Granola\Component::get('menu', [
                    'theme_location' => 'top',
                    'menu_id' => 'top-menu', // Required for 'aria-controls' in burger component.
                    'classes' => [
                        'site-header__navigation site-header__navigation--top',
                    ],
                ]); ?>

                <?= \Granola\Component::get('language/switcher'); ?>
            </div>

            <div class="site-header__utility-group site-header__utility-group--end">
                <?= \Granola\Component::get('menu', [
                    'theme_location' => 'header-utility',
                    'menu_id' => 'header-utility-menu',
                    'classes' => [
                        'site-header__navigation site-header__navigation--utility',
                    ],
                ]); ?>

                <?php if (!empty($args['content']['call_to_action_1'])) : ?>
                    <?= \Granola\Component::get('link', [
                        'url' => $args['content']['call_to_action_1']['url'],
                        'content' => $args['content']['call_to_action_1']['title'],
                        'target' => $args['content']['call_to_action_1']['target'] ?? '',
                        'classes' => $args['content']['call_to_action_1']['classes'],
                    ]); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php
    // -------------------------------------------------------------------------
    // Main row. Capped at 1440px and guttered; on compact this becomes the 64px
    // bar plus the drawer's nav rows.
    // -------------------------------------------------------------------------
    ?>
    <div class="site-header__inner">

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

        <?= \Granola\Component::get('menu', [
            'theme_location' => 'header',
            'menu_id' => 'main-menu', // Required for 'aria-controls' in burger component.
            'slice' => [0, $args['nav_split']],
            'classes' => [
                'site-header__navigation site-header__navigation--primary site-header__navigation--start',
            ],
        ]); ?>

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

        <?php
        // The second half of the primary nav and the icons share the third grid
        // column, so the wordmark stays mathematically centred between two equal
        // 1fr columns. On compact this wrapper becomes `display: contents` and
        // the two children separate: the icons stay in the bar, the nav drops
        // into the drawer.
        ?>
        <div class="site-header__end">
            <?= \Granola\Component::get('menu', [
                'theme_location' => 'header',
                'menu_id' => 'main-menu-end',
                'slice' => [$args['nav_split']],
                'classes' => [
                    'site-header__navigation site-header__navigation--primary site-header__navigation--end',
                ],
            ]); ?>

            <div class="site-header__icons">
                <button
                    class="site-header__search-toggler g-button"
                    aria-expanded="false"
                    aria-controls="site-header-search-form">
                    <span class="visually-hidden">
                        <?= esc_html__('Expand the search field', 'granola'); ?>
                    </span>
                </button>

                <?= \Granola\Component::get('link', [
                    'url' => get_the_permalink(wc_get_page_id('myaccount')),
                    'classes' => ['site-header__account-link'],
                    'content' => '<span class="visually-hidden">' . esc_html__('My account', 'granola') . '</span>',
                ]); ?>

                <?= \Granola\Component::get('link', [
                    'url' => get_the_permalink(wc_get_page_id('cart')),
                    'classes' => ['site-header__basket-link'],
                    'content' => $args['content']['basket_button_content'],
                ]); ?>
            </div>
        </div>
    </div>

    <?php
    // -------------------------------------------------------------------------
    // Drawer-only links. The account link is in the icon row too, but there it
    // is an icon with a hidden label; in the drawer it wants naming.
    //
    // A sibling of the main row rather than a child, so the compact column can
    // put it after the utility links instead of above them.
    // -------------------------------------------------------------------------
    ?>
    <div class="site-header__mobile-links">
        <?= \Granola\Component::get('link', [
            'url' => get_the_permalink(wc_get_page_id('myaccount')),
            'classes' => ['site-header__mobile-links__account'],
            'content' => esc_html__('Your account', 'granola'),
        ]); ?>

        <?php if ($args['help_center_link']) : ?>
            <?= \Granola\Component::get('link', [
                'url' => $args['help_center_link']['url'],
                'classes' => ['site-header__mobile-links__help-center'],
                'content' => esc_html__('Help center', 'granola'),
            ]); ?>
        <?php endif; ?>
    </div>

    <?= \Granola\Component::get('header-search', [
        'id' => 'site-header-search-form',
        'classes' => [
            'js-expandable-element',
        ],
    ]); ?>
</header>
