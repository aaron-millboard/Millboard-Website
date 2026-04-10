<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?php if (!empty($args['taxonomy_filters_args'])) { ?>
        <?= \Granola\Component::get('taxonomy-filters', $args['taxonomy_filters_args']); ?>
    <?php } ?>

    <?php if (!empty($args['rows'])) { ?>
        <?= \Granola\Component::get('gallery', [
            'image_rows' => $args['rows'],
            'lightbox' => $args['lightbox'],
        ]); ?>

        <?=  \Granola\Component::get('pagination', $args['pagination_args']); ?>
    <?php } else { ?>
        <?= \Granola\Component::get('no-content', [
            'object' => \get_post_type_object('image'),
        ]); ?>
    <?php } ?>
</section>
