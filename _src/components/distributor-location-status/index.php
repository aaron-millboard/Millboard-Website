<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="distributor-location-status__inner">

        <?php if (!empty($args['address'])) { ?>
            <p class="distributor-location-status__address">
                <svg class="distributor-location-status__pin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                    <circle cx="12" cy="10" r="2.6"></circle>
                </svg>
                <?= esc_html($args['address']); ?>
            </p>
        <?php } ?>

        <?php if (!empty($args['badges']) || !empty($args['status'])) { ?>
            <div class="distributor-location-status__row">

                <?php if (!empty($args['badges'])) { ?>
                    <ul class="distributor-location-status__badges">
                        <?php foreach ($args['badges'] as $badge) { ?>
                            <li class="distributor-location-status__badge distributor-location-status__badge--<?= esc_attr($badge['modifier']); ?>">
                                <?php if (!empty($badge['dot'])) { ?>
                                    <span class="distributor-location-status__dot" aria-hidden="true"></span>
                                <?php } ?>

                                <?php if ($badge['icon'] === 'display') { ?>
                                    <svg class="distributor-location-status__badge-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                        <rect x="3" y="3" width="18" height="14" rx="1.5"></rect>
                                        <path d="M3 21h18M12 17v4"></path>
                                    </svg>
                                <?php } ?>

                                <?= esc_html($badge['label']); ?>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>

                <?php if (!empty($args['status'])) { ?>
                    <p class="distributor-location-status__open distributor-location-status__open--<?= esc_attr($args['status']['state']); ?>">
                        <span class="distributor-location-status__dot" aria-hidden="true"></span>
                        <?= esc_html($args['status']['label']); ?>
                    </p>
                <?php } ?>

            </div>
        <?php } ?>

    </div>
</section>
