<ul <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?php foreach ($args['items'] as $key => $item) { ?>
        <?= \Granola\Component::get('menu/menu-item', [
            'item' => $item,
            'depth' => $args['depth'],
            'max_depth' => $args['max_depth'],
            'parent_list_id' => $args['attributes']['id'] ?? null,
        ]); ?>
    <?php } ?>
</ul>
