<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="spec-contact__inner">
        <div class="spec-contact__content">
            <?php if (!empty($args['preheading'])) { ?>
                <div class="spec-contact__preheading is-style-typestyle-h6"><?= wp_kses_post($args['preheading']); ?></div>
            <?php } ?>

            <?php if (!empty($args['heading'])) { ?>
                <h2 class="spec-contact__heading"><?= wp_kses_post($args['heading']); ?></h2>
            <?php } ?>

            <?php if (!empty($args['content'])) { ?>
                <div class="spec-contact__text"><?= wp_kses_post($args['content']); ?></div>
            <?php } ?>

            <?php if (!empty($args['person_name']) || !empty($args['person_image_data'])) { ?>
                <div class="spec-contact__person">
                    <?php if (!empty($args['person_image_data'])) { ?>
                        <div class="spec-contact__person-photo img-fit">
                            <?= \Granola\Component::get('image', $args['person_image_data']); ?>
                        </div>
                    <?php } ?>

                    <div class="spec-contact__person-text">
                        <?php if (!empty($args['person_name'])) { ?>
                            <p class="spec-contact__person-name"><?= esc_html($args['person_name']); ?></p>
                        <?php } ?>

                        <?php if (!empty($args['person_role'])) { ?>
                            <p class="spec-contact__person-role"><?= esc_html($args['person_role']); ?></p>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>

            <?php if (!empty($args['note_html'])) { ?>
                <?php // Built in functions.php from the escaped note: only mailto: and tel: links are added. ?>
                <p class="spec-contact__note"><?= $args['note_html']; ?></p>
            <?php } ?>
        </div>

        <div class="spec-contact__form">
            <?php if (!empty($args['has_form'])) { ?>
                <div class="gravity-forms">
                    <script charset="utf-8" type="text/javascript" src="https://js-<?= esc_attr($args['hs_region']); ?>.hsforms.net/forms/embed/v2.js"></script>
                    <script charset="utf-8" type="text/javascript">
                        hbspt.forms.create({
                            region: "<?= esc_js($args['hs_region']); ?>",
                            portalId: "<?= esc_js($args['hs_portal_id']); ?>",
                            formId: "<?= esc_js($args['hs_form_id']); ?>"
                        });
                    </script>
                </div>
            <?php } elseif (!empty($args['is_preview'])) { ?>
                <p class="spec-contact__placeholder"><?= esc_html__('Add a HubSpot form ID to show the enquiry form.', 'granola'); ?></p>
            <?php } ?>
        </div>
    </div>
</section>
