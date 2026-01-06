<?php

namespace Granola\WordPress;

class PageObject
{
    public static function get(): object
    {
        global $wp_query;

        // Check if WC product query
        if ((class_exists('\WooCommerce')) && function_exists('is_product') && is_product()) {
            return wc_get_product();
        }

        // Standard WP queries
        if ($wp_query->is_home()) {
            // The query is for the posts index. Return the 'post' post type.
            return \get_post_type_object('post');
        } elseif ($wp_query->is_search()) {
            // The query is for a search, return the query.
            return $wp_query;
        } elseif ($wp_query->is_404()) {
            // The query results in a 404, return the query.
            return $wp_query;
        } elseif (!empty($wp_query->get_queried_object())) {
            // The query has a queried object, return that object.
            return \get_queried_object();
        } else {
            // Final fallback, return the query.
            return $wp_query;
        }
    }
}
