<?php

namespace Granola\Components\GravityForms;

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
