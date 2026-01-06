<?php

use Automattic\WooCommerce\Enums\ProductType;

defined('ABSPATH') || exit;

// Note: `wc_get_gallery_image_html` was added in WC 3.3.2 and did not exist prior. This check protects against theme overrides being used on older versions of WC.
if (! function_exists('wc_get_gallery_image_html')) {
    return;
}

global $product;

$post_thumbnail_id = $product->get_image_id();
$attachment_ids = $product->get_gallery_image_ids();

?>

<div class="woocommerce-product-gallery" data-columns="3" style="opacity: 0; transition: opacity .25s ease-in-out;">

    <div class="woocommerce-product-gallery__wrapper">
        <?php
        if ($post_thumbnail_id) {
            $html = wc_get_gallery_image_html($post_thumbnail_id, true);
        } else {
            $wrapper_classname = $product->is_type(ProductType::VARIABLE) && ! empty($product->get_available_variations('image')) ?
                'woocommerce-product-gallery__image woocommerce-product-gallery__image--placeholder' :
                'woocommerce-product-gallery__image--placeholder';
            $html              = sprintf('<div class="%s">', esc_attr($wrapper_classname));
            $html             .= sprintf('<img src="%s" alt="%s" class="wp-post-image" />', esc_url(wc_placeholder_img_src('woocommerce_single')), esc_html__('Awaiting product image', 'woocommerce'));
            $html             .= '</div>';
        }

        if ($attachment_ids && $product->get_image_id()) {
            foreach ($attachment_ids as $key => $attachment_id) {
                echo wc_get_gallery_image_html($attachment_id, false, $key);
            }
        }

        ?>
    </div>
</div>
