<?php if (!empty($args['taxonomy_filters_args'])) { ?>
    <?= \Granola\Component::get('taxonomy-filters', $args['taxonomy_filters_args']); ?>
<?php } ?>

<?php if (!empty($args['items_component_args']['items'])) { ?>
    <?= \Granola\Component::get($args['items_component'], $args['items_component_args']); ?>
    <?php if (!empty($args['pagination_args'])) { ?>
        <?= \Granola\Component::get('pagination', $args['pagination_args']); ?>
    <?php } ?>
<?php } else { ?>
    <?= \Granola\Component::get('no-content', [
        'object' => $args['object'],
    ]); ?>
<?php }