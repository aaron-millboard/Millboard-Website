<?php

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
