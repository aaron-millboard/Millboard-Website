<?php

namespace Granola\Components\HomeNewsletter;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'eyebrow' => null,
        'heading' => null,
        'description' => null,
        'reassurance' => null,
        'image' => null,
        'gravity_form_id' => '',
        'classes' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'home-newsletter',
        'wp-block',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // The form.
    //
    // Rendered from Gravity Forms, never hand-built. This one collects a name
    // and an email address and feeds the mailing list, so the submission, the
    // notifications and whatever consent handling sits behind them all belong
    // to the form -- rewriting the inputs here would quietly detach the lot.
    //
    // The id falls back to the theme option, the same way email-subscription
    // resolves it, so the site keeps one answer to "which form is this".
    // -------------------------------------------------------------------------
    if (empty($args['gravity_form_id'])) {
        $args['gravity_form_id'] = \get_field('gravity_form_id', 'options') ?: null;
    }

    // -------------------------------------------------------------------------
    // Image.
    // -------------------------------------------------------------------------
    if (!empty($args['image'])) {
        $args['image']['size'] = 'large';
        $args['image']['classes'] = ['home-newsletter__image'];

        // Sized for the crop, not the column.
        //
        // The frame is 16:10 and these photographs are wider than that, so a
        // cover crop has to reach on height: asking for the column width left
        // it scaling up 1.09x. Roughly 1.2x the column covers it.
        $args['image']['sizes'] = '(max-width: 720px) 110vw, min(56vw, 860px)';
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
