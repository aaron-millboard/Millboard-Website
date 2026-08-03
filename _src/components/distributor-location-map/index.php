<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="distributor-location-map__inner">

        <div class="distributor-location-map__map">
            <?php
            /**
             * data-no-lazy opts out of Perfmatters' iframe lazy loading, which moves
             * src to data-src and needs its own JS to swap it back. With script
             * delaying on, that left the map as an empty box. The native
             * loading="lazy" below defers it without depending on any JS.
             */
            ?>
            <iframe
                class="distributor-location-map__frame skip-lazy"
                src="<?= esc_url($args['embed_url']); ?>"
                title="<?= esc_attr($args['embed_title']); ?>"
                loading="lazy"
                data-no-lazy="1"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen
            ></iframe>
        </div>

        <div class="distributor-location-map__content">
            <span class="distributor-location-map__rule" aria-hidden="true"></span>

            <h2 class="distributor-location-map__heading"><?= esc_html($args['heading']); ?></h2>

            <p class="distributor-location-map__address">
                <span class="distributor-location-map__name"><?= esc_html($args['name']); ?></span>
                <?php if (!empty($args['address'])) { ?>
                    <br><?= esc_html($args['address']); ?>
                <?php } ?>
            </p>

            <div class="distributor-location-map__actions">
                <a
                    class="distributor-location-map__action distributor-location-map__action--primary"
                    href="<?= esc_url($args['directions_url']); ?>"
                    data-partner-action="directions"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <?= esc_html__('Get directions', 'granola'); ?>
                    <svg class="distributor-location-map__action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <path d="M7 17 17 7M8 7h9v9"></path>
                    </svg>
                </a>

                <?php if (!empty($args['website'])) { ?>
                    <a
                        class="distributor-location-map__action distributor-location-map__action--secondary"
                        href="<?= esc_url($args['website']); ?>"
                        data-partner-action="website"
                        target="_blank"
                        rel="noopener noreferrer"
                    ><?= esc_html__('Visit website', 'granola'); ?></a>
                <?php } ?>
            </div>
        </div>

    </div>
</section>