<?php

namespace Granola\WordPress;

class UploadMimes
{
    public static function init(): void
    {
        \add_filter('upload_mimes', [__CLASS__, 'extend']);
        \add_filter('wp_check_filetype_and_ext', [__CLASS__, 'filetype_and_ext'], 10, 4);
    }

    /**
     * Filters the list of allowed upload mime types and file extensions via the
     * 'granola/wordpress/upload_mimes/types' filter.
     *
     * @link https://developer.wordpress.org/reference/hooks/upload_mimes/
     *
     * @param array $types Mime types array keyed by the file extension regex corresponding to those types.
     * @return array The filtered mime types array.
     */
    public static function extend(array $types = []): array
    {
        $mime_types = \apply_filters('granola/wordpress/upload_mimes/types', []);
        if (empty($mime_types) || !is_array($mime_types)) {
            return $types;
        }

        foreach ($mime_types as $key => $value) {
            $types[$key] = $value;
        }

        return $types;
    }

    /**
     * Filters the "real" file type of the given file, based on the allowed mime types set by the
     * 'granola/wordpress/upload_mimes/types' filter.
     *
     * @link https://developer.wordpress.org/reference/hooks/wp_check_filetype_and_ext/
     *
     * @param array $types Values for the extension, mime type, and corrected filename.
     * @param string $file Full path to the file.
     * @param string $filename The name of the file (may differ from $file due to $file being in a tmp directory).
     * @param array $mimes Array of mime types keyed by their file extension regex.
     * @return array The filtered array of "corrected" file type values.
     */
    public static function filetype_and_ext(array $types, string $file, string $filename, ?array $mimes): array
    {
        $mime_types = \apply_filters('granola/wordpress/upload_mimes/types', []);
        if (empty($mime_types) || !is_array($mime_types)) {
            return $types;
        }

        $filetype = \wp_check_filetype($filename, $mimes);

        if (array_key_exists($filetype['ext'], $mime_types)) {
            $types['ext'] = $filetype['ext'];
            $types['type'] = $filetype['type'];
        }

        return $types;
    }
}
