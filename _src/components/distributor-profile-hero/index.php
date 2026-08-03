<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>

    <?php if (!empty($args['show_breadcrumbs'])) { ?>
        <div class="distributor-profile-hero__breadcrumbs">
            <div class="distributor-profile-hero__breadcrumbs-inner">
                <?= \Granola\Component::get('breadcrumbs'); ?>
            </div>
        </div>
    <?php } ?>

    <div class="distributor-profile-hero__inner">

        <?php if (!empty($args['preheading'])) { ?>
            <p class="distributor-profile-hero__preheading"><?= esc_html($args['preheading']); ?></p>
        <?php } ?>

        <h1 class="distributor-profile-hero__heading"><?= esc_html($args['heading']); ?></h1>

        <?php if (!empty($args['address'])) { ?>
            <p class="distributor-profile-hero__address">
                <svg class="distributor-profile-hero__pin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                    <circle cx="12" cy="10" r="2.6"></circle>
                </svg>
                <?= esc_html($args['address']); ?>
            </p>
        <?php } ?>

    </div>
</section>