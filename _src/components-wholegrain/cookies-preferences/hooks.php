<?php

namespace Granola\Components\CookiesPreferences;

add_filter('granola/partial/assets/components/cookies-preferences', __NAMESPACE__ . '\\filter_args');

add_action('parse_request', __NAMESPACE__ . '\\check_cookies_consent_reset');
