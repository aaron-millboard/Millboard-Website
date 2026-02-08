<?php

/**
 * Functionality that enables a 'Template' CPT which is used for editing archive
 * (and other special templates) page content.
 */

namespace Granola\WordPress;

class TemplatePage
{
    protected const SLUG = 'granola-template'; // Prefixed to avoid conflicts.

    /**
     * Create Template Page post type and link to configured post types and taxonomies.
     */
    public static function init()
    {
        // -------------------------------------------------------------------------
        // Functional. Set up basic building blocks of Template Pages.
        // -------------------------------------------------------------------------

        // Register Template Page CPT to store on-page content for archives and "special" pages.
        \add_action('init', [__CLASS__, 'register_post_type'], 11);

        // Create a new Template Page post instance when requesting a valid "Add Template" URL.
        \add_action('admin_post_create_template_page', [__CLASS__, 'create_template_page']);

        // Deletes the relevant Template Page option when a Template Page post instance is trashed.
        \add_action('after_delete_post', [__CLASS__, 'remove_template_option'], 10, 2);


        // -------------------------------------------------------------------------
        // UI - Buttons. Appends various admin bar buttons for Template Page CRUD.
        // -------------------------------------------------------------------------

        // Append "Add Template" buttons to admin bar.
        \add_action('admin_bar_menu', [__CLASS__, 'add_404_add_template_admin_bar_link'], 80);
        \add_action('admin_bar_menu', [__CLASS__, 'add_search_add_template_admin_bar_link'], 80);
        \add_action('admin_bar_menu', [__CLASS__, 'add_taxonomy_add_template_admin_bar_link'], 80);
        \add_action('admin_bar_menu', [__CLASS__, 'add_term_add_template_admin_bar_link'], 80);
        \add_action('admin_bar_menu', [__CLASS__, 'add_term_add_template_admin_bar_link_front_end'], 80);
        \add_action('admin_bar_menu', [__CLASS__, 'add_post_type_archive_template_admin_bar_link'], 80);

        // Append "Edit Template" buttons to admin bar.
        \add_action('admin_bar_menu', [__CLASS__, 'add_404_edit_toolbar_button'], 80);
        \add_action('admin_bar_menu', [__CLASS__, 'add_search_edit_toolbar_button'], 80);
        \add_action('admin_bar_menu', [__CLASS__, 'add_post_type_edit_toolbar_button'], 80);
        \add_action('admin_bar_menu', [__CLASS__, 'add_admin_bar_edit_toolbar_button'], 80);

        // Add "View Template" button to admin bar.
        \add_action('admin_bar_menu', [__CLASS__, 'add_view_toolbar_button'], 80);


        // -------------------------------------------------------------------------
        // UI - Enhancements.
        // -------------------------------------------------------------------------

        // Add an "Edit Template" link to all CPTs (which have a template set) in the admin sidebar.
        \add_filter('granola/wordpress/admin/submenu', [__CLASS__, 'add_post_type_template_edit_submenu_link']);

        // Return the permalink of a Template Page's linked object instead of the Template Page's own permalink.
        \add_filter('post_type_link', [__CLASS__, 'filter_template_page_permalink'], 10, 2);

        // Admin columns related actions and filters. (next static method adds more actions / filters)
        \add_action('init', [__CLASS__, 'init_taxonomy_template_page_column']);


        // -------------------------------------------------------------------------
        // Menus. Allow (valid) Template Pages to be linked in menus.
        // -------------------------------------------------------------------------

        \add_filter('wp_setup_nav_menu_item', [__CLASS__, 'filter_admin_nav_menu_item']);
        \add_filter('customize_nav_menu_available_items', [__CLASS__, 'filter_customizer_available_nav_menu_items'], 10, 3);
        \add_filter('customize_nav_menu_searched_items', [__CLASS__, 'filter_customizer_nav_menu_items']);


        // -------------------------------------------------------------------------
        // Clean up. Remove any conflicting core WP functionality.
        // -------------------------------------------------------------------------

        // Remove "edit page" button for Posts PT when there is a static page set in WP Settings > Reading.
        \add_action('admin_bar_menu', [__CLASS__, 'remove_blog_home_edit_page_button'], 100);
    }

    /**
     * Initialize the Template Page column in the edit terms table view for taxonomies with enabled template pages
     */
    public static function init_taxonomy_template_page_column(): void
    {

        // Pull list of taxonomies which we are going to use for 'template page'.
        $taxonomies = self::get_template_taxonomies();

        // Prevent unnecessary loop attempt if our array is empty.
        if (empty($taxonomies)) {
            return;
        }

        // Loop through each taxonomy and manage its 'Template Page' column.
        foreach ($taxonomies as $taxonomy_slug) {
            // Add column
            \add_filter("manage_edit-{$taxonomy_slug}_columns", [__CLASS__, 'add_taxonomy_template_page_column'], 10);
            // Populate data in the column
            \add_action("manage_{$taxonomy_slug}_custom_column", [__CLASS__, 'render_taxonomy_template_page_column'], 10, 3);
        }
    }

    /**
     * Register the TemplatePage post type.
     *
     * @return void
     */
    public static function register_post_type(): void
    {
        if (!function_exists('register_extended_post_type')) {
            return;
        }

        \register_extended_post_type(self::SLUG, [
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'show_in_nav_menus' => true,
            'menu_position' => 50, // Below comments, after all other post types.
            'menu_icon' => 'dashicons-welcome-widgets-menus',
            'supports' => [
                'title',
                'editor',
                'revisions',
                'thumbnail',
                'custom-fields',
                'excerpt',
            ],
            'admin_cols' => [
                'title' => [
                    'title' => \__('Template Name', 'granola'),
                ],
                'updated' => [
                    'title'      => \__('Updated', 'granola'),
                    'post_field' => 'post_modified',
                    'date_format' => 'Y/m/d \a\t H:i a',
                ],
                'template_for' => [
                    'title' => \__('Template For', 'granola'),
                    'function' => function ($post) {
                        $object = self::get_templated_object($post);

                        if (empty($object)) {
                            return;
                        }

                        if ($object instanceof \WP_Term) {
                            echo sprintf(
                                // translators: 1: Taxonomy name. 2: Term archive link.
                                \_x('%1$s: %2$s', 'Template post list taxonomy archive link', 'granola'),
                                \get_taxonomy($object->taxonomy)->labels->singular_name,
                                \Granola\Component::get('link', [
                                    'url' => \get_term_link($object),
                                    'content' => $object->name,
                                ])
                            );
                        } elseif ($object instanceof \WP_Taxonomy) {
                            echo sprintf(
                                // translators: 1: Taxonomy archive link
                                \_x('All %s', 'Template post list taxonomy name', 'granola'),
                                $object->label,
                            );
                        } elseif ($object instanceof \WP_Post_Type) {
                            echo sprintf(
                                // translators: 1: Post type archive link.
                                \_x('Post Type: %s', 'Template post list post type archive link', 'granola'),
                                \Granola\Component::get('link', [
                                    'url' => \get_post_type_archive_link($object->name),
                                    'content' => $object->label,
                                ])
                            );
                        } elseif (!empty($object->type) && $object->type === '404') {
                            echo \_x('Special: 404', 'Template post list 404', 'granola');
                        } elseif (!empty($object->type) && $object->type === 'search') {
                            echo \_x('Special: Search', 'Template post list search', 'granola');
                        }
                    }
                ]
            ],

            /**
             * Only allow users to add new template pages via custom admin bar buttons.
             *
             * @link https://developer.wordpress.org/reference/functions/register_post_type/#capabilities
             *
             * Argument explanation:
             * capability_type: Use the 'post' post type capabilities for the `granola-template` post type by default.
             * capabilities['create_posts']: Override `create_posts` "primitive capability" with 'do_not_allow' on
             *                               multisites (as super-admin permissions override this setting) or false on
             *                               single sites to prevent post creation except by custom buttons.
             * map_meta_cap: Required to enable overriding of `create_posts` "primitive capability".
             */
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => \is_multisite() ? 'do_not_allow' : false,
            ],
            'map_meta_cap' => true, // Required.
        ], [
            // Override the base names used for labels (optional).
            'singular' => \__('Template Page', 'granola'),
            'plural'   => \__('Template Pages', 'granola'),
            'slug'     => self::SLUG,
        ]);
    }

    /**
     * Creates a new template page for a post type, if one does not exist, using the `admin_post_{action}` hook.
     *
     * @link https://developer.wordpress.org/reference/hooks/admin_post_action/
     */
    public static function create_template_page(): void
    {
        // Bail early - capability check.
        if (!\current_user_can('edit_pages')) {
            return;
        }

        // Bail early - nonce check.
        $nonce = $_REQUEST['nonce'] ?? '';
        if (\wp_verify_nonce($nonce, 'create_template_page') === false) {
            \wp_die(
                \__('Invalid request.', 'granola')
            );
        }

        if (empty($_REQUEST['object_type']) || !isset($_REQUEST['object_id'])) {
            // Bail early - required args not set.
            \wp_safe_redirect(
                \admin_url('edit.php?post_type=' . self::SLUG)
            );
            exit;
        }

        $object_data = (object) [
            'id' => \sanitize_text_field((string) $_REQUEST['object_id']),
            'type' => \sanitize_text_field((string) $_REQUEST['object_type']),
        ];

        if ($object_data->type === 'wp_post_type') {
            $object = \get_post_type_object($object_data->id);
            $object_data->title = $object->labels->name;
            $object_data->slug = $object->name;
        } elseif ($object_data->type === 'wp_term') {
            $object = \get_term_by('term_taxonomy_id', $object_data->id);
            $object_data->title = $object->name;
            $object_data->slug = $object->slug;
        } elseif ($object_data->type === 'wp_taxonomy') {
            $object = \get_taxonomy($object_data->id);
            $object_data->title = $object->label;
            $object_data->slug = $object->name;
        } elseif ($object_data->type === '404') {
            $object_data->title = \_x('404 Not Found', 'Template Page 404 title', 'granola');
            $object_data->slug = '404';
        } elseif ($object_data->type === 'search') {
            $object_data->title = \_x('Search', 'Template Page search title', 'granola');
            $object_data->slug = 'search';
        }

        // Bail early - invalid object.
        if (empty($object_data->title)) {
            \wp_safe_redirect(
                \admin_url('edit.php?post_type=' . self::SLUG)
            );
            exit;
        }

        // Create new template page for this post type.
        $template_page_id = \wp_insert_post([
            'post_title'   => $object_data->title,
            'post_content' => $object_data->type === '404' ? '' : '<!-- wp:acf/template-loop /-->',
            'post_status'  => 'publish',
            'post_type'    => self::SLUG,
            'post_name'    => isset($object_data->slug) ? $object_data->slug : null,
            'meta_input'   => [
                'granola-template-page-data' => $object_data,
            ],
        ]);

        // Bail early - post creation failed somehow.
        if (empty($template_page_id) || \is_wp_error($template_page_id)) {
            \wp_safe_redirect(
                \admin_url('edit.php?post_type=' . self::SLUG)
            );
            exit;
        }

        if ($object_data->type === 'wp_post_type' || $object_data->type === 'wp_taxonomy') {
            // Connection from post type or taxonomy to template page.
            \update_option("{$object_data->slug}_template_page", $template_page_id, false);
        } elseif ($object_data->type === 'wp_term') {
            // Connection from term to template page.
            \update_term_meta($object_data->id, 'template_page', $template_page_id);
        } elseif ($object_data->type === '404') {
            \update_option('404_template_page', $template_page_id, false);
        } elseif ($object_data->type === 'search') {
            \update_option('search_template_page', $template_page_id, false);
        }

        \wp_safe_redirect(
            \get_edit_post_link($template_page_id, 'redirect')
        );
        exit;
    }

    /**
     * Deletes a template page option field when the related template page post is trashed.
     */
    public static function remove_template_option($post_id, $post): void
    {
        // Bail early - wrong post type.
        if (\get_post_type($post_id) !== self::SLUG) {
            return;
        }

        $object = self::get_templated_object($post);

        // Bail early - no templated object found.
        if (empty($object)) {
            return;
        }

        if ($object instanceof \WP_Post_Type || $object instanceof \WP_Taxonomy) {
            \delete_option("{$object->name}_template_page");
        } elseif ($object instanceof \WP_Term) {
            \delete_term_meta($object->term_id, 'template_page');
        } elseif (!empty($object->type) && $object->type === '404') {
            \delete_option('404_template_page');
        } elseif (!empty($object->type) && $object->type === 'search') {
            \delete_option('search_template_page');
        }

        return;
    }

    /**
     * Filters the global $submenu to add post type edit link(s) to the WP admin sidebar.
     *
     * @param array $submenu An array of WP admin menu items.
     * @return array The array of WP admin menu items, with Template Page add/edit links appended where relevant.
     */
    public static function add_post_type_template_edit_submenu_link(array $submenu): array
    {
        // Bail early - user doesn't have the right capabilities.
        if (!\current_user_can('edit_pages')) {
            return $submenu;
        }

        $post_types = self::get_template_post_types();

        foreach ($post_types as $pt) {
            $template_page = self::get_template_page(
                \get_post_type_object($pt)
            );

            // Handle 'post' PT menu key edge case.
            $key = ($pt === 'post') ? 'edit.php' : "edit.php?post_type={$pt}";

            // Bail early - no page found.
            if (self::is_valid_template_page($template_page)) {
                $link_array = [
                    \__('Edit Template', 'granola'),
                    'edit_pages',
                    \get_edit_post_link($template_page->ID),
                ];
            } else {
                $link_array = [
                    \__('Add Template', 'granola'),
                    'edit_pages',
                    \add_query_arg([
                        'action' => 'create_template_page',
                        'object_type' => 'wp_post_type',
                        'object_id' => $pt,
                        'nonce' => \wp_create_nonce('create_template_page'),
                    ], \admin_url('admin-post.php')),
                ];
            }

            $submenu[$key][] = $link_array;
        }

        return $submenu;
    }

    /**
     * Filters the global $submenu to add post type edit link(s) to the WP admin bar.
     *
     * @param \WP_Admin_Bar $admin_bar An array of WP admin menu items.
     */
    public static function add_taxonomy_add_template_admin_bar_link(\WP_Admin_Bar $admin_bar): void
    {
        // Bail early - not on an admin screen.
        if (!\is_admin()) {
            return;
        }

        // Bail early - user doesn't have the right capabilities.
        if (!\current_user_can('edit_posts')) {
            return;
        }

        $screen = \get_current_screen();
        $taxonomies = self::get_template_taxonomies();

        // Bail early - not currently editing a valid taxonomy term.
        if (
            empty($screen->taxonomy) ||
            $screen->base !== 'edit-tags' ||
            !in_array($screen->taxonomy, $taxonomies, true)
        ) {
            return;
        }

        $taxonomy_name = $screen->taxonomy;
        $template_page_id = \get_option("{$taxonomy_name}_template_page", 0);
        $template_page = \get_post($template_page_id);

        // Bail early - template page set already.
        if (self::is_valid_template_page($template_page)) {
            return;
        }

        $admin_bar->add_menu([
            'id'    => 'granola-add-template',
            'title' => \__('Add Template', 'granola'),
            'href'  => \add_query_arg([
                'action' => 'create_template_page',
                'object_type' => 'wp_taxonomy',
                'object_id' => $taxonomy_name,
                'nonce' => \wp_create_nonce('create_template_page'),
            ], \admin_url('admin-post.php')),
            'meta'  => [
                'title' => \__('Add Template', 'granola'),
            ],
        ]);
    }

    /**
     * Filters the global $submenu to add post type edit link(s) to the WP admin bar.
     *
     * @param \WP_Admin_Bar $admin_bar An array of WP admin menu items.
     */
    public static function add_term_add_template_admin_bar_link_front_end(\WP_Admin_Bar $admin_bar): void
    {
        // Bail early - on an admin screen.
        if (\is_admin()) {
            return;
        }

        // Bail early - not on a taxonomy page.
        if (!\Granola\Helpers::is_taxonomy()) {
            return;
        }

        // Bail early - user doesn't have the right capabilities.
        if (!\current_user_can('edit_posts')) {
            return;
        }

        $queried_object = \get_queried_object();

        if (!$queried_object instanceof \WP_Term) {
            return;
        }

        $template_page_id = \get_term_meta($queried_object->term_id, 'template_page', true);

        // If we have a template page ID, check if it's valid...
        if (!empty($template_page_id)) {
            $template_page = \get_post($template_page_id);

            // Bail early - valid template page set already.
            if (self::is_valid_template_page($template_page)) {
                return;
            }
        }

        $admin_bar->add_menu([
            'id'    => 'granola-add-template',
            'title' => \__('Add Template', 'granola'),
            'href'  => \add_query_arg([
                'action' => 'create_template_page',
                'object_type' => 'wp_term',
                'object_id' => $queried_object->term_taxonomy_id,
                'nonce' => \wp_create_nonce('create_template_page'),
            ], \admin_url('admin-post.php')),
            'meta'  => [
                'title' => \__('Add Template', 'granola'),
            ],
        ]);
    }

    /**
     * Filters the global $submenu to add post type edit link(s) to the WP admin bar.
     *
     * @param \WP_Admin_Bar $admin_bar An array of WP admin menu items.
     */
    public static function add_post_type_archive_template_admin_bar_link(\WP_Admin_Bar $admin_bar): void
    {
        // Bail early - not on an archive page.
        if (!\is_archive()) {
            return;
        }

        // Bail early - user doesn't have the right capabilities.
        if (!\current_user_can('edit_posts')) {
            return;
        }

        $queried_object = \get_queried_object();

        if (!$queried_object instanceof \WP_Post_Type) {
            return;
        }

        $template_page = self::get_template_page($queried_object);

        // Bail early - template page set already.
        if (self::is_valid_template_page($template_page)) {
            return;
        }

        $admin_bar->add_menu([
            'id'    => 'granola-add-template',
            'title' => \__('Add Template', 'granola'),
            'href'  => \add_query_arg([
                'action' => 'create_template_page',
                'object_type' => 'wp_post_type',
                'object_id' => $queried_object->name,
                'nonce' => \wp_create_nonce('create_template_page'),
            ], \admin_url('admin-post.php')),
            'meta'  => [
                'title' => \__('Add Template', 'granola'),
            ],
        ]);
    }

    /**
     * Filters the global $submenu to add post type edit link(s) to the WP admin bar.
     *
     * @param \WP_Admin_Bar $admin_bar An array of WP admin menu items.
     */
    public static function add_term_add_template_admin_bar_link(\WP_Admin_Bar $admin_bar): void
    {
        // Bail early - not on an admin screen.
        if (!\is_admin()) {
            return;
        }

        // Bail early - user doesn't have the right capabilities.
        if (!\current_user_can('edit_posts')) {
            return;
        }

        $screen = \get_current_screen();
        $taxonomies = self::get_template_taxonomies();

        // Bail early - not currently editing a valid taxonomy term.
        if (empty($screen->taxonomy) || $screen->base !== 'term' || !in_array($screen->taxonomy, $taxonomies, true)) {
            return;
        }

        $term = self::get_term_being_edited();

        // Bail early - invalid term page.
        if (empty($term)) {
            return;
        }

        $template_page_id = \get_term_meta($term->term_id, 'template_page', true);
        $template_page = \get_post($template_page_id);

        // Bail early - template page set already.
        if (self::is_valid_template_page($template_page)) {
            return;
        }

        $admin_bar->add_menu([
            'id'    => 'granola-add-template',
            'title' => \__('Add Template', 'granola'),
            'href'  => \add_query_arg([
                'action' => 'create_template_page',
                'object_type' => 'wp_term',
                'object_id' => $term->term_taxonomy_id,
                'nonce' => \wp_create_nonce('create_template_page'),
            ], \admin_url('admin-post.php')),
            'meta'  => [
                'title' => \__('Add Template', 'granola'),
            ],
        ]);
    }

    /**
     * Filters the global $submenu to add post type edit link(s) to the WP admin bar.
     *
     * @param \WP_Admin_Bar $admin_bar An array of WP admin menu items.
     */
    public static function add_404_add_template_admin_bar_link(\WP_Admin_Bar $admin_bar): void
    {
        // Bail early - on an admin screen or not on a 404 page.
        if (\is_admin() || !\is_404()) {
            return;
        }

        // Bail early - user doesn't have the right capabilities.
        if (!\current_user_can('edit_posts')) {
            return;
        }

        $template_page_id = \get_option('404_template_page', 0);

        // Bail early - template page set already.
        if (!empty($template_page_id)) {
            return;
        }

        $template_page = \get_post($template_page_id);

        // Bail early - template page set already.
        if (self::is_valid_template_page($template_page)) {
            return;
        }

        $admin_bar->add_menu([
            'id'    => 'granola-add-template',
            'title' => \__('Add Template', 'granola'),
            'href'  => \add_query_arg([
                'action' => 'create_template_page',
                'object_type' => '404',
                'object_id' => '0',
                'nonce' => \wp_create_nonce('create_template_page'),
            ], \admin_url('admin-post.php')),
            'meta'  => [
                'title' => \__('Add Template', 'granola'),
            ],
        ]);
    }

    /**
     * Filters the global $submenu to add post type edit link(s) to the WP admin bar on the Search page.
     *
     * @param \WP_Admin_Bar $admin_bar An array of WP admin menu items.
     */
    public static function add_search_add_template_admin_bar_link(\WP_Admin_Bar $admin_bar): void
    {

        // Bail early - on an admin screen or not on a 404 page.
        if (\is_admin() || !\is_search()) {
            return;
        }

        // Bail early - user doesn't have the right capabilities.
        if (!\current_user_can('edit_posts')) {
            return;
        }

        $template_page_id = \get_option('search_template_page', 0);

        // Bail early - template page set already.
        if (!empty($template_page_id)) {
            return;
        }

        $page_object = \Granola\WordPress\PageObject::get();
        $template_page = self::get_template_page($page_object);

        // Bail early - template page set already.
        if (self::is_valid_template_page($template_page)) {
            return;
        }

        $admin_bar->add_menu([
            'id'    => 'granola-add-template',
            'title' => \__('Add Template', 'granola'),
            'href'  => \add_query_arg([
                'action' => 'create_template_page',
                'object_type' => 'search',
                'object_id' => '0',
                'nonce' => \wp_create_nonce('create_template_page'),
            ], \admin_url('admin-post.php')),
            'meta'  => [
                'title' => \__('Add Template', 'granola'),
            ],
        ]);
    }

    /**
     * Add a 'View Template' button to the WP admin bar when editing a granola-template post.
     *
     * @link https://developer.wordpress.org/reference/hooks/admin_bar_menu/
     *
     * @param \WP_Admin_Bar $admin_bar The WP_Admin_Bar instance, passed by reference.
     */
    public static function add_view_toolbar_button(\WP_Admin_Bar $admin_bar): void
    {
        // Bail early - not on an admin screen.
        if (!\is_admin()) {
            return;
        }

        // Bail early - user doesn't have the right capabilities.
        if (!\current_user_can('edit_posts')) {
            return;
        }

        $screen = \get_current_screen();

        // Bail early - not currently on a screen related to granola-template.
        if (empty($screen->post_type) || $screen->post_type !== self::SLUG) {
            return;
        }

        // Bail early - not currently on a post edit screen.
        if (empty($screen->base) || $screen->base !== 'post') {
            return;
        }

        // Retrieves the object (WP_Post_Type|WP_Term) linked to this granola-template post.
        $object = self::get_templated_object(\get_post());

        if (empty($object)) {
            return;
        }

        if ($object instanceof \WP_Term) {
            $link = \get_term_link($object);
        } elseif ($object instanceof \WP_Post_Type) {
            $link = \get_post_type_archive_link($object->name);
        } else {
            return; // Bail early - invalid post meta.
        }

        $admin_bar->add_menu([
            'id'    => 'granola-view-template',
            'title' => \__('View Template', 'granola'),
            'href'  => $link,
            'meta'  => [
                'title' => \__('View Template', 'granola'),
                'class' => 'granola-ab-item granola-view-template'
            ],
        ]);
    }

    /**
     * Add an 'Edit Template' button to the WP admin bar when editing a valid taxonomy
     * term or viewing a valid post type list on the back-end.
     *
     * @link https://developer.wordpress.org/reference/hooks/admin_bar_menu/
     *
     * @param \WP_Admin_Bar $admin_bar The WP_Admin_Bar instance, passed by reference.
     */
    public static function add_admin_bar_edit_toolbar_button(\WP_Admin_Bar $admin_bar): void
    {
        // Bail early - not on an admin screen.
        if (!\is_admin()) {
            return;
        }

        // Bail early - user doesn't have the right capabilities.
        if (!\current_user_can('edit_posts')) {
            return;
        }

        $screen = \get_current_screen();

        // Bail early - wrong screen.
        if (
            $screen->base !== 'term'
            && $screen->base !== 'edit'
            && $screen->base !== 'edit-tags'
        ) {
            return;
        }

        $post_types = self::get_template_post_types();
        $taxonomies = self::get_template_taxonomies();

        if (
            $screen->base === 'term'
            && !empty($screen->taxonomy)
            && !in_array($screen->taxonomy, $taxonomies, true)
        ) {
            return; // Bail early - currently editing an invalid taxonomy term.
        } elseif (
            $screen->base === 'edit'
            && !empty($screen->post_type)
            && !in_array($screen->post_type, $post_types, true)
        ) {
            return; // Bail early - currently viewing an invalid post type list.
        }

        if (!empty($screen->taxonomy) && $screen->base === 'term') {
            $term = self::get_term_being_edited();

            // Bail early - invalid term page.
            if (empty($term)) {
                return;
            }

            $template_page_id = \get_term_meta($term->term_id, 'template_page', true);
        } elseif (!empty($screen->taxonomy) && $screen->base === 'edit-tags') {
            $template_page_id = \get_option("{$screen->taxonomy}_template_page", 0);
        } elseif (!empty($screen->post_type) && $screen->base === 'edit') {
            $template_page_id = \get_option("{$screen->post_type}_template_page", 0);
        }

        if (empty($template_page_id)) {
            return;
        }

        $template_page = \get_post($template_page_id);

        // Bail early - no valid template page set.
        if (!self::is_valid_template_page($template_page)) {
            return;
        }

        $admin_bar->add_menu([
            'id'    => 'granola-edit-template',
            'title' => sprintf(
                // translators: 1: opening html tags. 2: closing html tags.
                \_x('%1$sEdit Template Content%2$s', 'Admin bar edit link', 'granola'),
                '<span class="ab-icon" aria-hidden="true"></span><span class="ab-label">',
                '</span>'
            ),
            'href'  => \get_edit_post_link($template_page),
            'meta'  => [
                'title' => \_x('Edit Template Content', 'Admin bar edit link title', 'granola'),
                'class' => 'granola-ab-item granola-edit-template'
            ],
        ]);
    }

    /**
     * Add an 'Edit Template' button to the WP admin bar when viewing a post type
     * template on the front-end, which is linked to a granola-template post.
     *
     * @link https://developer.wordpress.org/reference/hooks/admin_bar_menu/
     *
     * @param \WP_Admin_Bar $admin_bar The WP_Admin_Bar instance, passed by reference.
     */
    public static function add_post_type_edit_toolbar_button(\WP_Admin_Bar $admin_bar): void
    {
        // Bail early - not on the front-end.
        if (\is_admin()) {
            return;
        }

        // Bail early - not on an archive.
        if (!\is_archive() && !\is_home()) {
            return;
        }

        // Bail early - user doesn't have the right capabilities.
        if (!\current_user_can('edit_posts')) {
            return;
        }

        $page_object = \Granola\WordPress\PageObject::get();
        $template_page = self::get_template_page($page_object);

        // Bail early - no valid template page set.
        if (!self::is_valid_template_page($template_page)) {
            return;
        }

        $admin_bar->add_menu([
            'id'    => 'granola-edit-template',
            'title' => sprintf(
                // translators: 1: opening html tags. 2: closing html tags.
                \_x('%1$sEdit Template Content%2$s', 'Admin bar edit link', 'granola'),
                '<span class="ab-icon" aria-hidden="true"></span><span class="ab-label">',
                '</span>'
            ),
            'href'  => \get_edit_post_link($template_page),
            'meta'  => [
                'title' => \_x('Edit Template Content', 'Admin bar edit link title', 'granola'),
                'class' => 'granola-ab-item granola-edit-template'
            ],
        ]);
    }

    /**
     * Add an 'Edit Template' button to the WP admin bar when viewing a 404 page
     * on the front-end, which is linked to a granola-template post.
     *
     * @link https://developer.wordpress.org/reference/hooks/admin_bar_menu/
     *
     * @param \WP_Admin_Bar $admin_bar The WP_Admin_Bar instance, passed by reference.
     */
    public static function add_404_edit_toolbar_button(\WP_Admin_Bar $admin_bar): void
    {
        // Bail early - not on the front-end.
        if (\is_admin()) {
            return;
        }

        // Bail early - not on a 404 page.
        if (!\is_404()) {
            return;
        }

        // Bail early - user doesn't have the right capabilities.
        if (!\current_user_can('edit_posts')) {
            return;
        }

        $page_object = \Granola\WordPress\PageObject::get();
        $template_page = self::get_template_page($page_object);

        // Bail early - no valid template page set.
        if (!self::is_valid_template_page($template_page)) {
            return;
        }

        $admin_bar->add_menu([
            'id'    => 'granola-edit-template',
            'title' => sprintf(
                // translators: 1: opening html tags. 2: closing html tags.
                \_x('%1$sEdit Template Content%2$s', 'Admin bar edit link', 'granola'),
                '<span class="ab-icon" aria-hidden="true"></span><span class="ab-label">',
                '</span>'
            ),
            'href'  => \get_edit_post_link($template_page),
            'meta'  => [
                'title' => \_x('Edit Template Content', 'Admin bar edit link title', 'granola'),
                'class' => 'granola-ab-item granola-edit-template'
            ],
        ]);
    }

    /**
     * Add an 'Edit Template' button to the WP admin bar when viewing a search page
     * on the front-end, which is linked to a granola-template post.
     *
     * @link https://developer.wordpress.org/reference/hooks/admin_bar_menu/
     *
     * @param \WP_Admin_Bar $admin_bar The WP_Admin_Bar instance, passed by reference.
     */
    public static function add_search_edit_toolbar_button(\WP_Admin_Bar $admin_bar): void
    {
        // Bail early - not on the front-end.
        if (\is_admin()) {
            return;
        }

        // Bail early - not on a search page.
        if (!\is_search()) {
            return;
        }

        // Bail early - user doesn't have the right capabilities.
        if (!\current_user_can('edit_posts')) {
            return;
        }

        $page_object = \Granola\WordPress\PageObject::get();
        $template_page = self::get_template_page($page_object);

        // Bail early - no valid template page set.
        if (!self::is_valid_template_page($template_page)) {
            return;
        }

        $admin_bar->add_menu([
            'id'    => 'granola-edit-template',
            'title' => sprintf(
                // translators: 1: opening html tags. 2: closing html tags.
                \_x('%1$sEdit Template Content%2$s', 'Admin bar edit link', 'granola'),
                '<span class="ab-icon" aria-hidden="true"></span><span class="ab-label">',
                '</span>'
            ),
            'href'  => \get_edit_post_link($template_page),
            'meta'  => [
                'title' => \_x('Edit Template Content', 'Admin bar edit link title', 'granola'),
                'class' => 'granola-ab-item granola-edit-template'
            ],
        ]);
    }

    /**
     * Remove the default 'Edit Page' button from the WP admin bar when viewing the 'Post' post type
     * template on the front-end, which is linked to a 'Page' post via core settings.
     *
     * Hooked in after the button has been added (priority: >80).
     *
     * @link https://developer.wordpress.org/reference/hooks/admin_bar_menu/
     * @see /wp-includes/admin-bar.php:847
     *
     * @param \WP_Admin_Bar $admin_bar The WP_Admin_Bar instance, passed by reference.
     */
    public static function remove_blog_home_edit_page_button(\WP_Admin_Bar $admin_bar): void
    {
        // Bail early - on an admin screen.
        if (\is_admin()) {
            return;
        }

        // Bail early - not on the blog archive page.
        if (!\is_home()) {
            return;
        }

        // Bail early - user doesn't have the right capabilities.
        if (!\current_user_can('edit_posts')) {
            return;
        }

        $admin_bar->remove_menu('edit');
    }

    /**
     * Retrieve the permalink for a Template Page's linked object instead of the Template Page's own permalink.
     *
     * Does not return a filtered permalink for the 404 or search pages (for hopefully obvious reasons).
     *
     * @param string $permalink The template page's permalink.
     * @param \WP_Post $post The template post object.
     * @return string The template page's filtered permalink.
     */
    public static function filter_template_page_permalink(string $permalink, \WP_Post $post): string
    {
        $object = self::get_templated_object($post);

        if (empty($object)) {
            return $permalink;
        }

        if (is_a($object, 'WP_Post_Type')) {
            return \get_post_type_archive_link($object->name);
        } elseif (is_a($object, 'WP_Term')) {
            return \get_term_link($object, $object->taxonomy);
        }

        return $permalink;
    }

    /**
     * Filters the Template Page menu items in the admin Edit Menu screen to prevent unlinked Template Pages, the 404,
     * and the Search Template Page from being available to add to nav menus via the the 'Most Recent', 'View All',
     * and 'Search' menu item selection lists.
     *
     * @link https://developer.wordpress.org/reference/hooks/wp_setup_nav_menu_item/
     *
     * @param object|null $item The menu item object.
     * @return object|null The same menu item object. Null if unlinked or linked to the 404 or Search pages.
     */
    public static function filter_admin_nav_menu_item(?object $item)
    {
        // Bail early - on the front-end.
        if (!\is_admin() || \is_customize_preview()) {
            return $item;
        }

        if (empty($item) || !$item instanceof \WP_Post || $item->post_type !== self::SLUG) {
            return $item;
        }

        if (!self::is_valid_template_page_menu_item($item)) {
            return null;
        }

        return $item;
    }

    /**
     * Filter the initially available Template Page menu items in the customizer.
     *
     * @link https://developer.wordpress.org/reference/hooks/customize_nav_menu_available_items/
     * @see /wp-includes/class-wp-customize-nav-menus.php:300
     *
     * @param array $items The array of menu items.
     * @param string $type The object type.
     * @param string $object The object name.
     * @return array The filtered array of menu items.
     */
    public static function filter_customizer_available_nav_menu_items(array $items, string $type, string $object): array
    {
        if ($type !== 'post_type' || $object !== self::SLUG) {
            return $items;
        }

        return self::filter_customizer_nav_menu_items($items);
    }

    /**
     * Filter any menu items retrieved in the customizer to remove invalid Template Pages.
     *
     * @link https://developer.wordpress.org/reference/hooks/customize_nav_menu_searched_items/
     * @see /wp-includes/class-wp-customize-nav-menus.php:473
     *
     * @param array $items An array of menu items.
     * @return array The filtered array of menu items.
     */
    public static function filter_customizer_nav_menu_items(array $items): array
    {
        return array_values(
            array_filter($items, function ($item) {
                $item_post = \get_post($item['object_id']);

                // Keep this item - not a Template Page post to check.
                if (!$item_post instanceof \WP_Post || $item_post->post_type !== self::SLUG) {
                    return true;
                }

                return self::is_valid_template_page_menu_item($item_post);
            })
        );
    }

    /**
     * Determines whether the given Template Page is linked to an object that should be allowed in a menu.
     *
     * For example, a post type or term archive would be fine, while a 404 page would not.
     *
     * @param mixed $page A Template Page post object.
     * @return boolean Whether the given Template Page is linked to a valid object. False if not linked to an object.
     */
    public static function is_valid_template_page_menu_item($page): bool
    {
        $object = self::get_templated_object($page);

        return !empty($object) && is_object($object) && (!$object instanceof \WP_Taxonomy) && empty($object->type);
    }

    /**
     * Retrieves the filtered page content for a Post Type, Taxonomy, or Term, if an template page is set.
     *
     * @param object $object A WP_Post_Type, WP_Taxonomy, WP_Term, or WP_Query which might have an template page set.
     * @return string|bool The content of the template page, if found, false otherwise.
     */
    public static function get_content($object)
    {
        if (!\Granola\Helpers::is_valid_class($object, ['WP_Post_Type', 'WP_Taxonomy', 'WP_Term', 'WP_Query'])) {
            return false;
        }

        $template_page = self::get_template_page($object);

        // Bail early - invalid template page found,
        if (!self::is_valid_template_page($template_page)) {
            return false;
        }

        // Only return the content (to the public) if the template page has been published.
        // Draft, Pending, etc statuses are valid but should only be viewed by users who can see unpublished content.
        if ($template_page->post_status !== 'publish' && !\current_user_can('edit_posts')) {
            return false;
        }

        return \apply_filters('the_content', $template_page->post_content);
    }


    /**
     * Retrieves the Template Page for a specific object, if it exists.
     *
     * @param object $object A WP_Post_Type, WP_Taxonomy, WP_Term, or WP_Query which might have a template page set.
     * @return \WP_Post|bool The post object, or false if the $object argument isn't valid or no template is set.
     */
    public static function get_template_page($object)
    {
        if (!\Granola\Helpers::is_valid_class($object, ['WP_Post_Type', 'WP_Taxonomy', 'WP_Term', 'WP_Query'])) {
            return false;
        }

        if ($object instanceof \WP_Term) {
            $template_id = \get_term_meta($object->term_id, 'template_page', true);

            // Fallback: term's taxonomy template used for terms without a set template page.
            if (empty($template_id)) {
                $template_id = \get_option("{$object->taxonomy}_template_page", 0);
            }
        } elseif ($object instanceof \WP_Query && $object->is_404()) {
            $template_id = \get_option('404_template_page', 0);
        } elseif ($object instanceof \WP_Query && $object->is_search()) {
            $template_id = \get_option('search_template_page', 0);
        } else {
            $template_id = \get_option("{$object->name}_template_page", 0);
        }

        // Filter the template ID.
        // Allows us to hook in for multi-lingual sites.
        $template_id = \apply_filters('granola/templates/template-page-id', $template_id, $object);


        if (!empty($template_id)) {
            $template_page = \get_post($template_id);

            if (self::is_valid_template_page($template_page)) {
                return $template_page;
            }
        }

        return false;
    }

    /**
     * Determines whether the passed object is a valid template page.
     *
     * A valid template page is a WP_Post object that hasn't been trashed.
     *
     * @param mixed $object The potential Template Page to check.
     * @return boolean Whether the passed object is a valid template page.
     */
    public static function is_valid_template_page(mixed $object): bool
    {
        return !empty($object) && $object instanceof \WP_Post && $object->post_status !== 'trash';
    }


    /**
     * Retrieves the object that the given Template Page is linked to, if one exists.
     *
     * @param \WP_Post $page The Template Page which may be linked to a WP object (WP_Post_Type or WP_Term), or 404 page.
     * @return ?object The object linked to the given Template Page, or null if there is nothing linked.
     */
    public static function get_templated_object(\WP_Post $page): ?object
    {
        $object_data = \get_post_meta($page->ID, 'granola-template-page-data', true);

        if (empty($object_data)) {
            return null;
        }

        if ($object_data->type === 'wp_post_type') {
            $object = \get_post_type_object($object_data->id);
        } elseif ($object_data->type === 'wp_term') {
            $object = \get_term_by('term_taxonomy_id', $object_data->id);
        } elseif ($object_data->type === 'wp_taxonomy') {
            $object = \get_taxonomy($object_data->id);
        } elseif ($object_data->type === '404') {
            $object = (object) $object_data;
        } elseif ($object_data->type === 'search') {
            $object = (object) $object_data;
        }

        if (!empty($object)) {
            return $object;
        }

        return null;
    }

    /**
     * Get the term currently being edited on the edit.php screen in the WordPress admin.
     *
     * @return \WP_Term|null The term object or null on failure.
     */
    public static function get_term_being_edited()
    {
        global $taxnow;

        if (empty($taxnow) || empty($_GET['tag_ID'])) {
            return null;
        }

        $term_id = \absint($_GET['tag_ID']);
        $term    = \get_term($term_id, $taxnow);

        return $term instanceof \WP_Term ? $term : null;
    }

    /**
     * Retrieves the list of taxonomy names that can have a template assigned.
     *
     * @return array An array of taxonomy names that can have a template assigned.
     */
    public static function get_template_taxonomies(): array
    {
        return \apply_filters('granola/templates/taxonomies', []);
    }

    /**
     * Retrieves the list of post type names that can have a template assigned.
     *
     * @return array An array of post type names that can have a template assigned.
     */
    public static function get_template_post_types(): array
    {
        return \apply_filters('granola/templates/post-types', []);
    }

    /**
     * Just adds a new column (template page) to the taxonomy's term list table.
     *
     * @param array $columns The existing columns array.
     * @return array The columns array modified by this function.
     */
    public static function add_taxonomy_template_page_column($columns): array
    {
        // Add new column in the position #4
        $columns = array_slice($columns, 0, 4, true) +
            ['template_page' => \__('Template Page', 'granola')] +
            array_slice($columns, 4, null, true);

        return $columns;
    }

    /**
     * Renders data for the 'template page' column in the taxonomy term list table.
     *
     * @param string $empty The empty column content.
     * @param string $column The current column name.
     * @param int $term_id The term ID.
     * @return string The rendered column content.
     */
    public static function render_taxonomy_template_page_column($empty, $column, $term_id): string
    {

        if ($column !== 'template_page') {
            return $empty;
        }

        $term = \get_term($term_id);

        $template_page_id = \get_term_meta($term_id, 'template_page', true);
        $template_page = \get_post($template_page_id);

        // Check if we have template page assigned to this term / category
        if (empty($template_page) || !($template_page instanceof \WP_Post)) {
            // Add link to create a new template pageif not exists
            return \Granola\Component::get('link', [
                'url' => \add_query_arg([
                    'action' => 'create_template_page',
                    'object_type' => 'wp_term',
                    'object_id' => $term->term_taxonomy_id,
                    'nonce' => \wp_create_nonce('create_template_page'),
                ], \admin_url('admin-post.php')),
                'content' => \__('Add Template', 'granola'),
            ]);
        }

        // Pull link to edit the template page if found
        return \sprintf(
            // translators: 1: Template page link.
            \_x('Edit: %s', 'Template page column', 'granola'),
            \Granola\Component::get('link', [
                'url' => \get_edit_post_link($template_page),
                'content' => $template_page->post_title,
            ])
        );
    }
}
