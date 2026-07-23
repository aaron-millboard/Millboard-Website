<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="language-switcher__inner">
        <div class="language-switcher__button-wrapper">
            <?= \Granola\Component::get('button', $args['button']); ?>
        </div>

        <div <?= \Granola\Helpers::build_attributes($args['items_attributes']); ?>>
            <?= \Granola\Component::get('menu', [
                'theme_location' => $args['menu_name'],
                // Distinct id: the wrapper div above already uses $args['uid']
                // (the button's aria-controls target), so the menu list must not
                // reuse it or the id is duplicated (WCAG 4.1.2 - duplicate-id-aria).
                'menu_id' => $args['uid'] . '-menu',
                'aria_label' => _x('Language', 'Language switcher nav landmark', 'granola'),
                'classes' => ['language-switcher__menu'],
            ]); ?>
        </div>
    </div>
</div>
