<?php

namespace Theme\WordPress;

class Sidebars
{
    public static function init(): void
    {
        \add_action('widgets_init', [__CLASS__, 'register_sidebars']);
    }

    public static function register_sidebars(): void
    {
        $sidebars = [
            // [
            //     'name'          => \esc_html__('Sidebar', 'granola'),
            //     'id'            => 'sidebar-1',
            //     'description'   => \esc_html__('Add widgets here.', 'granola'),
            //     'before_widget' => '<section id="%1$s" class="widget %2$s">',
            //     'after_widget'  => '</section>',
            //     'before_title'  => '<h4 class="widget-title">',
            //     'after_title'   => '</h4>',
            // ]
        ];

        if (!empty($sidebars)) {
            foreach ($sidebars as $sidebar) {
                \register_sidebar($sidebar);
            }
        }
    }
}
