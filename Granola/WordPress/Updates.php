<?php

namespace Granola\WordPress;

class Updates
{
    public static function init(): void
    {
        /**
         * Explicitly enable auto updates for themes and plugins.
         *
         * @link https://wordpress.org/support/article/configuring-automatic-background-updates/#plugin-theme-updates-via-filter
         */
        \add_filter('auto_update_theme', '__return_true');
        \add_filter('auto_update_plugin', '__return_true');

        /**
         * Explicitly enable auto-update user interface(s).
         *
         * @link https://make.wordpress.org/core/2020/07/15/controlling-plugin-and-theme-auto-updates-ui-in-wordpress-5-5/
         */
        \add_filter('themes_auto_update_enabled', '__return_true');
        \add_filter('plugins_auto_update_enabled', '__return_true');

        /**
         * Explicitly allow major, dev, & minor/security core updates.
         */
        \add_filter('allow_major_auto_core_updates', '__return_true');
        \add_filter('allow_minor_auto_core_updates', '__return_true');
        \add_filter('allow_dev_auto_core_updates', '__return_true');

        /**
         * Prevent WP Core update success emails.
         */
        \add_filter('auto_core_update_send_email', [__CLASS__, 'skip_core_update_success_emails'], 10, 2);

        /**
         * Prevent WP Plugin update success emails.
         */
        \add_filter('auto_plugin_update_send_email', [__CLASS__, 'skip_plugin_update_success_emails'], 10, 2);
    }

    /**
     * Prevent WordPress from sending a 'success' email for Core updates to reduce email 'noise'.
     *
     * @param boolean $send Whether to send the email. Default true.
     * @param string $type The type of email to send. Can be one of 'success', 'fail', 'critical'.
     * @return boolean Whether to send the email.
     */
    public static function skip_core_update_success_emails(bool $send, string $type): bool
    {
        // Don't send the email if it's a success - no need.
        if (!empty($type) && $type === 'success') {
            return false;
        }

        return $send;
    }

    /**
     * Prevent WordPress from sending a 'success' email for Plugin updates to reduce email 'noise'.
     *
     * This will only prevent emails if all plugin updates are a success. If any fail, then the email will still be
     * sent (if plugin update notifications are already enabled).
     *
     * @param boolean $enabled True if plugin update notifications are enabled, false otherwise.
     * @param array $update_results The results of plugins update tasks.
     * @return boolean Whether to send the email.
     */
    public static function skip_plugin_update_success_emails(bool $enabled, array $update_results): bool
    {
        // If there are any failed plugin updates, continue as normal.
        foreach ($update_results as $update_result) {
            if ($update_result->result !== true) {
                return $enabled;
            }
        }

        // No failed plugin updates -  don't send the email.
        return false;
    }
}
