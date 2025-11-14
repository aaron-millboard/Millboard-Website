<nav <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="menu__inner">
        <?php if (!empty($args['heading'])) { ?>
            <?= \Granola\Component::get('heading', [
                'content' => $args['heading'],
                'classes' => ['menu__heading'],
            ]); ?>
        <?php } ?>

        <?= \Granola\Component::get('menu/menu-list', [
            'items' => $args['items'],
            'id' => $args['menu_id'],
            'classes' => !empty($args['menu_class']) ? explode(' ', $args['menu_class']) : [],
            'max_depth' => $args['max_depth'],
        ]); ?>
    </div>
</nav>
