<?php

    // We need to access the global product object
    global $product;

?>

<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>

    <div class="product__gallery">

        <?php echo \Granola\Component::get('wc-single-product/gallery'); ?>

    </div>

    <div class="product__content">

        <div class="product__content-section product__header">

            <?php if (!empty($args['preheading'])) { ?>
                <div class="product__preheading is-style-typestyle-h6">
                    <?= wp_kses_post($args['preheading']); ?>
                </div>
            <?php } ?>
            
            <?php if (!empty($args['heading'])) { ?>
                <h1 class="product__heading">
                    <?= wp_kses_post($args['heading']); ?>
                </h1>
            <?php } ?>

            <?php if (!empty($args['description'])) { ?>
                <div class="product__description is-style-typestyle-body">
                    <?= wp_kses_post($args['description']); ?>
                </div>
            <?php } ?>

            <?php if (!empty($args['header_cta'])) { ?>
                <div class="product__header-cta">
                    <?= \Granola\Component::get('link', $args['header_cta']); ?>
                </div>
            <?php } ?>

        </div>


        <?php

            /**
             * Hook: woocommerce_single_product_summary.
             *
             * @hooked woocommerce_template_single_title - 5
             * @hooked woocommerce_template_single_rating - 10
             * @hooked woocommerce_template_single_price - 10
             * @hooked woocommerce_template_single_excerpt - 20
             * @hooked woocommerce_template_single_add_to_cart - 30
             * @hooked woocommerce_template_single_meta - 40
             * @hooked woocommerce_template_single_sharing - 50
             * @hooked WC_Structured_Data::generate_product_data() - 60
             */

            do_action('woocommerce_single_product_summary');

        ?>

    </div>
</div>