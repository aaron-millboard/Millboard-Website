<?php
$phone = $args['phone'] ?? '';
$email = $args['email'] ?? '';
$tel = preg_replace('/[^0-9+]/', '', (string) $phone);

$icon_phone = '<svg class="installer-quote-form__contact-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.6a2 2 0 0 1-.5 2.1L8 9.6a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.8.3 1.7.5 2.6.6a2 2 0 0 1 1.7 2Z"></path></svg>';
$icon_mail = '<svg class="installer-quote-form__contact-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path></svg>';
?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="installer-quote-form__inner">

        <div class="installer-quote-form__intro">
            <?php if (!empty($args['eyebrow'])) { ?>
                <p class="installer-quote-form__eyebrow"><?= esc_html($args['eyebrow']); ?></p>
            <?php } ?>
            <?php if (!empty($args['heading'])) { ?>
                <h2 class="installer-quote-form__heading"><?= esc_html($args['heading']); ?></h2>
            <?php } ?>
            <?php if (!empty($args['intro'])) { ?>
                <p class="installer-quote-form__lead"><?= esc_html($args['intro']); ?></p>
            <?php } ?>

            <?php if (!empty($phone) || !empty($email)) { ?>
                <div class="installer-quote-form__contact">
                    <?php if (!empty($phone)) { ?>
                        <a class="installer-quote-form__contact-btn" href="tel:<?= esc_attr($tel); ?>"><?= $icon_phone; ?><span><?= esc_html__('Call us', 'granola'); ?></span></a>
                    <?php } ?>
                    <?php if (!empty($email)) { ?>
                        <a class="installer-quote-form__contact-btn" href="mailto:<?= esc_attr($email); ?>"><?= $icon_mail; ?><span><?= esc_html__('Email us', 'granola'); ?></span></a>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>

        <div class="installer-quote-form__panel">
            <?php if (!empty($args['has_form'])) { ?>
                <div class="installer-quote-form__hs">
                    <div class="hs-form-html" data-region="<?= esc_attr($args['hs_region']); ?>" data-form-id="<?= esc_attr($args['hs_form_id']); ?>" data-portal-id="<?= esc_attr($args['hs_portal_id']); ?>"></div>
                </div>
                <?php if (!empty($args['is_preview'])) { ?>
                    <p class="installer-quote-form__note"><?= esc_html__('HubSpot enquiry form — displays on the published page.', 'granola'); ?></p>
                <?php } ?>
            <?php } elseif (!empty($args['is_preview'])) { ?>
                <p class="installer-quote-form__placeholder"><?= esc_html__('Add a HubSpot form ID to embed the enquiry form.', 'granola'); ?></p>
            <?php } ?>
        </div>

    </div>
</section>
