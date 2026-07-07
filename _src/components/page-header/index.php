<header <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="page-header__inner">
        <?php if (!empty($args['show_breadcrumbs'])) { ?>
            <!-- Breadcrumbs -->
            <div class="page-header__breadcrumbs">
                <?= \Granola\Component::get('breadcrumbs'); ?>
            </div>
        <?php } ?>

        <div class="page-header__wrapper">
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
