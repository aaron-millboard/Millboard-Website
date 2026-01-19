<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="media-object__inner">
        <?php if ($args['media_position'] === 'before' && (!empty($args['video']) || !empty($args['media']))) { ?>
            <div class="media-object__media img-fit <?= $args['animate'] ? 'animate' : null; ?>">
                <?php if ($args['media_type'] === 'video' && !empty($args['video'])) { ?>
                    <?= \Granola\Component::get('video-item', $args['video']); ?>
                <?php } else { ?>
                    <?php if ($args['hover_effect']) { ?>
                        <div class="media-object__media--hover-effect">
                            <span class="media-object__media--hover-effect__top">
                                <?= __('View', 'granola'); ?>
                            </span>
                            <span class="media-object__media--hover-effect__bottom">
                                <?= __('Article', 'granola'); ?>
                            </span>
                        </div>
                    <?php } ?>
                    
                    <?= \Granola\Component::get('image', $args['media']); ?>
                <?php } ?>
            </div>
        <?php } ?>

        <div class="media-object__content flex-column">
            <?php if (!empty($args['meta_prefix'])) { ?>
                <div class="media-object__meta-prefix">
                    <?= wp_kses_post($args['meta_prefix']); ?>
                </div>
            <?php } ?>

            <?php if (!empty($args['icon'])) { ?>
                <?= \Granola\Component::get('icons', [
                    'icon' => $args['icon'],
                ]); ?>
            <?php } ?>

            <?php if (!empty($args['heading']) || !empty($args['text'])) { ?>
                <div class="media-object__header flex-column">
                    <?php if (!empty($args['heading'])) { ?>
                        <?= \Granola\Component::get('heading', $args['heading']); ?>
                    <?php } ?>

                     <?php if (!empty($args['subheading'])) { ?>
                        <div class="media-object__subheading">
                            <?= wp_kses_post($args['subheading']); ?>
                        </div>
                     <?php } ?>

                    <?php if (!empty($args['text'])) { ?>
                        <div class="media-object__text nflm is-style-typestyle-small">
                            <?= wp_kses_post($args['text']); ?>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>

            <?php if (!empty($args['meta_data'])) { ?>
                    <?= \Granola\Component::get('list', [
                    'items' => $args['meta'],
                    'parent_class_name' => 'media-object',
                    'classes' => ['media-object__meta', 'is-style-typestyle-meta'],
                ]); ?>
            <?php } ?>

            <?php if (!empty($args['labels'])) { ?>
                <?= \Granola\Component::get('list', [
                    'items' => $args['labels'],
                    'parent_class_name' => 'media-object',
                    'is_buttons' => false,
                    'classes' => ['media-object__labels'],
                ]); ?>
            <?php } ?>

            <?php if (!empty($args['buttons'])) { ?>
                <?= \Granola\Component::get('list', [
                    'items' => $args['buttons'],
                    'parent_class_name' => 'media-object',
                    'is_buttons' => true,
                    'classes' => ['media-object__buttons'],
                ]); ?>
            <?php } ?>
        </div>

        <?php if ($args['media_position'] === 'after') { ?>
            <div class="
                media-object__media 
                <?= $args['animate'] ? 'animate' : null; ?>
                <?= $args['media_type'] == 'illustration' ? 'img-contain' : 'img-fit'; ?>
                <?= 'media-object__media--' . $args['media_type']; ?>
            ">
                <?php if ($args['media_type'] === 'video' && !empty($args['video'])) { ?>
                    <?= \Granola\Component::get('video-item', $args['video']); ?>
                <?php } elseif ($args['media_type'] === 'illustration' && !empty($args['illustration'])) { ?>
                    <?= \Granola\SVG::get("illustrations/{$args['illustration']}.svg"); ?>
                <?php } elseif (!empty($args['media'])) { ?>
                    <?= \Granola\Component::get('image', $args['media']); ?>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

</div>
