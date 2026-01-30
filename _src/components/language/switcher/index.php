<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="language-switcher__inner">
        <div class="language-switcher__button-wrapper">
            <?= \Granola\Component::get('button', $args['button']); ?>
        </div>

        <div <?= \Granola\Helpers::build_attributes($args['items_attributes']); ?>>
            <?= \Granola\Component::get('menu', [
                'theme_location' => $args['menu_name'],
                'menu_id' => $args['uid'],
                'classes' => ['language-switcher__menu'],
            ]); ?>
        </div>
    </div>
</div>
