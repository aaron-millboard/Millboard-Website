<?php

namespace Theme\Utils;

class Trustpilot
{
    /**
     * The widget's display language for the current site.
     *
     * It is the widget's display language, not a filter on which reviews are
     * counted: the business unit is the same one everywhere, which is where
     * the reviews live. Without this the German and French pages get English
     * widget furniture.
     */
    public static function locale(): string
    {
        $locales = [
            'en_GB' => 'en-GB',
            'en_US' => 'en-US',
            'en_IE' => 'en-IE',
            'en_AU' => 'en-AU',
            'de_DE' => 'de-DE',
            'fr_FR' => 'fr-FR',
        ];

        return $locales[\get_locale()] ?? 'en-GB';
    }

    /**
     * Point pasted Trustpilot widget markup at this site's locale.
     *
     * The block stores the markup Trustpilot give you, and theirs always
     * carries the locale of whoever copied it. Every site had en-GB baked in.
     * Rewriting it on output means an editor can paste Trustpilot's markup as
     * supplied and still get the right language on every site.
     */
    public static function localise_embed(string $markup): string
    {
        if ($markup === '' || !str_contains($markup, 'data-locale')) {
            return $markup;
        }

        return (string) preg_replace(
            '/data-locale=(["\'])[^"\']*\1/',
            'data-locale="' . \esc_attr(self::locale()) . '"',
            $markup
        );
    }
}
