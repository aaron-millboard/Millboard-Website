<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="testimonials__inner">
        <?php if (!is_array($args['testimonials'])) { ?>
            <?= $args['testimonials']; ?>
        <?php } else { ?>
            <?= \Granola\Component::get('slider', $args['slider']); ?>
        <?php } ?>
    </div>
</div>
