<?php

namespace Granola\Components\SpecContact;

/**
 * Contact row: a column of text and a named adviser beside an enquiry form.
 *
 * The form is a HubSpot embed, wrapped in the same `.gravity-forms` wrapper the
 * gravity-forms block uses, so the boxed fields and labels are the site's own
 * form styling and not a second copy of it. The theme's InlineEmbeds filter adds
 * `css: ''` to the embed so the form is drawn in the page, not an iframe.
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
        'person_image' => 0,
        'person_name' => '',
        'person_role' => '',
        'note' => '',
        'hs_form_id' => '',
        'hs_portal_id' => '26853518',
        'hs_region' => 'eu1',
    ], $args);

    if (empty($args['heading']) && empty($args['hs_form_id']) && empty($args['is_preview'])) {
        return null;
    }

    // The embed takes three ids; keep them to what each can hold.
    $args['hs_form_id'] = preg_replace('/[^a-zA-Z0-9\-]/', '', (string) $args['hs_form_id']);
    $args['hs_portal_id'] = preg_replace('/[^0-9]/', '', (string) $args['hs_portal_id']);
    $args['hs_region'] = preg_replace('/[^a-z0-9]/', '', strtolower((string) $args['hs_region']));

    $args['has_form'] = $args['hs_form_id'] !== '' && $args['hs_portal_id'] !== '' && $args['hs_region'] !== '';

    if (!empty($args['person_image'])) {
        $args['person_image_data'] = [
            'attachment_id' => $args['person_image'],
            'size' => 'thumbnail',
            // The name is printed beside the photograph, so the photograph is decorative: alt text
            // that repeated the name would make a screen reader read it twice.
            'alt' => '',
        ];
    }

    // The note can hold a phone number and an email address: make both tappable.
    $args['note_html'] = '';

    if (!empty($args['note'])) {
        $note = \esc_html($args['note']);
        $note = preg_replace_callback('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', static function ($m) {
            return '<a href="mailto:' . \esc_attr($m[0]) . '">' . $m[0] . '</a>';
        }, $note);
        $note = preg_replace_callback('/\+?\d[\d ()]{7,}\d/', static function ($m) {
            $digits = preg_replace('/[^\d+]/', '', preg_replace('/\(0\)/', '', $m[0]));

            return '<a href="tel:' . \esc_attr($digits) . '">' . $m[0] . '</a>';
        }, $note);
        $args['note_html'] = $note;
    }

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'spec-contact',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    // Mist unless the editor picks another background. The picked colour's class is added from
    // background_color, so the default is only added when nothing is picked.
    if (empty($args['background_color']) || $args['background_color'] === 'none') {
        $args['classes'][] = 'has-brand-1-background-color';
        $args['classes'][] = 'has-background';
    }

    return $args;
}
