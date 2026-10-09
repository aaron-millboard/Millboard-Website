<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="hero-header__inner">
        <?php if (!empty($args['image'])) { ?>
            <div class="hero-header__media">
                <?php if (!empty($args['strapline'])) {
                    $strapline_text = is_array($args['strapline'])
                        ? ($args['strapline']['content'] ?? '')
                        : $args['strapline'];
                    $strapline_mask_id = \wp_unique_id('hero-header-cutout-');
                ?>
                    <div class="hero-header__strapline-wrapper">
                        <h1 class="hero-header__strapline">
                            <?php // Real heading text for SEO and screen readers; the visible
                                  // treatment is the SVG cutout below. ?>
                            <span class="visually-hidden"><?= esc_html($strapline_text); ?></span>

                            <?php // Crisp "video through text": a cream sheet covering the video
                                  // with the strapline knocked out via an SVG luminance mask, so
                                  // the video shows through the letters with clean, anti-aliased
                                  // edges (no mix-blend-mode fringing). ?>
                            <svg class="hero-header__strapline-cutout" aria-hidden="true" focusable="false" preserveAspectRatio="xMidYMid meet">
                                <mask id="<?= esc_attr($strapline_mask_id); ?>">
                                    <rect class="hero-header__strapline-cutout-sheet" width="100%" height="100%"></rect>
                                    <text class="hero-header__strapline-cutout-text" x="50%" y="50%" dy="0.35em" text-anchor="middle"><?= esc_html($strapline_text); ?></text>
                                </mask>
                                <rect class="hero-header__strapline-cutout-fill" width="100%" height="100%" mask="url(#<?= esc_attr($strapline_mask_id); ?>)"></rect>
                            </svg>
                        </h1>
                    </div>
                <?php } ?>

                <?php if (!empty($args['image'])) { ?>
                    <div class="hero-header__image-wrapper img-fit">
                        <?= \Granola\Component::get('image', $args['image']); ?>
                    </div>
                <?php } ?>

                <?php if (!empty($args['embed_url'])) { ?>
                    <iframe
                        src="<?= esc_attr($args['embed_url']); ?>"
                        data-embed-url="<?= esc_attr($args['embed_url']); ?>"
                        class="hero-header__iframe"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                    ></iframe>
                <?php } ?>

                <?php if (!empty($args['control_button'])) { ?>
                    <?= \Granola\Component::get('button', $args['control_button']); ?>
                <?php } ?>
            </div>
        <?php } ?>

        <?php if (!empty($args['heading']) || !empty($args['ctas'])) { ?>
            <div class="hero-header__header alignwide">
                <div class="hero-header__content">
                    <?php if (!empty($args['preheading'])) { ?>
                        <div class="hero-header__preheading is-style-typestyle-h6">
                            <?= wp_kses_post($args['preheading']); ?>
                        </div>
                    <?php } ?>

                    <?= \Granola\Component::get('heading', $args['heading']); ?>

                    <?= \Granola\Component::get('link', $args['link']); ?>
                </div>

                <?php if (!empty($args['ctas'])) { ?>
                    <div class="hero-header__ctas">
                        <?php foreach ($args['ctas'] as $cta) { ?>
                            <div class="hero-header__cta">
                                <?php if (!empty($cta['image_desktop'])) { ?>
                                    <?= \Granola\Component::get('image', $cta['image_desktop']); ?>
                                <?php } ?>

                                <?php if (!empty($cta['image_mobile'])) { ?>
                                    <?= \Granola\Component::get('image', $cta['image_mobile']); ?>
                                <?php } ?>

                                <?php if (!empty($cta['link'])) { ?>
                                    <?= \Granola\Component::get('link', $cta['link']); ?>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>
