<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="gallery__list__wrapper">
        <?= \Granola\Component::get('gallery/items', [
            'image_rows' => $args['image_rows'],
            'lightbox' => $args['lightbox'],
        ]); ?>
    </div>

    <?php if ($args['lightbox']) { ?>
        <dialog <?= \Granola\Helpers::build_attributes($args['lighbox_attributes']); ?>>
            <main class="gallery__lightbox__main" role="document">
                <figure class="gallery__lightbox__main-image">
                    <div class="gallery__lightbox__main-image__inner img-fit" data-image-orientation="">
                        <img class="gallery__lightbox__image" src="" alt="">
                    </div>
                    <div class="gallery__lightbox__main-image__caption is-style-typestyle-small is-style-typestyle-meta">
                        <div class="gallery__lightbox__main-image__caption__main"></div>
                        <div class="gallery__lightbox__main-image__caption__secondary"></div>
                    </div>
                </figure>

                <div class="gallery__lightbox__content">
                    <?= \Granola\Component::get('button', $args['lighbox_close_button']); ?>

                    <div class="gallery__lightbox__counter is-style-typestyle-meta">
                        <span class="gallery__lightbox__counter__current">1</span>
                        <span class="gallery__lightbox__counter__separator">/</span>
                        <span class="gallery__lightbox__counter__total"><?= $args['total_images']; ?></span>
                        <span class="gallery__lightbox__counter__label"><?=  $args['total_images_label'] ?></span>
                    </div>

                    <div class="gallery__lightbox__announcement visually-hidden" aria-live="polite" aria-atomic="true"></div>

                    <div class="gallery__lightbox__thumbnails" aria-label="<?= __('Thumbnails', 'granola'); ?>">
                        <?php if ($args['thumbnail_navigation'] && isset($args['controls']['previous'])) { ?>
                            <?= \Granola\Component::get('button', $args['controls']['previous']); ?>
                        <?php } ?>

                        <ul class="gallery__lightbox__thumbnails__list">
                            <?php foreach ($args['images'] as $image) { ?>
                                <li class="gallery__lightbox__thumbnail img-fit">
                                    <button <?= \Granola\Helpers::build_attributes($image['button_attributes']); ?>></button>
                                    <?= \Granola\Component::get('image', $image['image_thumbnail']); ?>
                                </li>
                            <?php } ?>
                        </ul>

                        <?php if ($args['thumbnail_navigation'] && isset($args['controls']['next'])) { ?>
                            <?= \Granola\Component::get('button', $args['controls']['next']); ?>
                        <?php } ?>
                    </div>
                </div>
            </main>
        </dialog>
    <?php } ?>
</section>
