<?php

/**
 * Specifier page header: the page-header block's split layout with a row of up
 * to three calls to action. See functions.php.
 */

?>
<header <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?php if (!empty($args['image'])) { ?>
        <div class="page-header__media">
            <div class="page-header__media-image img-fit">
                <?= \Granola\Component::get('image', $args['image']); ?>
            </div>
        </div>
    <?php } ?>

    <div class="page-header__inner">
        <?php if (!empty($args['show_breadcrumbs'])) { ?>
            <div class="page-header__breadcrumbs">
                <?php // The shared breadcrumbs nav has no accessible name; this block names it without touching the shared component. ?>
                <?= str_replace('<nav class="breadcrumbs"', '<nav class="breadcrumbs" aria-label="' . esc_attr__('Breadcrumb', 'granola') . '"', \Granola\Component::get('breadcrumbs')); ?>
            </div>
        <?php } ?>

        <div class="page-header__wrapper">
            <div class="page-header__content page-header__content--first">
                <div class="page-header__header">
                    <?php if (!empty($args['preheading'])) { ?>
                        <div class="page-header__preheading">
                            <?= wp_kses_post($args['preheading']); ?>
                        </div>
                    <?php } ?>

                    <?= \Granola\Component::get('heading', $args['heading']); ?>
                </div>
            </div>

            <div class="page-header__content page-header__content--last">
                <?php if (!empty($args['description'])) { ?>
                    <div class="page-header__description">
                        <?= wp_kses_post($args['description']['content']); ?>
                    </div>
                <?php } ?>

                <?php if (!empty($args['ctas'])) { ?>
                    <div class="spec-page-header__actions">
                        <?php foreach ($args['ctas'] as $cta) { ?>
                            <?= \Granola\Component::get('link', $cta); ?>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</header>
