<?php

/**
 * Home newsletter.
 *
 * A picture beside the mailing list sign-up.
 *
 * The form is Gravity Forms, rendered by its own shortcode. The design draws
 * the inputs directly, but this one takes a name and an email address and adds
 * someone to a mailing list, so the submission, the notifications and the
 * consent handling behind them belong to the form. Hand-building the inputs to
 * match a mockup would detach all of that and leave a sign-up that looks right
 * and does nothing. The styling is applied to what Gravity Forms outputs
 * instead.
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="home-newsletter__inner mbh-reveal">

        <?php if (!empty($args['image'])) : ?>
            <?= \Granola\Component::get('image', $args['image']); ?>
        <?php endif; ?>

        <div class="home-newsletter__body">
            <span class="home-newsletter__rule" aria-hidden="true"></span>

            <?php if (!empty($args['eyebrow'])) : ?>
                <p class="home-newsletter__eyebrow"><?= esc_html($args['eyebrow']); ?></p>
            <?php endif; ?>

            <?php if (!empty($args['heading'])) : ?>
                <h2 class="home-newsletter__heading"><?= esc_html($args['heading']); ?></h2>
            <?php endif; ?>

            <?php if (!empty($args['description'])) : ?>
                <p class="home-newsletter__description"><?= esc_html($args['description']); ?></p>
            <?php endif; ?>

            <?php if (!empty($args['gravity_form_id'])) : ?>
                <div class="home-newsletter__form">
                    <?= do_shortcode('[gravityform id="' . absint($args['gravity_form_id']) . '" title="false" description="false" ajax="true"]'); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($args['reassurance'])) : ?>
                <p class="home-newsletter__reassurance"><?= esc_html($args['reassurance']); ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>
