<div class="woocommerce-product-gallery">
    <div class="woocommerce-product-gallery__wrapper">
        <?php foreach ($args['attachment_ids'] as $key => $attachment_id) { ?>
            <div class="woocommerce-product-gallery__image">
                <?= \Granola\Component::get('image', [
                    'attachment_id' => $attachment_id,
                    'size' => 'full',
                ]); ?>
            </div>
        <?php } ?>
    </div>
</div>
