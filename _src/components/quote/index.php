<blockquote <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="quote__inner">
        <?php if (!empty($args['image'])) { ?>
            <div class="quote__image img-fit">
                <div class="quote__image-inner">
                    <?= \Granola\Component::get('image', $args['image']); ?>
                </div>
            </div>
        <?php } ?>
        <div class="quote__content">
            <?php if (!empty($args['testimonial'])) { ?>
                <div class="quote__testimonial">
                    <?= \wp_kses_post($args['testimonial']); ?>
                </div>
            <?php } ?>
            <?php if (!empty($args['name']) || !empty($args['affiliation'])) { ?>
                <cite class="quote__citation">
                    <?php if (!empty($args['name'])) { ?>
                        <div class="quote__name">
                            <?= \wp_kses_post($args['name']); ?>
                        </div>
                    <?php } ?>

                    <?php if (!empty($args['affiliation'])) { ?>
                        <div class="quote__affiliation">
                            <?= \wp_kses_post($args['affiliation']); ?>
                        </div>
                    <?php } ?>
                </cite>
            <?php } ?>
        </div>
    </div>
</blockquote>
