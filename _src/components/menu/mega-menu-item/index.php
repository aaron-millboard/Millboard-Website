<?php

/**
 * One mega menu entry, drawn as a card, a rail line or a titled paragraph.
 *
 * The arrows are decorative: the link's own text says where it goes, and an
 * announced arrow would only add "link" noise to every line in the panel.
 */

$link_attributes = [];

if (!empty($args['link']['target'])) {
    $link_attributes[] = 'target="' . esc_attr($args['link']['target']) . '" rel="noopener"';
}

if (!empty($args['link']['attr_title'])) {
    $link_attributes[] = 'title="' . esc_attr($args['link']['attr_title']) . '"';
}

$link_attributes = $link_attributes ? ' ' . implode(' ', $link_attributes) : '';

?>
<li <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>

    <?php if ($args['shape'] === 'card') { ?>
        <a class="mega-menu-item__card" href="<?= esc_url($args['link']['url']); ?>"<?= $link_attributes; ?>>
            <span class="mega-menu-item__frame">
                <?php if (!empty($args['item_image'])) { ?>
                    <?= \Granola\Component::get('image', [
                        'attachment_id' => $args['item_image'],
                        'alt' => '',
                        'size' => 'medium_large',
                        'classes' => ['mega-menu-item__image'],
                    ]); ?>
                <?php } ?>
            </span>

            <span class="mega-menu-item__title">
                <?= esc_html($args['link']['title']); ?>
                <svg class="mega-menu-item__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <path d="M4 12h15M14 7l5 5-5 5"></path>
                </svg>
            </span>
        </a>

    <?php } elseif ($args['shape'] === 'rail') { ?>
        <a class="mega-menu-item__rail" href="<?= esc_url($args['link']['url']); ?>"<?= $link_attributes; ?>>
            <?= esc_html($args['link']['title']); ?>
            <svg class="mega-menu-item__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <path d="m9 5 7 7-7 7"></path>
            </svg>
        </a>

    <?php } else { ?>
        <a class="mega-menu-item__text" href="<?= esc_url($args['link']['url']); ?>"<?= $link_attributes; ?>>
            <span class="mega-menu-item__title">
                <?= esc_html($args['link']['title']); ?>
            </span>

            <?php if (!empty($args['description'])) { ?>
                <span class="mega-menu-item__description">
                    <?= esc_html($args['description']); ?>
                </span>
            <?php } ?>
        </a>
    <?php } ?>

</li>
