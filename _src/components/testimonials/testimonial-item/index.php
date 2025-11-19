<article <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="testimonial-item__inner">
        <?php if (!empty($args['image'])) { ?>
            <div class="testimonial-item__image img-fit">
                <div class="testimonial-item__image-inner">
                    <?= \Granola\Component::get('image', $args['image']); ?>
                </div>
            </div>
        <?php } ?>
        <div class="testimonial-item__content">
            <?php if (!empty($args['testimonial'])) { ?>
                <div class="testimonial-item__testimonial">
                    <?= \wp_kses_post($args['testimonial']); ?>
                </div>
            <?php } ?>
            <?php if (!empty($args['name'])) { ?>
                <div class="testimonial-item__name">
                    <?= \wp_kses_post($args['name']); ?>
                </div>
            <?php } ?>

            <?php if (!empty($args['affiliation'])) { ?>
                <div class="testimonial-item__affiliation">
                    <?= \wp_kses_post($args['affiliation']); ?>
                </div>
            <?php } ?>
        </div>
    </div>
</article>
