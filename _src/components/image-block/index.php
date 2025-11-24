<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="image__inner">
        <?php if (!empty($args['heading'])) { ?>
            <div class="image-block__heading">
                <?= \Granola\Component::get('heading', $args['heading']); ?>

                <?php if (!empty($args['subheading'])) { ?>
                    <div class="image-block__subheading is-style-typestyle-xlarge">
                        <?= \wp_kses_post($args['subheading']); ?>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>


        <?php if (!empty($args['image'])) { ?>
            <?= \Granola\Component::get('image', $args['image']); ?>
        <?php } ?>

        <?php if (!empty($args['caption'])) { ?>
            <div class="image-block__caption is-style-typestyle-small is-style-typestyle-meta">
                <div class="image-block__caption-inner nflm">
                    <?= \wp_kses_post($args['caption']); ?>
                </div>

                <?php if (!empty($args['caption_meta'])) { ?>
                    <div class="image-block__caption-meta">
                        <?= \wp_kses_post($args['caption_meta']); ?>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>
