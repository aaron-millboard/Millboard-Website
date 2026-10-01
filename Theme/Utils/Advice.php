<?php

namespace Theme\Utils;

/**
 * Shared look-ups for the advice centre's hub blocks.
 *
 * Several blocks print a guide count, and the design file draws them as typed
 * numbers ("14 guides"). A typed count is a claim that goes stale the day an
 * article is published or retired, so every count here is read off the
 * articles themselves at render time.
 */
class Advice
{
    public const POST_TYPE = 'advice-centre';
    public const TAXONOMY = 'advice_category';

    /**
     * Results already worked out on this request, keyed by the sorted term ids.
     * The journey rows, the category cards and the schema all ask for the same
     * counts, so each is queried once.
     *
     * @var array<string, int[]>
     */
    protected static array $post_ids = [];

    /**
     * The published articles filed under any of these categories, their
     * sub-categories included.
     *
     * Not the term's own `count`: that counts only articles filed directly
     * against it, so a parent category whose articles all sit in its children
     * reports 1 when it holds 22. It also counts an article twice across two
     * terms, where this returns each article once.
     *
     * @param int[] $term_ids
     * @return int[]
     */
    public static function post_ids(array $term_ids): array
    {
        $term_ids = array_values(array_unique(array_filter(array_map('intval', $term_ids))));

        if (!$term_ids) {
            return [];
        }

        sort($term_ids);
        $key = implode(',', $term_ids);

        if (isset(self::$post_ids[$key])) {
            return self::$post_ids[$key];
        }

        $query = new \WP_Query([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'orderby' => 'date',
            'order' => 'DESC',
            'tax_query' => [
                [
                    'taxonomy' => self::TAXONOMY,
                    'field' => 'term_id',
                    'terms' => $term_ids,
                    'include_children' => true,
                ],
            ],
        ]);

        self::$post_ids[$key] = array_map('intval', $query->posts);

        return self::$post_ids[$key];
    }

    /**
     * How many published guides sit under these categories.
     *
     * @param int[] $term_ids
     */
    public static function guide_count(array $term_ids): int
    {
        return count(self::post_ids($term_ids));
    }

    /**
     * "1 guide", "14 guides", translated.
     */
    public static function guide_label(int $count): string
    {
        return sprintf(
            // translators: %d: number of advice articles.
            \_n('%d guide', '%d guides', $count, 'granola'),
            $count
        );
    }

    /**
     * Reading time in whole minutes, at 200 words a minute.
     *
     * Counted on the rendered text, not the raw post content, because the
     * articles are built from blocks whose comment delimiters and attribute
     * JSON would otherwise be counted as words.
     */
    public static function read_minutes(int $post_id): int
    {
        $content = (string) \get_post_field('post_content', $post_id);
        $text = \wp_strip_all_tags(\strip_shortcodes(\excerpt_remove_blocks($content) ?: $content));

        // excerpt_remove_blocks() drops the ACF blocks wholesale, which is
        // where the FAQ answers live, so fall back to stripping the delimiters
        // when it leaves next to nothing.
        if (str_word_count($text) < 100) {
            $text = \wp_strip_all_tags(preg_replace('/<!--.*?-->/s', ' ', $content));
        }

        $words = count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY));

        return max(1, (int) ceil($words / 200));
    }

    /**
     * A term's name as text.
     *
     * Several are stored with their entities intact ("Buying Guides &amp;
     * FAQs"). The templates escape whatever they are given, so passing that
     * straight through would escape the ampersand twice.
     */
    public static function term_name(\WP_Term $term): string
    {
        return html_entity_decode($term->name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
