<?php

/**
 * The sunrise.php file - loaded before WordPress is loaded and used to load the correct site based on the URL.
 * This file should be automatically copied to the wp-content directory by the theme.
 */

/**
 * Increase the amount of 'segments' in the URL to be used for the site lookup.
 *
 * 1 => example.com
 * 2 => example.com/segment1
 * 3 => example.com/segment1/segment2
 *
 * @link https://developer.wordpress.org/reference/hooks/site_by_path_segments_count/
 *
 * @param int|null $segments The amount of segments to use for the site lookup.
 * @return int|null The filtered amount of segments to use for the site lookup.
 */
\add_filter('site_by_path_segments_count', function (?int $segments): ?int {
    return 3;
}, 999);
