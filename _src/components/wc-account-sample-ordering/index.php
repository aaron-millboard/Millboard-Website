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

$shortcode = (string) apply_filters('millboard/account/sample_ordering_shortcode', 'mb_sample_ordering');
$has_widget = shortcode_exists($shortcode);

?>

<div class="mb-account-samples">

    <div class="mb-account-panel__head">
        <h2 class="mb-account-panel__title"><?php esc_html_e('Sample ordering', 'granola'); ?></h2>
        <span class="mb-account-panel__badge"><?php esc_html_e('Millboard team', 'granola'); ?></span>
    </div>

    <p class="mb-account-panel__intro">
        <?php esc_html_e('Place sample orders for your customers through this easy to use tool. The catalogue is read live from WooCommerce, and finishing here adds the samples to the basket.', 'granola'); ?>
    </p>

    <?php if ($has_widget) : ?>

        <div class="mb-account-samples__widget">
            <?php echo do_shortcode('[' . $shortcode . ']'); ?>
        </div>

    <?php else : ?>

        <div class="mb-account-embed mb-account-embed--placeholder">
            <div class="mb-account-embed__placeholder-inner">
                <?php
                // An <img>, not inline SVG, so the theme's global svg reset is
                // not in play -- but it still needs a declared size.
                echo \Granola\Image::get('logo-monogram.svg', [
                    'alt' => '',
                    'classes' => ['mb-account-embed__mark'],
                ]);
                ?>

                <div class="mb-account-embed__eyebrow"><?php esc_html_e('Sample ordering form', 'granola'); ?></div>

                <p class="mb-account-embed__body">
                    <?php esc_html_e('The sample ordering form loads here, inheriting the page width and the account chrome around it. It appears as soon as the widget is installed.', 'granola'); ?>
                </p>
            </div>
        </div>

    <?php endif; ?>

</div>
