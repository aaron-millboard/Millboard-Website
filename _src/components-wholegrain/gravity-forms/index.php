<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?> <?= \Granola\Helpers::build_classes($args['classes']); ?>>
    <div class="gravity-forms__inner">
        <div class="gravity-forms__grid">
            <div class="gravity-forms__column gravity-forms__column--content">
                <div class="gravity-forms__header">
                    <?php if (!empty($args['preheading'])) { ?>
                        <h2 class="gravity-forms__preheading">
                            <?= wp_kses_post($args['preheading']); ?>
                        </h2>
                    <?php } ?>
                    
                    <?php if (!empty($args['heading'])) { ?>
                        <h2 class="gravity-forms__heading">
                            <?= wp_kses_post($args['heading']); ?>
                        </h2>
                    <?php } ?>

                    <?php if (!empty($args['description'])) { ?>
                        <div class="gravity-forms__description">
                            <?= wp_kses_post($args['description']); ?>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="gravity-forms__column gravity-forms__column--form">
                <?php if (!empty($args['gravity_form_id'])) { ?>
                    <div class="gravity-forms__form">
                        <?= do_shortcode('[gravityform id="' . absint($args['gravity_form_id']) . '" title="false" description="false" ajax="true"]'); ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
