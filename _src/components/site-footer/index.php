<footer <?= \Granola\Helpers::build_attributes($args['attributes']) ?>>
    <div class="site-footer__inner flex-column alignwide">
        <div class="site-footer__logo flex-column">
            <?= \Granola\Component::get('link', [
                'url' => home_url('/'),
                'content' => \Granola\SVG::get('logo-alt.svg'),
                'content_filter' => false,
                'attributes' => [
                    'aria-label' => get_bloginfo('name'),
                ],
            ]); ?>
        </div>

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

        <div class="site-footer__socials-text flex-column">
            <div class="site-footer__socials">
                <?= \Granola\Component::get('social-icons'); ?>
            </div>

            <div class="site-footer__text is-style-typestyle-small">
                <?= $args['copyright_label']; ?>
                <span class="site-footer__socials-text__separator">|</span>
                <?= $args['wholegrain_label']; ?>
            </div>
    </div>
</footer>
