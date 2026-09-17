<footer <?= \Granola\Helpers::build_attributes($args['attributes']) ?>>
    <div class="site-footer__inner">

        <?php
        // The five link columns. Still the menu component, still five theme
        // locations, still the accordion on a phone: the footer menus were
        // aligned across all eleven subsites in September and that structure is
        // settled content. What changes here is only how they look.
        ?>
        <div class="site-footer__menus is-style-typestyle-small">
            <?php foreach ($args['menus'] as $menu) { ?>
                <?= \Granola\Component::get('menu', [
                    'theme_location' => 'footer-' . $menu,
                    'max_depth' => 1,
                    'classes' => [
                        'site-footer__menu',
                        'site-footer__menu-' . $menu,
                    ],
                    'heading' => true,
                    'heading_button' => true,
                ]); ?>
            <?php } ?>
        </div>

        <?php
        // Everything below the hairline. One container for the lot, so the
        // rule and the space above it belong to whatever comes first: the
        // legal strip where a subsite has one, the wordmark row where it does
        // not. The alternative is a conditional border, which is a lot of CSS
        // to describe one line.
        ?>
        <div class="site-footer__bottom">

            <?php
            // The legal strip: terms, privacy, cookies, laid out along the
            // bottom rather than stacked in a column.
            //
            // Guarded rather than left to the menu component's own empty
            // handling, because that returns nothing while this wrapper would
            // still render -- an empty row holding the gap open on every
            // subsite that has not assigned the menu yet.
            ?>
            <?php if (has_nav_menu($args['legal_menu'])) { ?>
                <div class="site-footer__legal is-style-typestyle-small">
                    <?= \Granola\Component::get('menu', [
                        'theme_location' => $args['legal_menu'],
                        'max_depth' => 1,
                        'classes' => ['site-footer__legal-menu'],
                    ]); ?>
                </div>
            <?php } ?>

            <?php
            // The wordmark, the social links and the copyright, on one line
            // where there is room and stacked where there is not.
            ?>
            <div class="site-footer__bar">
                <div class="site-footer__logo">
                    <?= \Granola\Component::get('link', [
                        'url' => home_url('/'),
                        'content' => \Granola\SVG::get('logo-alt.svg'),
                        'content_filter' => false,
                        'attributes' => [
                            'aria-label' => $args['site_name'],
                        ],
                    ]); ?>
                </div>

                <div class="site-footer__socials">
                    <?= \Granola\Component::get('social-icons'); ?>
                </div>

                <p class="site-footer__text is-style-typestyle-small">
                    <?= $args['copyright_label']; ?>
                </p>
            </div>
        </div>
    </div>
</footer>
