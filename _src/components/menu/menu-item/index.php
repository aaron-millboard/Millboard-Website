<li <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <span class="menu-item__wrap">
        <?= \Granola\Component::get('link', $args['link']); ?>

        <?php if ($args['display_submenu'] === true) { ?>
            <?= \Granola\Component::get('button', $args['button']); ?>
        <?php } ?>
    </span>

    <?php if ($args['display_submenu'] === true) { ?>
        <div <?= \Granola\Helpers::build_attributes($args['sub-menu-attributes']) ?>>
            <?php if (!empty($args['is_mega_menu'])) { ?>
                <?= \Granola\Component::get('menu/mega-menu-list', [
                    'items' => $args['item']->children,
                    'depth' => ($args['depth'] + 1),
                    'max_depth' => $args['max_depth'],
                    'widget' => $args['mega_menu_widget'] ?? null,
                    'cta' => $args['mega_menu_cta'] ?? null,
                ]); ?>
            <?php } else { ?>
                <?= \Granola\Component::get('menu/menu-list', [
                    'items' => $args['item']->children,
                    'depth' => ($args['depth'] + 1),
                    'max_depth' => $args['max_depth'],
                ]); ?>
            <?php } ?>
        </div>
    <?php } ?>
</li>
