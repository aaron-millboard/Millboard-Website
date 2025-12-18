<?php

namespace Theme\Utils;

class Videos
{
    /**
     * Extract video embed URL from YouTube or Vimeo URL
     *
     * @param string $url Video URL
     * @return string|false Embed URL or false if invalid.
     */
    public static function get_video_embed_url($url): string|false
    {
        // Try Vimeo
        $vimeo_id = self::extract_vimeo_id($url);
        if ($vimeo_id) {
            // Player parameters: https://help.vimeo.com/hc/en-us/articles/12426260232977-About-Player-Parameters
            return add_query_arg([
                'autoplay' => 1,
                'controls' => 0,
                'transparent' => 0,
            ], 'https://player.vimeo.com/video/' . $vimeo_id);
        }

        // Try YouTube
        $youtube_id = self::extract_youtube_id($url);
        if ($youtube_id) {
            return add_query_arg([
                'autoplay' => 1,
                'modestbranding' => 1,
                'rel' => 0,
            ], 'https://www.youtube.com/embed/' . $youtube_id);
        }

        return false;
    }

    /**
     * Extract YouTube video ID from URL
     *
     * @param string $url Potential YouTube video URL
     * @return string|false Video ID or false if not found.
     */
    public static function extract_youtube_id($url): string|false
    {
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        return false;
    }

    /**
     * Extract Vimeo video ID from URL.
     *
     * @param string $url Potential Vimeo video URL.
     * @return string|false Video ID or false if not found.
     */
    public static function extract_vimeo_id($url): string|false
    {
        $pattern = '/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|)(\d+)(?:$|\/|\?)/';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[3];
        }
        return false;
    }
}
