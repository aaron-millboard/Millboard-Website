<li <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <span class="menu-item__wrap">
        <?= \Granola\Component::get('link', $args['link']); ?>

        <?php if ($args['display_submenu'] === true) { ?>
            <?= \Granola\Component::get('button', $args['button']); ?>
        <?php } ?>
    </span>

    <?php if ($args['display_submenu'] === true) { ?>
        <div <?= \Granola\Helpers::build_attributes($args['sub-menu-attributes']) ?>>
            <?= \Granola\Component::get('menu/menu-list', [
                'items' => $args['item']->children,
                'depth' => ($args['depth'] + 1),
                'max_depth' => $args['max_depth'],
            ]); ?>
        </div>
    <?php } ?>
</li>
