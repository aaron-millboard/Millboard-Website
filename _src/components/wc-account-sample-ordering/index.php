<?php

/**
 * Sample ordering.
 *
 * The rep-facing sample tool: samples picked from the live WooCommerce
 * catalogue, added to the basket and taken through checkout.
 *
 * The widget itself is the IT team's `mb-sof` build, rendered by its own
 * shortcode. Everything here is the chrome around it: the heading WooCommerce
 * would otherwise print, the explanation, and the slot it sits in. The widget
 * drops its own <h1> for exactly this reason, so there is one heading.
 *
 * Until that plugin is installed the slot shows the placeholder, sized to the
 * design's 560px so the page does not change shape when the real thing lands.
 */

use function Granola\Components\WC_Account\get_sample_shortcode;
use function Granola\Components\WC_Account_Sample_Ordering\has_catalogue;

$shortcode = get_sample_shortcode();
$has_widget = shortcode_exists($shortcode) && has_catalogue();

?>

<div class="mb-account-samples">

    <div class="mb-account-panel__head">
        <h2 class="mb-account-panel__title"><?php esc_html_e('Sample ordering', 'granola'); ?></h2>
        <?php
        // The badge says who the panel is for. On a distributor's or
        // installer's screen "Millboard team" is simply wrong, so staff see
        // that and partners see the neutral wording.
        ?>
        <span class="mb-account-panel__badge">
            <?php
            echo \Granola\Components\WC_Account\is_staff()
                ? esc_html__('Millboard team', 'granola')
                : esc_html__('Trade account', 'granola');
            ?>
        </span>
    </div>

    <p class="mb-account-panel__intro">
        <?php esc_html_e('Place sample orders for your customers through this easy to use tool. The catalogue is read live from WooCommerce, and finishing here adds the samples to the basket.', 'granola'); ?>
    </p>

    <?php if ($has_widget) : ?>

        <div class="mb-account-samples__widget">
            <?php echo do_shortcode('[' . $shortcode . ']'); ?>
        </div>

    <?php else : ?>

        <div class="mb-account-empty">
            <span class="mb-account-empty__rule" aria-hidden="true"></span>
            <h3 class="mb-account-empty__title"><?php esc_html_e('No sample catalogue yet', 'granola'); ?></h3>
            <p class="mb-account-empty__body">
                <?php esc_html_e('The form is installed and ready, but it cannot see the sample products yet. On this store a sample is a size option on a board rather than a product of its own, and the form reads products only.', 'granola'); ?>
            </p>
            <p class="mb-account-empty__body">
                <?php esc_html_e('Nothing can be ordered here until that is connected, so no order placed through this tab can go out wrong in the meantime.', 'granola'); ?>
            </p>
        </div>

    <?php endif; ?>

</div>
