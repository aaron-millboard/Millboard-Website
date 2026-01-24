<?php

namespace Granola;

class Debug
{
    /**
     * Prints a formatted error to the log. Optionally echoes it.
     *
     * @param $message An error (array, object, string)
     * @param $echo Whether to echo the error (if WP_DEBUG_DISPLAY is true)
     * @return void
     */
    public static function log($message, $echo = false): void
    {
        if (!defined('WP_DEBUG') || WP_DEBUG !== true) {
            return;
        }

        error_log(var_export($message, true));

        // Bail - no need to continue
        if (empty($echo)) {
            return;
        }

        if (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY === true) {
            self::dump($message);
        }
    }

    /**
     * Echoes a message in a formatted <pre> container.
     *
     * @param $message A message to print
     * @param $die Whether to die() after printing the message
     * @return void
     */
    public static function dump($message, $die = false): void
    {
        if (!defined('WP_DEBUG') || WP_DEBUG !== true) {
            return;
        }

        if (!defined('WP_DEBUG_DISPLAY') || WP_DEBUG_DISPLAY !== true) {
            return;
        }

        ini_set("highlight.default", "#222;");
        ini_set("highlight.html", "#808080");
        ini_set("highlight.keyword", "#912d72; font-weight: bold;");
        ini_set("highlight.string", "#112468;");
        ini_set("highlight.comment", "#222");

        echo "
            <pre style='
                font-size: 14px;
                padding: 1em;
                color: #222;
                background: #eee;
                border-radius: 9px;
                overflow-wrap: break-word;
            '>
        ";
        highlight_string("<?php\n" . var_export($message, true) . ";\n?>");
        echo "</pre>";

        if ($die) {
            die();
        }
    }
}
