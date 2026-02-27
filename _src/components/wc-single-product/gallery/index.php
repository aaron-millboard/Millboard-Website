<div class="woocommerce-product-gallery">
    <div class="woocommerce-product-gallery__wrapper">
        <?php foreach ($args['attachment_ids'] as $key => $attachment_id) { ?>
            <div class="woocommerce-product-gallery__image">
                <?= \Granola\Component::get('image', [
                    'attachment_id' => $attachment_id,
                    'size' => '2048x2048',
                    'attributes' => $key === 0 ? [
                        'fetchpriority' => 'high',
                        'data-spai-eager' => true,
                    ] : null,
                    'loading' => $key === 0 ? 'eager' : 'lazy',
                ]); ?>
            </div>
        <?php } ?>
    </div>
</div>
