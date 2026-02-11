<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?= \Granola\Component::get('taxonomy-filters', $args['taxonomy_filters_args']); ?>

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
