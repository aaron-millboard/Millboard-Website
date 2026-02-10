<?php

namespace Theme\Utils;

class Taxonomies
{
    /**
     * Retrieves the primary taxonomy term for a post.
     *
     * Uses the Yoast SEO primary term first with a fallback to the first found
     * term otherwise.
     *
     * @param int|WP_Post $post The post ID or object
     * @param string $post  Taxonomy name. Default 'category'.
     *
     * @return WP_Term|null The primary term. 'null' on failure.
     */
    public static function get_primary_term($post = null, $taxonomy = 'category'): ?\WP_Term
    {
        $post = \get_post($post);

        if (!$post || \is_wp_error($post)) {
            return null;
        }

        if (class_exists('WPSEO_Primary_Term')) {
            $yoast_primary_term = new \WPSEO_Primary_Term($taxonomy, $post->ID);
            $yoast_primary_term = $yoast_primary_term->get_primary_term();
            $term = \get_term($yoast_primary_term);

            if (!empty($term) && !\is_wp_error($term)) {
                return $term;
            }
        }

        // Fallback: first term if Yoast not available or a WP_Error returned.
        $terms = \get_the_terms($post, $taxonomy);

        if (!empty($terms) && is_array($terms)) {
            return $terms[0];
        }

        return null;
    }
}
