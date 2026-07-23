<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="installer-profile-header__breadcrumbs">
        <?= \Granola\Component::get('breadcrumbs'); ?>
    </div>

    <div class="installer-profile-header__inner">

        <div class="installer-profile-header__body">
            <?php if (!empty($args['tier_label'])) { ?>
                <p class="installer-profile-header__eyebrow"><?= esc_html($args['tier_label']); ?></p>
            <?php } ?>

            <h1 class="installer-profile-header__title"><?= esc_html($args['title']); ?></h1>

            <?php if (!empty($args['tagline'])) { ?>
                <p class="installer-profile-header__tagline"><?= esc_html($args['tagline']); ?></p>
            <?php } ?>

            <?php if (!empty($args['address_text'])) { ?>
                <p class="installer-profile-header__location">
                    <svg class="installer-profile-header__pin" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="2.6"></circle></svg>
                    <span><?= esc_html($args['address_text']); ?></span>
                </p>
            <?php } ?>

            <?php if (!empty($args['has_rating'])) { ?>
                <?php
                $rating_inner = '<span class="installer-profile-header__stars" aria-hidden="true">';
                for ($i = 0; $i < 5; $i++) {
                    $rating_inner .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" focusable="false"><path d="M12 2.4l2.85 6.5 7.05.6-5.35 4.65 1.6 6.9L12 17.9 5.9 21.55l1.6-6.9L2.1 9.5l7.05-.6z"></path></svg>';
                }
                $rating_inner .= '</span>';
                $rating_inner .= '<span class="installer-profile-header__rating-value">' . esc_html($args['rating']) . '</span>';
                if (!empty($args['review_count'])) {
                    /* translators: %s: number of reviews. */
                    $rating_inner .= '<span class="installer-profile-header__rating-count">' . esc_html(sprintf(\__('· %s reviews', 'granola'), $args['review_count'])) . '</span>';
                }
                ?>
                <?php if (!empty($args['reviews_url'])) { ?>
                    <a class="installer-profile-header__rating" href="<?= esc_url($args['reviews_url']); ?>"><?= $rating_inner; ?></a>
                <?php } else { ?>
                    <div class="installer-profile-header__rating"><?= $rating_inner; ?></div>
                <?php } ?>
            <?php } ?>

            <?php if (!empty($args['buttons'])) { ?>
                <div class="installer-profile-header__actions">
                    <?php foreach ($args['buttons'] as $button) { ?>
                        <a class="installer-profile-header__btn installer-profile-header__btn--<?= esc_attr($button['variant']); ?>" href="<?= esc_url($button['href']); ?>"><?= esc_html($button['text']); ?></a>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>

        <?php if (!empty($args['cover_image'])) { ?>
            <div class="installer-profile-header__media">
                <?= wp_get_attachment_image(
                    $args['cover_image'],
                    'large',
                    false,
                    [
                        'class' => 'installer-profile-header__cover',
                        'loading' => 'eager',
                    ]
                ); ?>

                <?php if (!empty($args['badge_image'])) { ?>
                    <span class="installer-profile-header__badge installer-profile-header__badge--image">
                        <?= wp_get_attachment_image($args['badge_image'], 'medium', false, ['class' => 'installer-profile-header__badge-img']); ?>
                    </span>
                <?php } else { ?>
                    <span class="installer-profile-header__badge">
                        <span class="installer-profile-header__badge-brand">Millboard</span>
                        <span class="installer-profile-header__badge-tier"><?= esc_html($args['tier']); ?> Installer</span>
                    </span>
                <?php } ?>
            </div>
        <?php } ?>

    </div>
</section>
