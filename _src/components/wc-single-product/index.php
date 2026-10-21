<?php woocommerce_output_all_notices(); ?>

<?php
// Rendered up front so the sample check can decide whether the group shows.
$visualiser = \Granola\Component::get('product-visualiser');
$samples = \Granola\Component::get('product-samples');
$has_samples = str_contains($samples, 'product-samples__button');
?>

<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="product__gallery">
        <?php echo \Granola\Component::get('wc-single-product/gallery'); ?>
    </div>

    <div class="product__content">
        <div class="product__content-section product__header">
            <?php if (!empty($args['preheading'])) { ?>
                <div class="product__preheading">
                    <?= wp_kses_post($args['preheading']); ?>
                </div>
            <?php } ?>

            <?php if (!empty($args['heading'])) { ?>
                <h1 class="product__heading">
                    <?= wp_kses_post($args['heading']); ?>
                </h1>
            <?php } ?>

            <?php if (!empty($args['description'])) { ?>
                <div class="product__description">
                    <?= wp_kses_post($args['description']); ?>
                </div>
            <?php } ?>

            <?php if (!empty($args['header_cta'])) { ?>
                <div class="product__header-cta">
                    <?= \Granola\Component::get('link', $args['header_cta']); ?>
                </div>
            <?php } ?>

            <?php if (!empty($args['nbs_source_url'])) { ?>
                <div class="product__nbs-source">
                    <?= \Granola\Component::get('link', [
                        'url' => $args['nbs_source_url'],
                        'content' => esc_html__('View on NBS Source', 'granola'),
                        'target' => '_blank',
                        'classes' => ['g-button'],
                    ]); ?>
                </div>
            <?php } ?>
        </div>

        <?php
        if (!empty($args['selectors'])) {
            foreach ($args['selectors'] as $variation) {
                echo \Granola\Component::get('product-variation-selector', $variation);
            }
        }
        ?>

        <?php
            /*
             * Hook: woocommerce_single_product_summary.
             *
             * NB: Some hooked functions have been unhooked.
             *
             * @hooked woocommerce_template_single_add_to_cart - 30
             * @hooked WC_Structured_Data::generate_product_data() - 60
             */
            do_action('woocommerce_single_product_summary');
        ?>

        <?php if ($has_samples) { ?>
            <div class="product__content-section product__try">
                <h2 class="product__try-heading"><?= esc_html__('Try before you buy', 'granola'); ?></h2>
                <p class="product__try-intro"><?= esc_html__('Order a sample to see and feel the board at home.', 'granola'); ?></p>

                <?= $samples; ?>
            </div>
        <?php } ?>

        <?= $visualiser; ?>
    </div>
</div>
