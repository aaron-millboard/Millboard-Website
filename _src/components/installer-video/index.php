<?php
$has_video = !empty($args['video_url']);
$has_cover = !empty($args['cover_image']);
?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="installer-video__inner">

        <div class="installer-video__text">
            <?php if (!empty($args['eyebrow'])) { ?>
                <p class="installer-video__eyebrow"><?= esc_html($args['eyebrow']); ?></p>
            <?php } ?>
            <?php if (!empty($args['heading'])) { ?>
                <h2 class="installer-video__heading"><?= esc_html($args['heading']); ?></h2>
            <?php } ?>
            <?php if (!empty($args['intro'])) { ?>
                <p class="installer-video__intro"><?= esc_html($args['intro']); ?></p>
            <?php } ?>
        </div>

        <?php if ($has_cover || $has_video) { ?>
            <?php
            $tag = $has_video ? 'button' : 'div';
            $attrs = 'class="installer-video__media' . ($has_video ? ' installer-video__media--playable' : '') . '"';
            if ($has_video) {
                $attrs .= ' type="button" data-video="' . esc_url($args['video_url']) . '" aria-label="' . esc_attr(sprintf(\__('Play video: %s', 'granola'), $args['heading'])) . '"';
            }
            ?>
            <<?= $tag; ?> <?= $attrs; ?>>
                <?php if ($has_cover) { ?>
                    <?= wp_get_attachment_image($args['cover_image'], 'large', false, ['class' => 'installer-video__cover']); ?>
                <?php } ?>
                <?php if ($has_video) { ?>
                    <span class="installer-video__scrim" aria-hidden="true"></span>
                    <span class="installer-video__play" aria-hidden="true">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor" focusable="false"><path d="M8 5v14l11-7z"></path></svg>
                    </span>
                    <?php if (!empty($args['duration'])) { ?>
                        <span class="installer-video__duration"><?= esc_html($args['duration']); ?></span>
                    <?php } ?>
                <?php } ?>
            </<?= $tag; ?>>
        <?php } ?>

    </div>
</section>
