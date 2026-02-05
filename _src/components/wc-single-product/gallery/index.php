<div class="woocommerce-product-gallery" data-columns="3" style="opacity: 0; transition: opacity .25s ease-in-out;">
    <div class="woocommerce-product-gallery__wrapper">
        <?php if (!empty($args['attachment_ids']) && !empty($args['post_thumbnail_id'])) { ?>
            <?php foreach ($args['attachment_ids'] as $key => $attachment_id) { ?>
                <?= \wc_get_gallery_image_html($attachment_id, false, $key); ?>
            <?php } ?>
        <?php } ?>
    </div>
</div>
