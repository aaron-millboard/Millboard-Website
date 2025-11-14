<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <button class="video-item__play-button js-video-item-play">
        <span class="visually-hidden">
            <?= esc_html__('Play video', 'granola'); ?>
        </span>
    </button>

    <?php if (!empty($args['image'])) { ?>
        <div class="video-item__media img-fit">
            <?= \Granola\Component::get('image', $args['image']); ?>
        </div>
    <?php } ?>

    <div class="video-item__video">
        <div class="video-item__video-inner">

            <div class="video-item__video-wrap">
                <button class="video-item__video-close cross">
                    <span class="visually-hidden">
                        <?= esc_html__('Close Video', 'granola') ?>
                    </span>
                </button>
                <?= $args['video'] ?>
            </div>
        </div>
    </div>
</div>
