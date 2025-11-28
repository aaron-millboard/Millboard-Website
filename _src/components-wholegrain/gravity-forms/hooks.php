<?php

namespace Granola\Components\GravityForms;

// Prevents the <head> GF hooks script being output on pages it's not needed.
// https://docs.gravityforms.com/gform_force_hooks_js_output/
\add_filter('gform_force_hooks_js_output', '__return_false');

// Moves any header GF scripts to the footer (most have been moved already, since 2.5). Includes jQuery.
// https://docs.gravityforms.com/gform_enqueue_scripts/
\add_filter('gform_enqueue_scripts', __NAMESPACE__ . '\\move_scripts_to_footer');

// Disable default theme CSS.
// https://docs.gravityforms.com/gform_disable_form_theme_css/
\add_filter('gform_disable_form_theme_css', '__return_true');
\add_filter('gform_disable_css', '__return_true');

// Override default GF initial settings.
\add_filter('gform_form_settings_initial_values', __NAMESPACE__ . '\\set_initial_settings', 10, 1);

// Sanitize the confirmation message which can be set via the CMS (uses wp_kses_post).
// https://docs.gravityforms.com/gform_sanitize_confirmation_message/
\add_filter('gform_sanitize_confirmation_message', '__return_true');

// Automatically scroll the page to the confirmation text or validation message upon submission.
// https://docs.gravityforms.com/gform_confirmation_anchor/
\add_filter('gform_confirmation_anchor', '__return_true');

// Override automatic update settings.
// https://docs.gravityforms.com/gform_disable_auto_update/
\add_filter('gform_disable_auto_update', '__return_true');
\add_filter('option_gform_enable_background_updates', '__return_false');

// Override the AJAX spinner URL (which appears next to the submit button on AJAX) submission.
// \add_filter('gform_ajax_spinner_url', __NAMESPACE__ . '\\spinner_url', 10);
