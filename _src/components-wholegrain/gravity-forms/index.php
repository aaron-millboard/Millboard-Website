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
                <?php if ($args['form_type'] === 'gravityforms' && !empty($args['gravity_form_id'])) { ?>
                    <div class="gravity-forms__form">
                        <?= do_shortcode('[gravityform id="' . absint($args['gravity_form_id']) . '" title="false" description="false" ajax="true"]'); ?>
                    </div>
                <?php } ?>
                <?php if ($args['form_type'] === 'hubspot' && !empty($args['hubspot_script'])) { ?>
                    <?php
                    // HubSpot draws a form inside an iframe, which the theme CSS cannot reach, unless the
                    // embed is told `css: ''`. Add it here so every pasted embed renders in the page and
                    // picks up the site's form styling (.gravity-forms .hbspt-form) instead of each form
                    // being restyled by hand. Skipped when the embed already sets its own `css`.
                    $hubspot_script = preg_replace('/hbspt\.forms\.create\(\s*\{(?![^}]*\bcss\s*:)/', "hbspt.forms.create({\n    css: '',", $args['hubspot_script'], 1);
                    ?>
                    <div class="gravity-forms__form">
                        <?= $hubspot_script; ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
