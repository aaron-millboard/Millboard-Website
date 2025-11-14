<?php

namespace Granola;

class Paths
{
    /**
     * Convert a 'relative' file path into a legitimate theme file path. Supports child theme overriding.
     *
     * @param string $path A 'relative' theme file path.
     * @return string The legitimate theme file path.
     */
    public static function resolve(string $path): string
    {
        // Resolve to get a full path.
        $path = \get_theme_file_path($path);

        // Load an index.php file for directory paths.
        // This imitates node.js with import statements.
        if (is_dir($path)) {
            $path = $path . '/index.php';
        }

        // Add the '.php' extension if it's missing.
        if (empty(pathinfo($path, PATHINFO_EXTENSION))) {
            $path = $path . '.php';
        }

        return $path;
    }

    /**
     * Retrieve a theme asset URI. Supports child theme overriding.
     *
     * @link https://developer.wordpress.org/reference/functions/get_theme_file_uri/
     *
     * @param string $path The asset path.
     * @return string The full theme asset URI with asset directory prepended.
     */
    public static function theme_asset_url(string $path = ''): string
    {
        return \get_theme_file_uri(self::theme_asset_path($path, true));
    }

    /**
     * Retrieve a theme asset path. Supports child theme overriding.
     *
     * @link https://developer.wordpress.org/reference/functions/get_theme_file_path/
     *
     * @param string $path The asset path.
     * @param bool $relative Whether the returned asset path should be relative to the theme root.
     * @return string The full theme asset path with asset directory prepended.
     */
    public static function theme_asset_path(string $path = '', $relative = false): string
    {
        $path = "assets/$path";
        if ($relative === true) {
            return $path;
        }

        return \get_theme_file_path($path);
    }
}
