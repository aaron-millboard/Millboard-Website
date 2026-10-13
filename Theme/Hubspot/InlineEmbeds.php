<?php

/**
 * Makes every HubSpot form embed render inside the page.
 *
 * HubSpot draws a form in an iframe, which the theme CSS cannot reach, unless the embed is told
 * `css: ''`. Editors paste the embed HubSpot gives them, and it never carries that, so each block
 * that accepts one (the gravity-forms block, the partner contact form, any future one) was
 * showing an unstyled form until someone restyled it by hand. Doing it here, on the rendered
 * block, covers all of them. An embed that already sets its own `css` is left alone.
 *
 * The styling itself is the `.gravity-forms .hbspt-form` rules in the gravity-forms component.
 */

namespace Theme\Hubspot;

class InlineEmbeds
{
    public static function init(): void
    {
        \add_filter('render_block', [__CLASS__, 'add_inline_css'], 10, 1);
    }

    public static function add_inline_css($html)
    {
        if (!is_string($html) || !str_contains($html, 'hbspt.forms.create')) {
            return $html;
        }

        return preg_replace(
            '/hbspt\.forms\.create\(\s*\{(?![^}]*\bcss\s*:)/',
            "hbspt.forms.create({\n    css: '',",
            $html
        );
    }
}
