<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="email-subscription__inner">
        <div class="email-subscription__grid">
            <div class="email-subscription__column email-subscription__column--image">
                <?php if (!empty($args['image_data'])) { ?>
                    <div class="email-subscription__image-wrapper">
                        <?= \Granola\Component::get('image', $args['image_data']); ?>
                    </div>
                <?php } ?>
            </div>

            <div class="email-subscription__column email-subscription__column--content">

                <div class="email-subscription__header">
                    <?php if (!empty($args['preheading'])) { ?>
                        <h2 class="email-subscription__preheading">
                            <?= wp_kses_post($args['preheading']); ?>
                        </h2>
                    <?php } ?>
                    
                    <?php if (!empty($args['heading'])) { ?>
                        <h2 class="email-subscription__heading">
                            <?= wp_kses_post($args['heading']); ?>
                        </h2>
                    <?php } ?>
                </div>

                <?php if (!empty($args['description'])) { ?>
                    <div class="email-subscription__description">
                        <?= wp_kses_post($args['description']); ?>
                    </div>
                <?php } ?>

                <?php if (!empty($args['gravity_form_id'])) { ?>
                    <div class="email-subscription__form">
                        <?= do_shortcode('[gravityform id="' . absint($args['gravity_form_id']) . '" title="false" description="false" ajax="true"]'); ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
