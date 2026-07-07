<?php

namespace Theme\Hubspot;

class Attribution
{
    public static function init(): void
    {
        \add_action('woocommerce_thankyou', [__CLASS__, 'millboard_hubspot_attribution'], 10, 1);
    }

    /**
     * HubSpot Source Attribution via Forms API.
     * Fires on WooCommerce thank you page to associate the hubspotutk cookie
     * with the contact, enabling correct Original Source attribution in HubSpot.
     *
     * Code provided by Millboard.
     *
     * @param $order_id The WooCommerce Order ID.
     * @return void
     */
    public static function millboard_hubspot_attribution($order_id): void
    {
        $logger = \wc_get_logger();
        $logger->info('HubSpot attribution hook fired for order: ' . $order_id, ['source' => 'hubspot-attribution']);

        if (empty($order_id)) {
            return;
        }

        // Prevent duplicate firing on page refresh.
        $meta_key = '_hubspot_attribution_sent';
        if (\get_post_meta($order_id, $meta_key, true)) {
            return;
        }

        // Get the order.
        $order = \wc_get_order($order_id);
        if (empty($order)) {
            return;
        }

        // Get customer email.
        $email = $order->get_billing_email();
        if (! $email) {
            return;
        }

        // Get hubspotutk cookie.
        $hutk = isset($_COOKIE['hubspotutk']) ? \sanitize_text_field($_COOKIE['hubspotutk']) : '';

        // Map locale to HubSpot Form GUID
        // All on Portal ID: '26853518', region: 'eu1'
        $portal_id = '26853518';
        $region = 'eu1';

        $locale_map = array(
            'en-gb' => '80e99b73-84a7-4eb0-8c21-7211f825a2e7',
            'en-ie' => '80e99b73-84a7-4eb0-8c21-7211f825a2e7', // shares GB form.
            'en-us' => 'a0a0c619-3aba-4a0b-8559-a36b3d7772b6',
            'de-de' => 'db4d2cf5-619f-41d2-b14b-1e177e5382ff',
            'en-au' => 'db4d2cf5-619f-41d2-b14b-1e177e5382ff', // shares DE form.
            'fr-fr' => '910d50b0-46c9-4dd0-b3e5-0c7c6b82f55b',
        );

        // Detect current locale from URL path e.g. /en-gb/, /en-us/
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $form_guid = null;

        foreach ($locale_map as $locale => $guid) {
            if (strpos($request_uri, '/' . $locale . '/') !== false) {
                $form_guid = $guid;
                break;
            }
        }

        // Fallback to en-gb if locale not detected.
        if (empty($form_guid)) {
            $form_guid = $locale_map['en-gb'];
        }

        // Build the Forms API endpoint (eu1 region).
        $endpoint = "https://api.hsforms.com/submissions/v3/integration/submit/{$portal_id}/{$form_guid}";

        // Build request body.
        $body = [
            'fields' => [
                [
                    'name' => 'email',
                    'value' => $email,
                ],
            ],
            'context' => [
                'hutk' => $hutk,
                'pageUri' => \home_url($request_uri),
                'pageName' => 'Order Confirmation',
            ],
        ];

        // Remove hutk from context if empty (don't send blank value).
        if (empty($hutk)) {
            unset($body['context']['hutk']);
        }

        // POST to HubSpot Forms API.
        $response = \wp_remote_post($endpoint, array(
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body'  => \wp_json_encode($body),
            'timeout' => 10,
        ));

        // Log errors to WooCommerce logger for debugging.
        if (\is_wp_error($response)) {
            $logger = \wc_get_logger();
            $logger->error(
                'HubSpot attribution error: ' . $response->get_error_message(),
                [
                    'source' => 'hubspot-attribution',
                ]
            );
            return;
        }

        $status_code = \wp_remote_retrieve_response_code($response);

        if ($status_code === 200 || $status_code === 302) {
            // Mark order so we don't fire again on refresh.
            \update_post_meta($order_id, $meta_key, true);
        } else {
            // Log non-200 responses for debugging.
            $logger = \wc_get_logger();
            $logger->warning(
                'HubSpot attribution non-200 response: ' . $status_code . ' - ' . \wp_remote_retrieve_body($response),
                [
                    'source' => 'hubspot-attribution',
                ]
            );
        }
    }
}
