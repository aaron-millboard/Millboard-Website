<?php

/**
 * Thin HubSpot API client.
 *
 * Only does one thing: patch a contact identified by email address. HubSpot
 * supports `idProperty=email` on PATCH, which is idempotent and avoids a
 * search-then-update round trip.
 */

declare(strict_types=1);

namespace Millboard\HubSpotOrderFields;

if (!defined('ABSPATH')) {
    exit;
}

final class Client
{
    private const BASE = 'https://api.hubapi.com';

    public const RESULT_OK        = 'ok';
    public const RESULT_NOT_FOUND = 'not_found';
    public const RESULT_NO_TOKEN  = 'no_token';
    public const RESULT_RATE_LIMIT = 'rate_limit';
    public const RESULT_ERROR     = 'error';

    /**
     * Patch a contact by email.
     *
     * @param array<string,string> $properties
     * @return array{result: string, status: int, message: string}
     */
    public static function patch_contact_by_email(string $email, array $properties): array
    {
        $token = get_token();

        if ($token === '') {
            return ['result' => self::RESULT_NO_TOKEN, 'status' => 0, 'message' => 'No HubSpot token configured'];
        }

        if ($properties === []) {
            return ['result' => self::RESULT_OK, 'status' => 0, 'message' => 'Nothing to send'];
        }

        $url = self::BASE . '/crm/v3/objects/contacts/' . rawurlencode($email) . '?idProperty=email';

        $response = wp_remote_request($url, [
            'method'  => 'PATCH',
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode(['properties' => $properties]),
        ]);

        if (is_wp_error($response)) {
            return [
                'result'  => self::RESULT_ERROR,
                'status'  => 0,
                'message' => $response->get_error_message(),
            ];
        }

        $status = (int) wp_remote_retrieve_response_code($response);

        if ($status >= 200 && $status < 300) {
            return ['result' => self::RESULT_OK, 'status' => $status, 'message' => 'Updated'];
        }

        // The contact may not exist yet: CRM Perks creates it, and we can lose
        // that race. Treated as retryable rather than an error.
        if ($status === 404) {
            return ['result' => self::RESULT_NOT_FOUND, 'status' => $status, 'message' => 'Contact not found yet'];
        }

        if ($status === 429 || $status >= 500) {
            return ['result' => self::RESULT_RATE_LIMIT, 'status' => $status, 'message' => 'Throttled or upstream error'];
        }

        $body    = (string) wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        $message = is_array($decoded) && isset($decoded['message']) ? (string) $decoded['message'] : substr($body, 0, 300);

        return ['result' => self::RESULT_ERROR, 'status' => $status, 'message' => $message];
    }

    /**
     * Cheap credential check for the settings screen.
     *
     * @return array{ok: bool, message: string}
     */
    public static function test_token(): array
    {
        $token = get_token();

        if ($token === '') {
            return ['ok' => false, 'message' => 'No token configured'];
        }

        $response = wp_remote_get(self::BASE . '/crm/v3/objects/contacts?limit=1', [
            'timeout' => 15,
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        if (is_wp_error($response)) {
            return ['ok' => false, 'message' => $response->get_error_message()];
        }

        $status = (int) wp_remote_retrieve_response_code($response);

        if ($status >= 200 && $status < 300) {
            return ['ok' => true, 'message' => 'Token valid, contacts readable'];
        }

        if ($status === 401 || $status === 403) {
            return ['ok' => false, 'message' => 'Token rejected (' . $status . '). Check it has crm.objects.contacts.write.'];
        }

        return ['ok' => false, 'message' => 'Unexpected status ' . $status];
    }
}
