<nav <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="menu__inner">
        <?php if (!empty($args['heading'])) { ?>
            <?php if ($args['heading_button']) { ?>
                <div class="menu__heading__button-wrap">
            <?php } ?>

            <?= \Granola\Component::get('heading', [
                'content' => $args['heading'],
                'classes' => ['menu__heading'],
            ]); ?>

            <?php if ($args['heading_button']) { ?>
                <?= \Granola\Component::get('button', $args['button']); ?>
                </div>
            <?php } ?>
        <?php } ?>

        <?= \Granola\Component::get('menu/menu-list', [
            'items' => $args['items'],
            'id' => $args['menu_id'],
            'classes' => !empty($args['menu_class']) ? explode(' ', $args['menu_class']) : [],
            'max_depth' => $args['max_depth'],
            'attributes' => $args['expandable_element_attributes'],
        ]); ?>
    </div>
</nav>
