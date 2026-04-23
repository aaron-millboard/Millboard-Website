<?php

namespace Granola\Components\WebsiteSelector;

// Register Website Selector options page
\add_action('acf/init', function () {

    if (!function_exists('acf_add_options_sub_page')) {
        return;
    }

    \acf_add_options_sub_page([
        'page_title'  => \__('Website selector', 'granola'),
        'menu_title'  => \__('Website selector', 'granola'),
        'menu_slug'   => 'acf-options-website-selector-popup'
    ]);
});

// Add modal to the footer
add_action('wp_footer', function () {

    // First Check if modal enabled
    if (!get_field('website_selector_enabled', 'options')) {
        return;
    }

    // Remove cookie checks to always show the modal
    // Check if we are on the right page
    $current_post_id = get_the_ID();
    $query_type = get_field('website_selector_query_type', 'options');

    if ($query_type == "custom" || $query_type == "exclude") {
        // Get all posts with restrictions
        $restricted_posts_ids = get_field('website_selector_restrict_posts', 'options');
        // If we have match
        if (!empty($restricted_posts_ids)) {
            // If we are in the exclude mode, we don't want to show the modal
            if ($query_type == "exclude" && in_array($current_post_id, $restricted_posts_ids)) {
                return;
            }
            if ($query_type == "custom" && !in_array($current_post_id, $restricted_posts_ids)) {
                return;
            }
        }
    }

    $modal_id = 'modal-website-selector';

    // Get the content for the modal
    $content_args = [
        'preheading' => get_field('website_selector_preheading', 'options'),
        'heading' => get_field('website_selector_heading', 'options'),
        'subheading' => get_field('website_selector_subheading', 'options'),
        'description' => get_field('website_selector_description', 'options'),
        'columns' => [
            [
                'description' => get_field('website_selector_column_1_description', 'options'),
                'image' => get_field('website_selector_column_1_image', 'options'),
                'cta' => get_field('website_selector_column_1_cta', 'options'),
            ],
            [
                'description' => get_field('website_selector_column_2_description', 'options'),
                'image' => get_field('website_selector_column_2_image', 'options'),
                'cta' => get_field('website_selector_column_2_cta', 'options'),
            ],
        ],
    ];
    $content = \Granola\Component::get('website-selector/template', $content_args);

    $options_fields = json_encode($content_args);
    $current_hash = md5($options_fields);

    // Check if we have cookie
    if (!empty($_COOKIE[$modal_id])) {
        // get hash from cookie
        $cookie_hash = $_COOKIE[$modal_id];
        // Look if we have the modal ID
        if ($current_hash == $cookie_hash) {
            return;
        }
    }

    // modal args
    $modal_args = [
        'classes' => ['modal--active'], // Add 'modal--active' class to show the modal on page load
        'id' => $modal_id,
        'content' => $content,
        'close_click_outside' => false,
        'hash' => $current_hash,
        'cookie_lifecycle' => get_field('website_selector_cookie_life', 'options') ?: 3 // Determine cookies lifecycle in days
    ];

    echo \Granola\Component::get('modal', $modal_args);
});
