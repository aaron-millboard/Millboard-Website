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
        return max(1, (int) ceil(self::word_count($post_id) / 200));
    }

    /**
     * Words in an article's text, as a reader meets them.
     *
     * Read off the parsed blocks rather than the rendered ones: rendering ran
     * every block filter on the site and took 20ms an article, too slow for a
     * category grid of fifty. The HTML of the core blocks is counted, and the
     * text fields of the ACF blocks, which is where the FAQ answers in the
     * accordion live. Field keys and numbers in that data are settings, not
     * words, and are passed over.
     */
    public static function word_count(int $post_id): int
    {
        static $counted = [];

        if (isset($counted[$post_id])) {
            return $counted[$post_id];
        }

        $text = [];

        $walk = function (array $blocks) use (&$walk, &$text) {
            foreach ($blocks as $block) {
                if (empty($block['blockName'])) {
                    $text[] = (string) $block['innerHTML'];
                    continue;
                }

                if (strpos($block['blockName'], 'acf/') === 0) {
                    foreach ((array) ($block['attrs']['data'] ?? []) as $key => $value) {
                        if (is_string($value) && strpos((string) $key, '_') !== 0 && strpos($value, 'field_') !== 0 && !is_numeric($value)) {
                            $text[] = $value;
                        }
                    }
                } else {
                    $text[] = implode(' ', array_filter((array) $block['innerContent'], 'is_string'));
                }

                if (!empty($block['innerBlocks'])) {
                    $walk($block['innerBlocks']);
                }
            }
        };

        $walk(\parse_blocks((string) \get_post_field('post_content', $post_id)));

        $plain = \wp_strip_all_tags(implode(' ', $text));

        return $counted[$post_id] = count(preg_split('/\s+/u', trim($plain), -1, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * "8 min read", translated.
     */
    public static function read_label(int $post_id): string
    {
        $minutes = self::read_minutes($post_id);

        return sprintf(
            // translators: %d: reading time in minutes.
            \_n('%d min read', '%d min read', $minutes, 'granola'),
            $minutes
        );
    }

    /**
     * The advice category being viewed, or null.
     *
     * A category template is edited as a page, and the editor's preview has
     * no category to read, so a block in preview can ask for a stand-in: the
     * category with the most guides, so the preview shows real cards rather
     * than an empty box.
     */
    public static function current_term(bool $or_sample = false): ?\WP_Term
    {
        $term = \get_queried_object();

        if ($term instanceof \WP_Term && $term->taxonomy === self::TAXONOMY) {
            return $term;
        }

        if (!$or_sample) {
            return null;
        }

        static $sample = false;

        if ($sample === false) {
            $sample = null;
            $most = 0;
            $terms = \get_terms(['taxonomy' => self::TAXONOMY, 'parent' => 0, 'hide_empty' => false]);

            foreach (\is_wp_error($terms) ? [] : $terms as $candidate) {
                $count = self::guide_count([$candidate->term_id]);

                if ($count > $most) {
                    $most = $count;
                    $sample = $candidate;
                }
            }
        }

        return $sample;
    }

    /**
     * Every category below this one, at any depth, in name order.
     *
     * @return \WP_Term[]
     */
    public static function descendants(\WP_Term $term): array
    {
        static $found = [];

        if (!isset($found[$term->term_id])) {
            $terms = \get_terms([
                'taxonomy' => self::TAXONOMY,
                'child_of' => $term->term_id,
                'hide_empty' => false,
                'orderby' => 'name',
            ]);

            $found[$term->term_id] = \is_wp_error($terms) ? [] : array_values($terms);
        }

        return $found[$term->term_id];
    }

    /**
     * What to call each category below this one, keyed by term id.
     *
     * Its name, less any words every one of them starts with. All eleven
     * under Product Information Comparisons begin "Composite", so as chips and
     * card labels they read "Decking Cost Factors", "Cladding Styles &
     * Aesthetics", which is how the design names them, and the word that says
     * nothing about which is which goes.
     *
     * @return array<int, string>
     */
    public static function topic_labels(\WP_Term $term): array
    {
        $names = [];

        foreach (self::descendants($term) as $child) {
            $names[$child->term_id] = preg_split('/\s+/u', trim(self::term_name($child)));
        }

        if (count($names) < 2) {
            return array_map(function ($words) {
                return implode(' ', $words);
            }, $names);
        }

        // Shared leading words, never all of anyone's name.
        $shared = 0;
        $shortest = min(array_map('count', $names)) - 1;

        while ($shared < $shortest) {
            $word = reset($names)[$shared];

            foreach ($names as $words) {
                if (mb_strtolower($words[$shared]) !== mb_strtolower($word)) {
                    break 2;
                }
            }

            $shared++;
        }

        return array_map(function ($words) use ($shared) {
            $label = implode(' ', array_slice($words, $shared));

            // A name in sentence case ("Composite decking cost factors")
            // would otherwise start in lower case. The chips are set in
            // capitals either way, but a screen reader reads the text.
            return mb_strtoupper(mb_substr($label, 0, 1)) . mb_substr($label, 1);
        }, $names);
    }

    /**
     * The guide a category puts first: the one picked on the category (Advice
     * Articles > Advice Categories), while it is published, else its newest.
     *
     * Not picked and fewer than three guides, none: the panel would only
     * repeat a card that sits straight below it.
     */
    public static function featured_guide_id(\WP_Term $term): int
    {
        if (function_exists('get_field')) {
            $picked = (int) \get_field('advice_featured_guide', $term);

            if ($picked > 0 && \get_post_status($picked) === 'publish') {
                return $picked;
            }
        }

        $posts = self::post_ids([$term->term_id]);

        return count($posts) >= 3 ? $posts[0] : 0;
    }

    /**
     * An article's one-line summary: its excerpt, else its own meta
     * description, which every guide carries and which is written as exactly
     * that.
     *
     * The meta description is read from the post rather than through Yoast's
     * presenter, which builds a whole indexable per call; a category grid asks
     * for fifty. Any %%variables%% in it are still filled the way Yoast fills
     * them. The post type's template description is not a summary of anything
     * and is not used.
     */
    public static function standfirst_of(int $post_id): string
    {
        if (\has_excerpt($post_id)) {
            return \wp_strip_all_tags(\get_the_excerpt($post_id));
        }

        $description = (string) \get_post_meta($post_id, '_yoast_wpseo_metadesc', true);

        if ($description !== '' && strpos($description, '%%') !== false && function_exists('wpseo_replace_vars')) {
            $description = (string) \wpseo_replace_vars($description, \get_post($post_id));
        }

        return trim(\wp_strip_all_tags(html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    /**
     * The category to name an article by, inside a parent category.
     *
     * Its primary category when that sits under the parent, else the first of
     * its categories that does, else the parent itself: an article filed
     * against the parent directly is named by the parent.
     */
    public static function topic_of(int $post_id, \WP_Term $within): \WP_Term
    {
        $below = [];

        foreach (self::descendants($within) as $child) {
            $below[$child->term_id] = $child;
        }

        $primary = Taxonomies::get_primary_term($post_id, self::TAXONOMY);

        if ($primary && isset($below[$primary->term_id])) {
            return $below[$primary->term_id];
        }

        foreach ((array) \get_the_terms($post_id, self::TAXONOMY) as $term) {
            if ($term instanceof \WP_Term && isset($below[$term->term_id])) {
                return $below[$term->term_id];
            }
        }

        return $within;
    }

    /**
     * Every category below this one an article sits in, directly or through a
     * category further down. The topic chips filter on these.
     *
     * @return int[]
     */
    public static function topic_ids_of(int $post_id, \WP_Term $within): array
    {
        $below = array_map(function ($child) {
            return $child->term_id;
        }, self::descendants($within));

        $ids = [];

        foreach ((array) \get_the_terms($post_id, self::TAXONOMY) as $term) {
            if (!$term instanceof \WP_Term) {
                continue;
            }

            $ids[] = $term->term_id;

            foreach (\get_ancestors($term->term_id, self::TAXONOMY, 'taxonomy') as $ancestor) {
                $ids[] = (int) $ancestor;
            }
        }

        return array_values(array_intersect($below, array_unique($ids)));
    }

    /**
     * "Updated Aug 2026", from the post's modified date, in the site's locale.
     */
    public static function updated_label(int $post_id): string
    {
        return sprintf(
            // translators: %s: month and year an article was last updated.
            \__('Updated %s', 'granola'),
            \wp_date('M Y', (int) \get_post_modified_time('U', true, $post_id))
        );
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
