<?php

namespace Granola\Components\CookiesPreferences;

function filter_args($args): ?array
{
    $args = array_merge([
        'id' => \wp_unique_id('cookies-preferences-'),
        'attributes' => [],
        'classes' => [],
        'consent_all_button' => [
            'content' => \__('Accept all', 'granola'),
            'attributes' => [
                'aria-label' => \__('Accept all cookies', 'granola'),
            ],
        ],
        'reject_all_button' => [
            'content' => \__('Reject all', 'granola'),
            'attributes' => [
                'aria-label' => \__('Reject all cookies', 'granola'),
            ],
        ],
        'manage_preferences_button' => [
            'content' => \__('Manage preferences', 'granola'),
            'attributes' => [
                'aria-label' => \__('Manage preferences by cookie type', 'granola'),
            ],
        ],
        'save_preferences_button' => [
            'content' => \__('Save preferences', 'granola'),
            'attributes' => [
                'aria-label' => \__('Save preferences for which cookies to consent to', 'granola'),
            ],
        ],
        'groups_expanded' => true,
        'feedback_text' => [
            'preferencesSaved' => \__('Cookies consent preferences saved', 'granola'),
        ],
    ], $args);

    $args['attributes'] = array_merge([
        'id' => $args['id'],
        'data-feedback-text' => htmlspecialchars(json_encode($args['feedback_text'])),
    ], $args['attributes']);

    $args['classes'] = array_merge($args['classes'], [
        'cookies-preferences',
    ]);

    $cookies_notice_show = \get_field('cookies_notice_show', 'option');
    if ($cookies_notice_show === false) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Set 'content to all' button arguments
    // -------------------------------------------------------------------------
    $consent_all_button = \get_field('cookies_consent_all_button', 'option');
    if (!empty($consent_all_button['text'])) {
        $args['consent_all_button']['content'] = $consent_all_button['text'];
    }

    if (!empty($consent_all_button['assistive_text'])) {
        $args['consent_all_button']['attributes']['aria-label'] = $consent_all_button['assistive_text'];
    }

    $args['consent_all_button']['classes'][] = 'g-button cookies-preferences__consent js-cookies-consent-all';
    $args['consent_all_button']['type'] = 'submit';

    // -------------------------------------------------------------------------
    // Set 'reject all' button arguments
    // -------------------------------------------------------------------------
    $reject_all_button = \get_field('cookies_reject_all_button', 'option');
    if (!empty($reject_all_button['text'])) {
        $args['reject_all_button']['content'] = $reject_all_button['text'];
    }

    if (!empty($reject_all_button['assistive_text'])) {
        $args['reject_all_button']['attributes']['aria-label'] = $reject_all_button['assistive_text'];
    }

    $args['reject_all_button']['classes'][] = 'g-button cookies-preferences__reject js-cookies-reject-all';
    $args['reject_all_button']['type'] = 'submit';

    // -------------------------------------------------------------------------
    // Set 'manage preferences' button arguments
    // -------------------------------------------------------------------------
    $manage_preferences_button = \get_field('cookies_manage_preferences_button', 'option');
    if (!empty($manage_preferences_button['text'])) {
        $args['manage_preferences_button']['content'] = $manage_preferences_button['text'];
    }

    if (!empty($manage_preferences_button['assistive_text'])) {
        $args['manage_preferences_button']['attributes']['aria-label'] = $manage_preferences_button['assistive_text'];
    }

    $args['manage_preferences_button']['classes'][] = 'g-button g-button--blend cookies-preferences__consent-groups-toggler';

    // Additional 'manage preferences' button attributes
    $args['manage_preferences_button']['attributes']['aria-expanded'] = 'false';
    $args['manage_preferences_button']['attributes']['aria-controls'] = $args['id'] . '-consent-groups';

    // -------------------------------------------------------------------------
    // Set 'save preferences' button arguments
    // -------------------------------------------------------------------------
    $save_preferences_button = \get_field('cookies_save_preferences_button', 'option');
    if (!empty($save_preferences_button['text'])) {
        $args['save_preferences_button']['content'] = $save_preferences_button['text'];
    }

    if (!empty($save_preferences_button['assistive_text'])) {
        $args['save_preferences_button']['attributes']['aria-label'] = $save_preferences_button['assistive_text'];
    }

    $args['save_preferences_button']['classes'][] = 'g-button cookies-preferences__save-consent js-cookies-consent-selected';
    $args['save_preferences_button']['type'] = 'submit';

    // -------------------------------------------------------------------------
    // Set cookie groups.
    // -------------------------------------------------------------------------
    if ($groups = \get_field('cookies_consent_groups', 'option')) {
        $args['groups'] = array_map(function ($group) use ($args) {
            $field_label = \Granola\Component::get('element', [
                'content' => $group['label'],
            ]);

            if (!empty($group['label_additional'])) {
                $field_label .= \Granola\Component::get('element', [
                    'content' => $group['label_additional'],
                ]);
            }

            if ($group['type'] === 'gtm_consent_type') {
                return [
                    'id' => $args['id'] . '-consent-type-' . $group['gtm_consent_type'],
                    'name' => 'gtm_consent_type_' . $group['gtm_consent_type'],
                    'value' => 'granted',
                    'label' => $field_label,
                ];
            }

            return [
                'id' => $args['id'] . '-consent-type-' . $group['cookie_name'],
                'name' => $group['cookie_name'],
                'value' => $group['cookie_consent_granted_value'],
                'label' => $field_label,
            ];
        }, $groups);
    }

    $args['fieldset_attributes'] = [
        'id' => $args['id'] . '-consent-groups',
        'class' => 'cookies-preferences__consent-groups js-expandable-element',
    ];

    if (empty($args['groups_expanded'])) {
        $args['fieldset_attributes']['aria-hidden'] = 'true';
    }

    // -------------------------------------------------------------------------
    // Return the content to the render functions
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * Remove cookies using a GET param, and redirect back to the non-param URL.
 * Hooking in at parse_request rather than init so we can grab the current request.
 * Ref https://www.php.net/manual/en/function.setcookie.php
 *
 */
function check_cookies_consent_reset(): void
{
    if (!isset($_GET['cookies-consent-reset'])) {
        return;
    }

    $cookie_name = 'cookies-consent';
    $cookie_args = [
        'expires' => time() - 106, // Arbitrary value in the past.
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => false,
        'samesite' => 'Strict',
    ];

    if (has_saved_cookies_preferences()) {
        setcookie($cookie_name, '', $cookie_args);
    }

    if (has_saved_cookies_preferences_flag()) {
        setcookie($cookie_name . '-preferences-saved', '', $cookie_args);
    }

    global $wp;

    wp_safe_redirect(home_url($wp->request));

    exit;
}

function has_saved_cookies_preferences(): bool
{
    return isset($_COOKIE['cookies-consent']);
}

function has_saved_cookies_preferences_flag(): bool
{
    return isset($_COOKIE['cookies-consent-preferences-saved']);
}
