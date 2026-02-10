<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

$content = [];
$object = \Granola\WordPress\PageObject::get();

while (have_posts()) {
    the_post();
    $content[] = apply_filters('the_content', get_the_content());
}

echo \Granola\Component::get('site-main', [
    'object' => $object,
    'content' => implode($content),
]);

get_footer();
