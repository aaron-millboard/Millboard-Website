<ul <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?php foreach ($args['image_rows'] as $key => $image_row) { ?>
        <?php if (!empty($image_row['image_1'])) { ?>
            <li <?= \Granola\Helpers::build_attributes($image_row['image_1']['li_attributes']); ?>>
                <div class="gallery__card__inner img-fit">
                    <?php if ($args['lightbox']) { ?>
                        <button <?= \Granola\Helpers::build_attributes($image_row['image_1']['button_attributes']); ?>></button>

                        <div class="gallery__card__button-icon"></div>
                    <?php } ?>

                    <?= \Granola\Component::get('image', $image_row['image_1']['image_medium']); ?>
                </div>
                <div class="gallery__card__caption is-style-typestyle-small is-style-typestyle-meta">
                    <div class="gallery__card__caption-main">
                        <?= esc_html($image_row['image_1']['caption_main']); ?>
                    </div>

                    <?php if (!empty($image_row['image_1']['caption_secondary'])) { ?>
                        <div class="gallery__card__caption-secondary">
                            <?= esc_html($image_row['image_1']['caption_secondary']); ?>
                        </div>
                    <?php } ?>
                </div>
            </li>
        <?php } ?>

        <?php if (!empty($image_row['image_2'])) { ?>
            <li <?= \Granola\Helpers::build_attributes($image_row['image_2']['li_attributes']); ?>>
                <div class="gallery__card__inner img-fit">
                    <?php if ($args['lightbox']) { ?>
                        <button <?= \Granola\Helpers::build_attributes($image_row['image_2']['button_attributes']); ?>></button>

                        <div class="gallery__card__button-icon"></div>
                    <?php } ?>

                    <?= \Granola\Component::get('image', $image_row['image_2']['image_medium']); ?>
                </div>
                <div class="gallery__card__caption is-style-typestyle-small is-style-typestyle-meta">
                    <div class="gallery__card__caption-main">
                        <?= esc_html($image_row['image_2']['caption_main']); ?>
                    </div>

                    <?php if (!empty($image_row['image_2']['caption_secondary'])) { ?>
                        <div class="gallery__card__caption-secondary">
                            <?= esc_html($image_row['image_2']['caption_secondary']); ?>
                        </div>
                    <?php } ?>
                </div>
            </li>
        <?php } ?>
    <?php } ?>
</ul>
