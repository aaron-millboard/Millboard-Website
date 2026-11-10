<?php

namespace Granola\Components\SpecSystem;

/**
 * A system or product band: an image beside a description, a list of key facts
 * and up to three buttons.
 *
 * It is the media-content block with a facts list inside the text column, which
 * media-content cannot hold. The look, the image, the heading and the buttons
 * come from media-content itself (this block hands it its own arguments), so the
 * two always match. media-content is used on live pages and is left alone.
 */
function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'attributes' => [],
        'preheading' => '',
        'heading' => '',
        'content' => '',
        'image' => 0,
        'media_side' => 'left',
        'facts' => [],
        'buttons' => [],
    ], $args);

    if (empty($args['heading'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // The facts list goes inside the description, after the editor's text.
    // -------------------------------------------------------------------------
    $facts = array_values(array_filter((array) $args['facts'], static function ($fact) {
        return !empty($fact['label']) && !empty($fact['value']);
    }));

    $facts_html = '';

    if ($facts) {
        $facts_html = '<dl class="spec-system__facts">';

        foreach ($facts as $fact) {
            $facts_html .= '<div class="spec-system__fact"><dt>' . \esc_html($fact['label']) . '</dt><dd>' . \esc_html($fact['value']) . '</dd></div>';
        }

        $facts_html .= '</dl>';
    }

    // -------------------------------------------------------------------------
    // Buttons: the first is the outlined button, the rest are text buttons.
    // -------------------------------------------------------------------------
    $buttons = [];

    foreach (array_values((array) $args['buttons']) as $index => $row) {
        if (empty($row['button']['url'])) {
            continue;
        }

        $buttons[] = [
            'button' => $row['button'],
            'weight' => $index === 0 ? 'primary' : 'secondary',
        ];
    }

    // -------------------------------------------------------------------------
    // The background classes the block supports add to the media-content root.
    // -------------------------------------------------------------------------
    $classes = ['spec-system', 'alignfull'];

    // The editor's colour arrives as background_color. This block's own classes are never drawn
    // (media-content draws the band), so the colour class goes on the classes handed on below.
    $background = (string) ($args['background_color'] ?? '');

    if ($background !== '' && $background !== 'none') {
        $classes[] = 'has-' . $background . '-background-color';
        $classes[] = 'has-background';
    }

    // The anchor the editor sets has to land on the element that is drawn.
    $attributes = !empty($args['anchor']) ? ['id' => $args['anchor']] : [];

    // -------------------------------------------------------------------------
    // The image carries its library alt text: the product photograph is informative.
    // -------------------------------------------------------------------------
    $image = [];

    if (!empty($args['image'])) {
        $image = [
            'attachment_id' => $args['image'],
            'alt' => (string) \get_post_meta((int) $args['image'], '_wp_attachment_image_alt', true),
        ];
    }

    // -------------------------------------------------------------------------
    // What media-content is handed.
    // -------------------------------------------------------------------------
    $args['media_content'] = [
        'meta_prefix' => $args['preheading'],
        'heading' => $args['heading'],
        'subheading' => $args['content'] . $facts_html,
        'media_type' => 'image',
        'media_side' => $args['media_side'] === 'right' ? 'right' : 'left',
        'image' => $image,
        'buttons' => $buttons,
        'classes' => $classes,
        'attributes' => $attributes,
    ];

    return $args;
}
