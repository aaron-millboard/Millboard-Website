<?php

namespace Granola\Components\GravityForms;

function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        'attributes' => [],
        'preheading' => '',
        'heading' => '',
        'description' => '',
        'form_type' => '',
        'gravity_form_id' => '',
        'hubspot_script' => '',
    ], $args);

    $args['classes'] = array_merge([
        'gravity-forms',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    if ($args['form_type'] === 'gravityforms' && empty($args['gravity_form_id'])) {
        return null;
    }

    if ($args['form_type'] === 'hubspot' && empty($args['hubspot_script'])) {
        return null;
    }

    return $args;
}

function move_scripts_to_footer(): void
{
    \wp_script_add_data('gform_gravityforms', 'group', 1);
    \wp_script_add_data('gform_json', 'group', 1);
}

function set_initial_settings($initial_values): array
{
    $initial_values['labelPlacement'] = 'top_label';
    $initial_values['descriptionPlacement'] = 'above';
    $initial_values['subLabelPlacement'] = 'above';
    $initial_values['validationSummary'] = true;
    $initial_values['enableHoneypot'] = true;
    $initial_values['enableAnimation'] = false;

    return $initial_values;
}

function spinner_url(): string
{
    return  'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
}
