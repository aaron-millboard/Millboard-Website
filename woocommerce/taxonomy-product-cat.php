<?php

/**
 * The Template for displaying products in a product category. Simply includes the archive template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/taxonomy-product-cat.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     4.7.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

$content = [];
$object = \Granola\WordPress\PageObject::get();

$template_page = \Granola\WordPress\TemplatePage::get_template_page($object);
$content[] = \Granola\WordPress\TemplatePage::get_content($object);

if (empty($template_page)) {
    $content[] = \Granola\Component::get('template-loop');
}

echo \Granola\Component::get('site-main', [
    'object' => $object,
    'content' => implode($content),
]);

get_footer();
