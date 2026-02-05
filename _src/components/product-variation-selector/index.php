<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?php if (!empty($args['heading'])) { ?>
        <?= \Granola\Component::get('heading', $args['heading']); ?>
    <?php } ?>

    <div class="product-variation-selector__variations">
        <?php foreach ($args['variants'] as $variant) { ?>
            <?php if (!empty($variant['url'])) { ?>
                <?= \Granola\Component::get('link', $variant); ?>
            <?php } else { ?>
                <?= \Granola\Component::get('element', $variant); ?>
            <?php } ?>
        <?php } ?>
    </div>
</div>
