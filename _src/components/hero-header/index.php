<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?php if (!empty($args['strapline'])) { ?>
        <div class="hero-header__strapline-wrapper">
            <?= \Granola\Component::get('heading', $args['strapline']); ?>
        </div>
    <?php } ?>

    <?php if (!empty($args['image'])) { ?>
        <div class="hero-header__image">
            <?= \Granola\Component::get('image', $args['image']); ?>
        </div>
    <?php } ?>
</div>
