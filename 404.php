<?php

get_header();

$content = [];
$object = \Granola\WordPress\PageObject::get();

$template_content = \Granola\WordPress\TemplatePage::get_content($object);
$content[] = $template_content ?: \Granola\Component::get('no-content', [
    'object' => $object,
]);

echo \Granola\Component::get('site-main', [
    'header' => \Granola\Component::get('page-header', [
        'object' => $object,
    ]),
    'object' => $object,
    'content' => implode($content),
]);

get_footer();
