<?php

namespace Theme\WordPress;

class Forms
{
    public static function init()
    {
        // Filter the password form to add appropriate classes.
        \add_filter('the_password_form', [__CLASS__, 'filter_post_password_form']);
    }

    /**
     * Filter the post password form to add styling classes.
     *
     * @param string $output The post password form HTML output.
     * @return string The filtered post password form HTML output.
     */
    public static function filter_post_password_form(string $output): string
    {
        return str_replace('<input type="submit"', '<input type="submit" class="g-button"', $output);
    }
}
