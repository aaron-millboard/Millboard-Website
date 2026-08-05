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
     * Credential check for the settings screen.
     *
     * Deliberately does NOT read contacts. This plugin only ever writes, so
     * requiring crm.objects.contacts.read just to run a health check would mean
     * granting a scope the plugin has no use for.
     *
     * Instead it attempts a PATCH against a reserved address that cannot exist
     * (.invalid is reserved by RFC 2606). PATCH never creates a record, so there
     * is no side effect, and the response tells us what we need:
     *
     *   404 -> credential accepted and permitted to attempt the write. Good.
     *   403 -> authenticated but missing crm.objects.contacts.write.
     *   401 -> credential itself rejected.
     *
     * @return array{ok: bool, message: string}
     */
    public static function test_token(): array
    {
        $token = get_token();

        if ($token === '') {
            return ['ok' => false, 'message' => 'No token configured'];
        }

        $probe = 'millboard-hsof-connection-test@example.invalid';
        $url   = self::BASE . '/crm/v3/objects/contacts/' . rawurlencode($probe) . '?idProperty=email';

        $response = wp_remote_request($url, [
            'method'  => 'PATCH',
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
            // A harmless no-op payload. It is never applied, because the record
            // does not exist and PATCH will not create it.
            'body' => wp_json_encode(['properties' => new \stdClass()]),
        ]);

        if (is_wp_error($response)) {
            return ['ok' => false, 'message' => $response->get_error_message()];
        }

        $status = (int) wp_remote_retrieve_response_code($response);

        // The expected healthy answer: permitted to write, record simply absent.
        if ($status === 404) {
            return ['ok' => true, 'message' => 'Token valid, contact write permitted'];
        }

        // Some deployments answer 400 for a malformed idProperty lookup. That
        // still proves the credential got past authorisation.
        if ($status === 400) {
            return ['ok' => true, 'message' => 'Token valid, contact write permitted'];
        }

        if ($status >= 200 && $status < 300) {
            return ['ok' => true, 'message' => 'Token valid, contact write permitted'];
        }

        if ($status === 403) {
            return ['ok' => false, 'message' => 'Authenticated, but missing the crm.objects.contacts.write scope (403). Add it to the Service Key.'];
        }

        if ($status === 401) {
            return ['ok' => false, 'message' => 'Credential rejected (401). Check the key was copied in full.'];
        }

        return ['ok' => false, 'message' => 'Unexpected status ' . $status];
    }
}
