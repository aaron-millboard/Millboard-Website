<?php

/**
 * Plugin Name:       Millboard HubSpot Order Fields
 * Plugin URI:        https://gitlab.com/aarondavismillboard1/millboard
 * Description:       Pushes the custom WooCommerce checkout fields to the HubSpot contact. CRM Perks reads these only intermittently, so this guarantees them. Network-installed, activate per site.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Millboard
 * License:           GPL-2.0-or-later
 * Text Domain:       millboard-hsof
 *
 * Why this exists
 * ---------------
 * The German checkout collects five custom fields, all of them required. Every
 * order carries them: 40 of 40 sampled orders had all five in order meta. The
 * CRM Perks feed, however, passes them on roughly 39% of contacts and 65% of
 * orders, all-or-nothing each time, so it is failing to read the custom field
 * block rather than mismapping individual fields.
 *
 * Two of those fields, Project Size and Project Start Time, are worth 50 of the
 * 75 fit points in the German lead-scoring model. The UK populates them at 97%,
 * Germany at 38%, which is most of why German leads sit in the B and C bands.
 *
 * This plugin reads the fields straight from order meta and writes them to the
 * HubSpot contact. It does not replace CRM Perks, which handles billing fields,
 * the Orders object, line items and associations correctly. Delete this plugin
 * if CRM Perks ever fixes their end.
 *
 * Deliberately out of scope
 * -------------------------
 * `marketing-opt-in` is NOT synced. Marketing consent is legally sensitive and
 * is already written by another route; a second writer risks inconsistent
 * consent records. Add it only as a deliberate decision, not by default.
 */

declare(strict_types=1);

namespace Millboard\HubSpotOrderFields;

if (!defined('ABSPATH')) {
    exit;
}

const VERSION     = '1.0.0';
const OPTION_KEY  = 'millboard_hsof_settings';
const LOG_OPTION  = 'millboard_hsof_log';
const SYNCED_META = '_millboard_hsof_synced';
const ATTEMPT_META = '_millboard_hsof_attempts';

require_once __DIR__ . '/includes/class-mapper.php';
require_once __DIR__ . '/includes/class-client.php';
require_once __DIR__ . '/includes/class-sync.php';
require_once __DIR__ . '/includes/class-admin.php';

/**
 * Boot the plugin, but only where WooCommerce is actually present.
 */
add_action('plugins_loaded', static function (): void {
    if (!class_exists('WooCommerce')) {
        return;
    }

    Sync::init();
    Admin::init();
}, 20);

/**
 * Resolve the HubSpot private app token.
 *
 * A wp-config constant is preferred so the token never lands in the database
 * or in a backup that gets passed around. The option is a fallback for sites
 * where editing wp-config is awkward.
 */
function get_token(): string
{
    if (defined('MILLBOARD_HUBSPOT_TOKEN') && is_string(MILLBOARD_HUBSPOT_TOKEN)) {
        return trim(MILLBOARD_HUBSPOT_TOKEN);
    }

    $settings = get_option(OPTION_KEY, []);

    return isset($settings['token']) ? trim((string) $settings['token']) : '';
}

/**
 * Whether the token came from wp-config rather than the database.
 */
function token_is_from_constant(): bool
{
    return defined('MILLBOARD_HUBSPOT_TOKEN') && is_string(MILLBOARD_HUBSPOT_TOKEN) && trim(MILLBOARD_HUBSPOT_TOKEN) !== '';
}

/**
 * Append a line to the rolling log.
 *
 * Kept deliberately small and free of personal data beyond the order id, so it
 * can be read in the admin without exposing customer details.
 *
 * @param string $level   info|warning|error
 * @param string $message Human-readable summary.
 * @param array  $context Extra detail. Do not put names, emails or addresses here.
 */
function log_line(string $level, string $message, array $context = []): void
{
    $log = get_option(LOG_OPTION, []);

    if (!is_array($log)) {
        $log = [];
    }

    array_unshift($log, [
        'time'    => gmdate('Y-m-d H:i:s'),
        'level'   => $level,
        'message' => $message,
        'context' => $context,
    ]);

    // Keep the most recent 200 lines only.
    update_option(LOG_OPTION, array_slice($log, 0, 200), false);
}
