<?php if (!empty($args['items_component_args']['items'])) { ?>
    <?= \Granola\Component::get('taxonomy-filters', [
        'label' => $args['filter_label'],
        'taxonomy' => $args['taxonomy'],
        'object' => $args['object'],
    ]);
    ?>
    
    <?= \Granola\Component::get($args['items_component'], $args['items_component_args']); ?>
    <?= \Granola\Component::get('pagination'); ?>
<?php } else { ?>
    <?= \Granola\Component::get('no-content', [
        'object' => $args['object'],
    ]); ?>
<?php }
