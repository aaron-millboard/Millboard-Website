<?php
/**
 * Page header.
 *
 * The `layout` arg selects the composition for `page` type headers:
 *  - `editorial` — full-bleed photo behind the whole header, text in the calm
 *    side of a directional scrim. Falls back to a flat colour with no image.
 *  - `split`     — text panel beside an image column that bleeds off the right.
 *  - `classic`   — the original agency layout, where the image is deliberately
 *    oversized so the heading runs across it.
 *
 * `post` and `product` type headers always use `classic` and are unaffected.
 */

$layout = $args['layout'] ?? 'classic';
$has_media_layer = !empty($args['image']) && in_array($layout, ['editorial', 'split'], true);
$video = $has_media_layer ? ($args['background_video'] ?? null) : null;
?>
<header <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?php if ($has_media_layer) { ?>
        <div class="page-header__media">
            <div class="page-header__media-image img-fit">
                <?= \Granola\Component::get('image', $args['image']); ?>
            </div>

            <?php if (!empty($video)) { ?>
                <?php // Decorative. The image below stays as the poster frame and the
                      // fallback for reduced motion, no JS, and unsupported formats. ?>
                <video
                    class="page-header__media-video"
                    data-page-header-video
                    muted
                    loop
                    playsinline
                    preload="none"
                    tabindex="-1"
                    aria-hidden="true"
                >
                    <source src="<?= esc_url($video['url']); ?>" type="<?= esc_attr($video['mime_type']); ?>">
                </video>
            <?php } ?>

            <?php if ($layout === 'editorial') { ?>
                <div class="page-header__scrim" aria-hidden="true"></div>
            <?php } ?>
        </div>

        <?php if (!empty($video)) { ?>
            <?php // WCAG 2.2 SC 2.2.2: moving content that starts automatically and
                  // runs for more than five seconds needs a way to pause it. Added by
                  // JS only, so it never appears when the video cannot play. ?>
            <button
                type="button"
                class="page-header__video-toggle"
                data-page-header-video-toggle
                data-label-pause="<?= esc_attr__('Pause background video', 'granola'); ?>"
                data-label-play="<?= esc_attr__('Play background video', 'granola'); ?>"
                hidden
            >
                <span class="page-header__video-toggle-icon page-header__video-toggle-icon--pause" data-page-header-video-icon="pause" aria-hidden="true"></span>
                <span class="page-header__video-toggle-icon page-header__video-toggle-icon--play" data-page-header-video-icon="play" aria-hidden="true" hidden></span>
                <span class="page-header__video-toggle-label"><?= esc_html__('Pause background video', 'granola'); ?></span>
            </button>
        <?php } ?>
    <?php } ?>

    <div class="page-header__inner">
        <?php if (!empty($args['show_breadcrumbs'])) { ?>
            <!-- Breadcrumbs -->
            <div class="page-header__breadcrumbs">
                <?= \Granola\Component::get('breadcrumbs'); ?>
            </div>
        <?php } ?>

        <div class="page-header__wrapper">
            <?php if ($layout === 'classic') { ?>
                <div class="<?= \Granola\Helpers::build_classes([
                    'page-header__image-wrapper',
                    empty($args['image']) ? 'page-header__image-wrapper--no-image' : '',
                ]); ?>">
                    <?php if (!empty($args['image'])) { ?>
                        <div class="page-header__image">
                            <div class="page-header__image-inner img-fit">
                                <?= \Granola\Component::get('image', $args['image']); ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>

            <div class="page-header__content page-header__content--first">
                <div class="page-header__header">
                    <?php if (!empty($args['preheading'])) { ?>
                        <div class="page-header__preheading">
                            <?= wp_kses_post($args['preheading']); ?>
                        </div>
                    <?php } ?>

                    <?php if (!empty($args['heading'])) { ?>
                        <?= \Granola\Component::get('heading', $args['heading']); ?>
                    <?php } ?>
                </div>

            <?php if ($args['type'] == 'page') : // Close the first content div here if the type is 'page' and opening a new one for the rest of the content. ?>
                </div>
                <div class="page-header__content page-header__content--last">
            <?php endif; ?>
                <?php if (!empty($args['description'])) { ?>
                    <div class="page-header__description">
                        <?= wp_kses_post($args['description']['content']); ?>
                    </div>
                <?php } ?>

                <?php if (!empty($args['cta'])) { ?>
                    <?= \Granola\Component::get('link', $args['cta']); ?>
                <?php } ?>

                <?php if (\is_search()) { ?>
                    <?= \Granola\Component::get('search-form'); ?>
                <?php } ?>

                <?php if (!empty($args['author_info']['display_name'])) { ?>
                    <div class="page-header__author">
                        <?php if (!empty($args['author_info']['image']['attachment_id'])) { ?>
                            <div class="page-header__author-avatar img-fit">
                                <?= \Granola\Component::get('image', $args['author_info']['image']); ?>
                            </div>
                        <?php } ?>

                        <div class="page-header__author-content">
                            <p class="page-header__author-name is-style-typestyle-h6">
                                <?= esc_html(sprintf(__('By %s', 'granola'), $args['author_info']['display_name'])); ?>
                            </p>

                            <?php if (!empty($args['author_info']['bio'])) { ?>
                                <p class="page-header__author-bio">
                                    <?= esc_html($args['author_info']['bio']); ?>
                                </p>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <?php if (!empty($args['bg_gradient'])) { ?>
        <div class="page-header__background">
            <div class="page-header__background-left"></div>
            <div class="page-header__background-top"></div>
            <div class="page-header__background-bottom"></div>
        </div>
    <?php } ?>
</header>

<?php if (!\is_product()) { ?>
    <?php woocommerce_output_all_notices(); ?>
<?php } ?>
