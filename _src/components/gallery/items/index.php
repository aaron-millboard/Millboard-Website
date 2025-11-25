<ul <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?php foreach ($args['images'] as $key => $image) { ?>
        <li class="gallery__card">
            <div class="gallery__card__inner img-fit">
                <?php if ($args['lightbox']) { ?>
                    <button <?= \Granola\Helpers::build_attributes($image['button_attributes']); ?>></button>

                    <div class="gallery__card__button-icon"></div>
                <?php } ?>

                <?= \Granola\Component::get('image', $image['image_medium']); ?>
            </div>
            <div class="gallery__card__caption is-style-typestyle-small is-style-typestyle-meta">
                <div class="gallery__card__caption-main">
                    <?= esc_html($image['caption_main']); ?>
                </div>

                <?php if (!empty($image['caption_secondary'])) { ?>
                    <div class="gallery__card__caption-secondary">
                        <?= esc_html($image['caption_secondary']); ?>
                    </div>
                <?php } ?>
            </div>
        </li>
    <?php } ?>
</ul>
