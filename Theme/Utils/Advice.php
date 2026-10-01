<?php

namespace Theme\Utils;

/**
 * Shared look-ups for the advice centre's hub blocks and archives.
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
     * Attachments already drawn by a hub block on this request.
     *
     * The category cards fall back to a guide's featured image, and the newest
     * guide is usually also the one in the hero or the featured panel, so the
     * same photograph turned up three times down the page. Blocks note what
     * they have shown here, and the cards pass over anything already on it.
     *
     * @var array<int, true>
     */
    protected static array $images_shown = [];

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
     * Note an image as drawn on the page.
     */
    public static function mark_image_shown(int $attachment_id): void
    {
        if ($attachment_id > 0) {
            self::$images_shown[$attachment_id] = true;
        }
    }

    /**
     * The images drawn on the page so far.
     *
     * @return int[]
     */
    public static function images_shown(): array
    {
        return array_keys(self::$images_shown);
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
     * Whether a template page stands in for an archive without paginating it.
     *
     * When an archive has a template page, the theme renders that page's
     * blocks instead of the post loop (index.php), but WordPress still splits
     * the main query into pages. So every /page/N/ the query reaches answered
     * 200 with an exact copy of page one, self-canonical and indexable, and
     * Yoast's rel="next" led Google from one copy to the next.
     *
     * A template that does list the posts with its own pagination, through a
     * loop block that reads the page number, paginates for real and is not
     * static, so adding one later brings the page URLs back to life.
     *
     * @param mixed $template The template page, as TemplatePage returns it.
     */
    public static function template_is_static($template): bool
    {
        if (!\Granola\WordPress\TemplatePage::is_valid_template_page($template)) {
            return false;
        }

        foreach (['acf/template-loop', 'acf/gallery-loop'] as $loop) {
            if (\has_block($loop, $template)) {
                return false;
            }
        }

        return true;
    }

    /**
     * A URL carrying the current request's query string, less the page number.
     *
     * Any other argument survives (a campaign's UTM tags, say); the page
     * number goes, or ?paged=2 would redirect to itself forever.
     */
    public static function unpaged_url(string $base): string
    {
        $query = isset($_SERVER['QUERY_STRING']) ? (string) $_SERVER['QUERY_STRING'] : '';

        return $query === '' ? $base : \remove_query_arg('paged', $base . '?' . $query);
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
