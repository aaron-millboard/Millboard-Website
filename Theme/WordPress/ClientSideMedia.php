<?php

namespace Theme\WordPress;

/**
 * Switches off WordPress 7.1's client-side media processing.
 *
 * Core 7.1 sends `Document-Isolation-Policy: isolate-and-credentialless` on block editor screens
 * (Chromium 137+, over https, for users who can upload files) so the browser can resize images
 * itself. That header puts the editing page in its own agent cluster and blocks the access the
 * editor and ACF need to their own iframes. The visible result is the SecurityError "Blocked a
 * frame with origin ... from accessing a cross-origin frame" from compose.min.js, for example when
 * reordering repeater rows in an ACF block, because ACF's WYSIWYG field lives in an iframe
 * (acf_content_ifr). Core's filter turns both the header and client-side processing off, so uploads
 * are resized on the server as they were before 7.1. See wp_set_up_cross_origin_isolation() in
 * wp-includes/media.php.
 *
 * Remove this when ACF and core handle the isolated editor together.
 */
class ClientSideMedia
{
    public static function init()
    {
        \add_filter('wp_client_side_media_processing_enabled', '__return_false');
    }
}
