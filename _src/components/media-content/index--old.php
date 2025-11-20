<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="media-content__inner">
        <div class="media-content__content">
            <?php if (!empty($args['heading'])) { ?>
                <?= \Granola\Component::get('heading', [
                    'content' => $args['heading'],
                    'classes' => ['media-content__heading'],
                ]); ?>
            <?php } ?>

            <?php if (!empty($args['subheading'])) { ?>
                <?= \Granola\Component::get('heading', [
                    'content' => $args['subheading'],
                    'el'      => 'h3',
                    'classes' => ['media-content__subheading'],
                ]); ?>
            <?php } ?>

            <?php if (!empty($args['content'])) { ?>
                <?= wp_kses_post($args['content']); ?>
            <?php } ?>

            <?php if (!empty($args['button_1'])) { ?>
                <div class="flex-list">
                    <?= \Granola\Component::get('link', $args['button_1']); ?>
                </div>
            <?php } ?>
        </div>

        <?php if (!empty($args['media']) || !empty($args['image'])) { ?>
            <div class="media-content__media img-fit">
                <?php if ($args['media_type'] === 'video' && !empty($args['video'])) {
                    echo \Granola\Component::get('video-item', $args['video']);
                } elseif ($args['media_type'] === 'image' && !empty($args['image'])) {
                    echo \Granola\Component::get('image', $args['image']);
                } ?>
            </div>
        <?php } ?>
    </div>
</div>
