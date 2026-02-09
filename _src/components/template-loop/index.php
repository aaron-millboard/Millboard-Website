<?php if (!empty($args['items_component_args']['items'])) { ?>
    <?php if (!empty($args['taxonomy_filters'])) { ?>
        <?= \Granola\Component::get('taxonomy-filters', $args['taxonomy_filters']); ?>
    <?php } ?>

    <?= \Granola\Component::get($args['items_component'], $args['items_component_args']); ?>
    <?= \Granola\Component::get('pagination'); ?>
<?php } else { ?>
    <?= \Granola\Component::get('no-content', [
        'object' => $args['object'],
    ]); ?>
<?php }
