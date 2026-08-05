<?php

/**
 * Settings screen: token, connection test, mapping overview, backfill, log.
 *
 * Sits under WooCommerce so it is next to the other order tooling rather than
 * cluttering the top-level menu.
 */

declare(strict_types=1);

namespace Millboard\HubSpotOrderFields;

if (!defined('ABSPATH')) {
    exit;
}

final class Admin
{
    private const SLUG = 'millboard-hsof';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_post_millboard_hsof_save', [self::class, 'handle_save']);
        add_action('admin_post_millboard_hsof_backfill', [self::class, 'handle_backfill']);
        add_action('admin_notices', [self::class, 'notice']);
    }

    public static function menu(): void
    {
        add_submenu_page(
            'woocommerce',
            __('HubSpot Order Fields', 'millboard-hsof'),
            __('HubSpot Order Fields', 'millboard-hsof'),
            'manage_woocommerce',
            self::SLUG,
            [self::class, 'render']
        );
    }

    public static function notice(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        if (get_token() !== '') {
            return;
        }

        printf(
            '<div class="notice notice-warning"><p><strong>%s</strong> %s <a href="%s">%s</a></p></div>',
            esc_html__('Millboard HubSpot Order Fields', 'millboard-hsof'),
            esc_html__('is active but has no HubSpot token, so nothing is being synced.', 'millboard-hsof'),
            esc_url(admin_url('admin.php?page=' . self::SLUG)),
            esc_html__('Add one', 'millboard-hsof')
        );
    }

    public static function handle_save(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('Insufficient permissions.', 'millboard-hsof'));
        }

        check_admin_referer('millboard_hsof_save');

        $token    = isset($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '';
        $settings = get_option(OPTION_KEY, []);

        if (!is_array($settings)) {
            $settings = [];
        }

        // An empty submission clears the stored token rather than silently keeping it.
        $settings['token'] = $token;

        update_option(OPTION_KEY, $settings, false);

        wp_safe_redirect(add_query_arg('saved', '1', admin_url('admin.php?page=' . self::SLUG)));
        exit;
    }

    public static function handle_backfill(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('Insufficient permissions.', 'millboard-hsof'));
        }

        check_admin_referer('millboard_hsof_backfill');

        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 25;
        $limit = max(1, min(200, $limit));

        $summary = Sync::backfill($limit);

        wp_safe_redirect(add_query_arg(
            array_map('rawurlencode', array_map('strval', $summary)),
            admin_url('admin.php?page=' . self::SLUG)
        ));
        exit;
    }

    public static function render(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $test = get_token() !== '' ? Client::test_token() : ['ok' => false, 'message' => 'No token configured'];
        $log  = get_option(LOG_OPTION, []);
        $log  = is_array($log) ? $log : [];

        echo '<div class="wrap"><h1>' . esc_html__('Millboard HubSpot Order Fields', 'millboard-hsof') . '</h1>';

        echo '<p style="max-width:46em">' . esc_html__(
            'Pushes the custom checkout fields to the HubSpot contact. CRM Perks reads these only intermittently, so this guarantees them. Marketing opt-in is deliberately not synced.',
            'millboard-hsof'
        ) . '</p>';

        // Connection status
        printf(
            '<div class="notice inline notice-%s"><p><strong>%s</strong> %s</p></div>',
            $test['ok'] ? 'success' : 'warning',
            esc_html__('Connection:', 'millboard-hsof'),
            esc_html($test['message'])
        );

        // Token
        echo '<h2>' . esc_html__('Token', 'millboard-hsof') . '</h2>';

        if (token_is_from_constant()) {
            echo '<p><em>' . esc_html__('Set via the MILLBOARD_HUBSPOT_TOKEN constant in wp-config.php, which takes precedence over anything stored here. That is the recommended setup.', 'millboard-hsof') . '</em></p>';
        } else {
            $settings = get_option(OPTION_KEY, []);
            $current  = is_array($settings) && isset($settings['token']) ? (string) $settings['token'] : '';

            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('millboard_hsof_save');
            echo '<input type="hidden" name="action" value="millboard_hsof_save">';
            echo '<p><input type="password" name="token" class="regular-text" autocomplete="off" value="' . esc_attr($current) . '" placeholder="pat-eu1-..."></p>';
            echo '<p class="description" style="max-width:46em">' . esc_html__(
                'A HubSpot private app token with crm.objects.contacts.write. Better still, define MILLBOARD_HUBSPOT_TOKEN in wp-config.php so it never sits in the database or in a database backup.',
                'millboard-hsof'
            ) . '</p>';
            submit_button(__('Save token', 'millboard-hsof'));
            echo '</form>';
        }

        // Mapping
        echo '<h2>' . esc_html__('Fields synced', 'millboard-hsof') . '</h2>';
        echo '<table class="widefat striped" style="max-width:46em"><thead><tr><th>' .
            esc_html__('Checkout field', 'millboard-hsof') . '</th><th>' .
            esc_html__('HubSpot property', 'millboard-hsof') . '</th></tr></thead><tbody>';

        foreach (Mapper::fields() as $meta => $property) {
            echo '<tr><td><code>' . esc_html($meta) . '</code></td><td><code>' . esc_html($property) . '</code></td></tr>';
        }

        echo '</tbody></table>';

        // Backfill
        echo '<h2>' . esc_html__('Backfill historic orders', 'millboard-hsof') . '</h2>';
        echo '<p style="max-width:46em">' . esc_html__(
            'Reads the fields from past orders and writes them to the matching contact. Every order carries these fields, so this recovers records CRM Perks never transmitted at all. Runs oldest-unsynced first and is safe to run repeatedly.',
            'millboard-hsof'
        ) . '</p>';

        foreach (['processed', 'synced', 'skipped', 'failed'] as $key) {
            if (isset($_GET[$key])) {
                echo '<p><strong>' . esc_html(ucfirst($key)) . ':</strong> ' . esc_html((string) absint($_GET[$key])) . '</p>';
            }
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('millboard_hsof_backfill');
        echo '<input type="hidden" name="action" value="millboard_hsof_backfill">';
        echo '<p><label>' . esc_html__('Orders per run', 'millboard-hsof') .
            ' <input type="number" name="limit" value="25" min="1" max="200" class="small-text"></label></p>';
        submit_button(__('Run backfill', 'millboard-hsof'), 'secondary');
        echo '</form>';

        // Log
        echo '<h2>' . esc_html__('Recent activity', 'millboard-hsof') . '</h2>';

        if ($log === []) {
            echo '<p><em>' . esc_html__('Nothing logged yet.', 'millboard-hsof') . '</em></p>';
        } else {
            echo '<table class="widefat striped"><thead><tr><th>' .
                esc_html__('Time (UTC)', 'millboard-hsof') . '</th><th>' .
                esc_html__('Level', 'millboard-hsof') . '</th><th>' .
                esc_html__('Message', 'millboard-hsof') . '</th><th>' .
                esc_html__('Detail', 'millboard-hsof') . '</th></tr></thead><tbody>';

            foreach (array_slice($log, 0, 60) as $line) {
                $context = isset($line['context']) && is_array($line['context'])
                    ? wp_json_encode($line['context'])
                    : '';

                printf(
                    '<tr><td>%s</td><td>%s</td><td>%s</td><td><code style="font-size:11px">%s</code></td></tr>',
                    esc_html((string) ($line['time'] ?? '')),
                    esc_html((string) ($line['level'] ?? '')),
                    esc_html((string) ($line['message'] ?? '')),
                    esc_html((string) $context)
                );
            }

            echo '</tbody></table>';
        }

        echo '</div>';
    }
}
