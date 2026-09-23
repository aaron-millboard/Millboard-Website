<?php

/**
 * Brand assets.
 *
 * The Canto library, framed inside the account so the team does not have to
 * sign in twice.
 *
 * Canto currently answers with `X-Frame-Options: DENY`, so until it is set to
 * allow millboard.com as a frame ancestor the browser refuses to paint the
 * frame and leaves an empty box. The frame is built as designed regardless,
 * and the script alongside this file notices an empty frame and reveals the
 * fallback panel instead. Nothing needs changing here when Canto opens up: the
 * frame simply fills and the fallback stays hidden.
 */

$canto_url = (string) apply_filters(
    'millboard/account/canto_url',
    'https://millboard.canto.global/v/millboarduk/landing'
);

?>

<div class="mb-account-assets">

    <div class="mb-account-panel__head">
        <h2 class="mb-account-panel__title"><?php esc_html_e('Brand assets', 'granola'); ?></h2>
        <span class="mb-account-panel__badge"><?php esc_html_e('Millboard team', 'granola'); ?></span>
    </div>

    <p class="mb-account-panel__intro">
        <?php esc_html_e('Photography, logos, brochures and product imagery, served from Canto. You are signed in already, so there is no second login.', 'granola'); ?>
    </p>

    <div class="mb-account-embed" data-mb-embed data-mb-embed-url="<?php echo esc_url($canto_url); ?>">
        <iframe
            src="<?php echo esc_url($canto_url); ?>"
            title="<?php esc_attr_e('Millboard brand assets', 'granola'); ?>"
            data-mb-embed-frame
            loading="lazy"
        ></iframe>

        <div class="mb-account-embed__fallback" data-mb-embed-fallback hidden>
            <div class="mb-account-embed__placeholder-inner">
                <div class="mb-account-embed__eyebrow"><?php esc_html_e('Opens in Canto', 'granola'); ?></div>
                <p class="mb-account-embed__body">
                    <?php esc_html_e('Canto does not allow its library to be shown inside another site, so the brand assets open in their own tab.', 'granola'); ?>
                </p>
                <p class="mb-account-embed__actions">
                    <a class="mb-account-btn" href="<?php echo esc_url($canto_url); ?>" target="_blank" rel="noopener">
                        <?php esc_html_e('Open the brand library', 'granola'); ?>
                    </a>
                </p>
            </div>
        </div>
    </div>

    <p class="mb-account-embed-note">
        <?php
        printf(
            /* translators: %1$s: opening link tag, %2$s: closing link tag. */
            esc_html__('Trouble loading? %1$sOpen Canto in a new tab%2$s.', 'granola'),
            '<a href="' . esc_url($canto_url) . '" target="_blank" rel="noopener">',
            '</a>'
        );
        ?>
    </p>

</div>
