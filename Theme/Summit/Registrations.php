<?php

namespace Theme\Summit;

/**
 * The Summit registration log.
 *
 * Registrations are NOT pushed to HubSpot as they arrive (Aaron, 10 Sep 2026):
 * they are held here until registration closes and then imported in one go. So
 * this log is the only record of who is coming, and it is what the two-per-
 * company cap counts against. Treat it as the source of truth.
 *
 * Two consequences worth knowing:
 *
 *  - The [UK] Summit workflow in HubSpot triggers on a HubSpot form submission,
 *    so it will not fire for anything registered here. `summit_days_attending`
 *    stays unset, the three seat lists do not count these people, and the
 *    08:17 daily capacity report reads UK as zero until the import happens.
 *    That is expected, not a fault.
 *  - Because we hold each attendee as its own row, two people booked on one
 *    shared mailbox are both recorded. HubSpot cannot do that; it dedupes on
 *    email and the second person vanishes. That was a real problem on the INT
 *    form, so keep the export one-row-per-attendee.
 *
 * A registration is stored as a post rather than a custom table because this
 * theme has no table-creation pattern anywhere in it, and a post type gets an
 * admin list, search and export for free.
 */
class Registrations
{
    public const POST_TYPE = 'summit_registration';

    /** Meta keys. Prefixed and kept flat so the export stays trivial. */
    public const META_EMAIL = '_mb_summit_email';
    public const META_COMPANY = '_mb_summit_company';
    public const META_COMPANY_KEY = '_mb_summit_company_key';
    /**
     * What the attendee typed into the Company field.
     *
     * The cap is counted against META_COMPANY_KEY, which comes from the invite
     * list and is authoritative. This is kept only so a mismatch is visible:
     * someone typing a different employer is worth a look before the event.
     */
    public const META_COMPANY_TYPED = '_mb_summit_company_typed';
    public const META_FIRST_NAME = '_mb_summit_first_name';
    public const META_LAST_NAME = '_mb_summit_last_name';
    public const META_PREFERRED_DATE = '_mb_summit_preferred_date';
    public const META_WORKSHOPS = '_mb_summit_workshops';
    public const META_FACTORY_TOUR = '_mb_summit_factory_tour';
    public const META_OPT_OUT = '_mb_summit_opt_out';
    public const META_CATEGORY = '_mb_summit_category';
    public const META_INVITED_NAME = '_mb_summit_invited_name';
    public const META_EXPORTED = '_mb_summit_exported_at';

    public static function init(): void
    {
        \add_action('init', [self::class, 'register']);
        \add_filter('manage_' . self::POST_TYPE . '_posts_columns', [self::class, 'columns']);
        \add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [self::class, 'column'], 10, 2);
    }

    /**
     * Registers the log post type.
     *
     * Deliberately not public: a registration must never be reachable at a
     * front-end URL or turn up in search or a sitemap. It holds a partner's
     * name, company and email address.
     */
    public static function register(): void
    {
        \register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'Summit registrations',
                'singular_name' => 'Summit registration',
                'menu_name' => 'Summit registrations',
            ],
            'public' => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => false,
            'menu_icon' => 'dashicons-tickets-alt',
            'supports' => ['title'],
            'capabilities' => ['create_posts' => 'do_not_allow'],
            'map_meta_cap' => true,
            'has_archive' => false,
            'rewrite' => false,
        ]);
    }

    /**
     * How many people from this company have already registered.
     *
     * Counts the log, not HubSpot, and counts rows rather than distinct emails,
     * because the cap is on PEOPLE: one address booking two colleagues uses two
     * of the company's places.
     */
    public static function count_for_company(string $company_key): int
    {
        $query = new \WP_Query([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'meta_query' => [
                [
                    'key' => self::META_COMPANY_KEY,
                    'value' => $company_key,
                    'compare' => '=',
                ],
            ],
        ]);

        return (int) $query->found_posts;
    }

    /**
     * Records one attendee.
     *
     * @param  array<string,mixed> $data Validated submission data.
     * @return int|\WP_Error The new post id, or an error.
     */
    public static function create(array $data)
    {
        $title = sprintf(
            '%s %s - %s',
            $data['first_name'] ?? '',
            $data['last_name'] ?? '',
            $data['company'] ?? ''
        );

        $post_id = \wp_insert_post([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => trim($title, ' -') ?: 'Summit registration',
        ], true);

        if (\is_wp_error($post_id)) {
            return $post_id;
        }

        $map = [
            self::META_EMAIL => $data['email'] ?? '',
            self::META_COMPANY => $data['company'] ?? '',
            self::META_COMPANY_KEY => $data['company_key'] ?? '',
            self::META_COMPANY_TYPED => $data['company_typed'] ?? '',
            self::META_FIRST_NAME => $data['first_name'] ?? '',
            self::META_LAST_NAME => $data['last_name'] ?? '',
            self::META_PREFERRED_DATE => $data['preferred_date'] ?? '',
            self::META_WORKSHOPS => $data['workshops'] ?? [],
            self::META_FACTORY_TOUR => $data['factory_tour'] ?? '',
            self::META_OPT_OUT => !empty($data['opt_out']) ? '1' : '0',
            self::META_CATEGORY => $data['category'] ?? '',
            self::META_INVITED_NAME => $data['invited_name'] ?? '',
        ];

        foreach ($map as $key => $value) {
            \update_post_meta($post_id, $key, $value);
        }

        return $post_id;
    }

    /**
     * Every registration as flat rows, for the eventual HubSpot import.
     *
     * One row per attendee, including two attendees who share an address, since
     * collapsing them is exactly what we are working around.
     *
     * @return array<int,array<string,string>>
     */
    public static function export_rows(): array
    {
        $ids = \get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'date',
            'order' => 'ASC',
        ]);

        $rows = [];

        foreach ($ids as $id) {
            $workshops = \get_post_meta($id, self::META_WORKSHOPS, true);

            $rows[] = [
                'registered_at' => \get_post_time('Y-m-d H:i:s', true, $id),
                'first_name' => (string) \get_post_meta($id, self::META_FIRST_NAME, true),
                'last_name' => (string) \get_post_meta($id, self::META_LAST_NAME, true),
                'email' => (string) \get_post_meta($id, self::META_EMAIL, true),
                'company' => (string) \get_post_meta($id, self::META_COMPANY, true),
                'company_typed' => (string) \get_post_meta($id, self::META_COMPANY_TYPED, true),
                'company_key' => (string) \get_post_meta($id, self::META_COMPANY_KEY, true),
                'category' => (string) \get_post_meta($id, self::META_CATEGORY, true),
                'preferred_date' => (string) \get_post_meta($id, self::META_PREFERRED_DATE, true),
                // Semicolon-joined to match how HubSpot stores a multi-checkbox
                // value, so the import needs no reshaping.
                'workshops' => is_array($workshops) ? implode(';', $workshops) : (string) $workshops,
                'factory_tour' => (string) \get_post_meta($id, self::META_FACTORY_TOUR, true),
                'opt_out_marketing' => \get_post_meta($id, self::META_OPT_OUT, true) === '1' ? 'true' : 'false',
                'invited_as' => (string) \get_post_meta($id, self::META_INVITED_NAME, true),
            ];
        }

        return $rows;
    }

    /** @param array<string,string> $columns */
    public static function columns(array $columns): array
    {
        return [
            'cb' => $columns['cb'] ?? '',
            'title' => 'Attendee',
            'mb_email' => 'Email',
            'mb_company' => 'Company',
            'mb_date' => 'Preferred date',
            'mb_tour' => 'Factory tour',
            'date' => $columns['date'] ?? 'Registered',
        ];
    }

    public static function column(string $column, int $post_id): void
    {
        $map = [
            'mb_email' => self::META_EMAIL,
            'mb_company' => self::META_COMPANY,
            'mb_date' => self::META_PREFERRED_DATE,
            'mb_tour' => self::META_FACTORY_TOUR,
        ];

        if (isset($map[$column])) {
            echo \esc_html((string) \get_post_meta($post_id, $map[$column], true));
        }
    }
}
