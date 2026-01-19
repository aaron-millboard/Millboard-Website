<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?php if (!empty($args['heading'])) { ?>
        <?= \Granola\Component::get('heading', $args['heading']); ?>
    <?php } ?>
</div>
