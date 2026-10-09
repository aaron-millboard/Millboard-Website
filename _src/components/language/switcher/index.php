<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="language-switcher__inner">
        <div class="language-switcher__button-wrapper">
            <?= \Granola\Component::get('button', $args['button']); ?>
        </div>

        <div <?= \Granola\Helpers::build_attributes($args['items_attributes']); ?>>
            <?= \Granola\Component::get('menu', [
                'theme_location' => $args['menu_name'],
                // Not the bare uid. That belongs to the wrapper above, which is
                // what the button's aria-controls points at; handing the same one
                // to the list inside put one id on two elements and left the
                // control ambiguous about what it opens.
                'menu_id' => $args['uid'] . '-menu',
                'classes' => ['language-switcher__menu'],
            ]); ?>
        </div>
    </div>
</div>
