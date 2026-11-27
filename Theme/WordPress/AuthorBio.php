<?php

namespace Theme\WordPress;

/**
 * Author bios in the language of the site being viewed.
 *
 * A bio is user meta, and user meta is shared by every site in the network, so one English bio shows on all six sites.
 * A bio can now also be stored under `description_<locale>` (for example `description_de_DE`); when the current site's
 * locale has one it is used, otherwise the normal `description` is, so every other site is unchanged.
 *
 * Hooked on get_the_author_meta('description'), which covers the post byline, the advice centre and the specification
 * credentials without touching any of them.
 */
class AuthorBio
{
    public static function init()
    {
        \add_filter('get_the_author_description', [__CLASS__, 'filter_description'], 10, 2);
    }

    /**
     * @param mixed $value   The bio as stored.
     * @param int   $user_id The author.
     * @return mixed The bio for this site's locale, or the stored one.
     */
    public static function filter_description($value, $user_id)
    {
        $local = \get_user_meta((int) $user_id, 'description_' . \get_locale(), true);

        return \is_string($local) && '' !== \trim($local) ? $local : $value;
    }
}
