<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?php foreach ($args['samples'] as $sample) { ?>
        <?= \Granola\Component::get('product-samples/sample-button', $sample); ?>
    <?php } ?>
</div>
