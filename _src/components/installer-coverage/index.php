<?php
$is_chips = ($args['display'] ?? 'rows') === 'chips';

// Allowed tags for a pasted Google Maps embed iframe.
$iframe_kses = [
    'iframe' => [
        'src' => true,
        'width' => true,
        'height' => true,
        'style' => true,
        'frameborder' => true,
        'allowfullscreen' => true,
        'loading' => true,
        'referrerpolicy' => true,
        'title' => true,
        'aria-label' => true,
        // Both carry the Perfmatters lazy-load opt-out added in functions.php. Without
        // them here wp_kses strips the pair straight back out and the map stays blank.
        'class' => true,
        'data-no-lazy' => true,
    ],
];
?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="installer-coverage__inner">
        <div class="installer-coverage__grid">

            <?php if (!empty($args['has_map'])) { ?>
                <div class="installer-coverage__map">
                    <?php if ($args['map_type'] === 'embed') { ?>
                        <div class="installer-coverage__map-embed"><?= wp_kses($args['map_embed'], $iframe_kses); ?></div>
                    <?php } else { ?>
                        <?= wp_get_attachment_image($args['map_image'], 'large', false, ['class' => 'installer-coverage__map-image']); ?>
                    <?php } ?>
                </div>
            <?php } ?>

            <div class="installer-coverage__content">
                <span class="installer-coverage__rule" aria-hidden="true"></span>
                <?php if (!empty($args['heading'])) { ?>
                    <h2 class="installer-coverage__heading"><?= esc_html($args['heading']); ?></h2>
                <?php } ?>
                <?php if (!empty($args['intro'])) { ?>
                    <p class="installer-coverage__intro"><?= esc_html($args['intro']); ?></p>
                <?php } ?>

                <?php if (!empty($args['coverage'])) { ?>
                    <div class="installer-coverage__areas installer-coverage__areas--<?= $is_chips ? 'chips' : 'rows'; ?>">
                        <?php foreach ($args['coverage'] as $area) { ?>
                            <div class="installer-coverage__area">
                                <span class="installer-coverage__area-county"><?= esc_html($area['county']); ?></span>
                                <?php if ($is_chips) { ?>
                                    <div class="installer-coverage__area-chips">
                                        <?php foreach ($area['towns_list'] as $town) { ?>
                                            <span class="installer-coverage__chip"><?= esc_html($town); ?></span>
                                        <?php } ?>
                                    </div>
                                <?php } else { ?>
                                    <span class="installer-coverage__area-towns"><?= esc_html(implode(' · ', $area['towns_list'] ?: [$area['towns']])); ?></span>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>

                <?php
                $button = $args['button'] ?? null;
                if (!empty($button) && !empty($button['url'])) {
                    $target = !empty($button['target']) ? ' target="' . esc_attr($button['target']) . '" rel="noopener"' : '';
                    $text = !empty($button['title']) ? $button['title'] : \__('Book a site visit', 'granola');
                    ?>
                    <a class="installer-coverage__btn" href="<?= esc_url($button['url']); ?>"<?= $target; ?>><span><?= esc_html($text); ?></span><svg class="installer-coverage__arrow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></a>
                <?php } ?>
            </div>

        </div>
    </div>
</section>
