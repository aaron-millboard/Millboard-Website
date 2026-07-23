<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="hero-header__inner">
        <?php if (!empty($args['image'])) { ?>
            <div class="hero-header__media">
                <?php if (!empty($args['strapline'])) { ?>
                    <div class="hero-header__strapline-wrapper-outer">
                        <div class="hero-header__strapline-wrapper-inner">
                            <?= \Granola\Component::get('heading', $args['strapline']); ?>
                        </div>
                    </div>

                    <div class="hero-header__strapline-wrapper-outer" aria-hidden="true">
                        <div class="hero-header__strapline-wrapper-inner">
                            <?= \Granola\Component::get('heading', $args['strapline']); ?>
                        </div>
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
