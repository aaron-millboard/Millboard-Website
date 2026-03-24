<?php

namespace Theme\Plugins;

class ACF
{
    public static function init(): void
    {
        \add_action('acf/init', [__CLASS__, 'option_pages']);
        \add_action('acf/init', [__CLASS__, 'set_acf_google_api_key']);

        \add_action('acf/init', [__CLASS__, 'fix_previews']);
        \add_action('acf/init', [__CLASS__, 'disable_shortcode']);

        // Handles disabling Gutenberg on flexible content template
        \add_filter('gutenberg_can_edit_post_type', [__CLASS__, 'disable_gutenberg'], 10, 2);
        \add_filter('use_block_editor_for_post_type', [__CLASS__, 'disable_gutenberg'], 10, 2);

        // Define custom wysiwyg toolbar.
        \add_filter('acf/fields/wysiwyg/toolbars', [__CLASS__, 'filter_editor_toolbar_types']);

        // Filter the choices for any ACF field named `theme_background_color` to automatically add
        // color options from the color palette/theme.json file.
        // Note: These values will only be updated in the JSON file when the field group is saved.
        \add_filter('acf/load_field/name=theme_background_color', [__CLASS__, 'load_color_field_choices']);
        \add_filter('acf/load_field/name=item_theme_background_color', [__CLASS__, 'load_color_field_choices']);

        // Filter the choices for any ACF field named `gravity_form_id` to automatically add available forms.
        \add_filter('acf/load_field/name=gravity_form_id', [__CLASS__, 'load_gravity_form_ids_choices']);

        // Filter empty link fields to return a consistent data type.
        \add_filter('acf/load_value/type=link', [__CLASS__, 'filter_empty_link_field']);

        // Register the "strip HTML" option to ACF wysiwyg fields' settings and use it to strip tags where needed.
        \add_action('acf/render_field_presentation_settings/type=wysiwyg', [__CLASS__, 'add_strip_html_field_setting']);
        \add_filter('acf/format_value/type=wysiwyg', [__CLASS__, 'strip_field_value_html_tags'], 20, 3);

        // Remove ACF 6.1's post type and taxonomy registration admin pages.
        \add_filter('acf/settings/enable_post_types', '__return_false');

        // Rename image field attachment ID array key from 'id' or 'ID' to 'attachment_id'.
        // Must be hooked higher than 10, as ACF converts from int to array on priority 10.
        \add_filter('acf/format_value/type=image', [__CLASS__, 'format_image_field_value'], 20, 1);

        // Add custom menu depth location rule for ACF field groups.
        \add_filter('acf/location/rule_types', [__CLASS__, 'acf_location_rules_types']);
        \add_filter('acf/location/rule_values/menu_level', [__CLASS__, 'acf_location_rule_values_level']);
        \add_filter('acf/location/rule_match/menu_level', [__CLASS__, 'acf_location_rule_match_level'], 10, 4);

        // Add custom field types.
        \add_action('acf/include_field_types', [ __CLASS__, 'register_custom_field_types' ]);
    }

    public static function option_pages(): void
    {
        $options_pages = [
            \_x('General', 'ACF options page name', 'granola'),
            \_x('Header', 'ACF options page name', 'granola'),
            \_x('Integrations', 'ACF options page name', 'granola'),
            \_x('Product Calculator', 'ACF options page name', 'granola'),
        ];

        if (empty($options_pages)) {
            return;
        }

        //  Create a top-level page to nest options pages under.
        \acf_add_options_page();

        // Create sub-pages.
        foreach ($options_pages as $page) {
            \acf_add_options_sub_page($page);
        }
    }

    /**
     * Set ACF's Google API key from a site option, if it exists.
     */
    public static function set_acf_google_api_key(): void
    {
        $option = \get_field('google_api_key', 'option');

        if (empty($option)) {
            return;
        }

        \acf_update_setting('google_api_key', $option);
    }

    public static function load_color_field_choices(array $field): array
    {
        $field['choices']['none'] = \__('None', 'granola');

        if (defined('GRANOLA_COLOR_PALETTE')) {
            foreach (GRANOLA_COLOR_PALETTE as $color) {
                $field['choices'][$color['slug']] = $color['name'];
            }
        }

        return $field;
    }

    public static function load_gravity_form_ids_choices(array $field): array
    {
        // Check if the GFAPI class exists
        if (!class_exists('\\GFAPI')) {
            return $field;
        }
        $forms = \GFAPI::get_forms();

        if (empty($forms)) {
            return $field;
        }

        foreach ($forms as $form) {
            $field['choices'][$form['id']] = $form['title'];
        }

        return $field;
    }

    public static function disable_gutenberg(bool $can_edit, string $post_type): bool
    {
        if (!(\is_admin() && !empty($_GET['post']))) {
            return $can_edit;
        }

        if (self::disable_editor($_GET['post'])) {
            $can_edit = false;
        }

        return $can_edit;
    }

    public static function disable_editor($id = false): bool
    {
        $excluded_templates = [
            // 'page-templates/example-template.php',
        ];

        if (empty($id)) {
            return false;
        }

        $id = intval($id);
        $template = \get_page_template_slug($id);

        return in_array($template, $excluded_templates);
    }

    /**
     * Filters ACF wysiwyg toolbar types array.
     *
     * @see /advanced-custom-fields-pro/includes/fields/class-acf-field-wysiwyg.php
     * @link https://www.advancedcustomfields.com/resources/customize-the-wysiwyg-toolbars/
     *
     * @param array[] $toolbars An array of ACF TinyMCE wysiwyg toolbar types.
     * @return array[] $toolbars The filtered array of ACF TinyMCE wysiwyg toolbar types.
     */
    public static function filter_editor_toolbar_types($toolbars): array
    {
        // Remove ACF defaults.
        unset($toolbars['Basic']);
        unset($toolbars['Full']);

        // Define 'Basic' toolbar with custom selection of TinyMCE buttons.
        $toolbars['Basic Formatting'] = [
            1 => \apply_filters('granola/acf/fields/wysiwyg/toolbars/basic', [
                'bold',
                'italic',
                'link',
                'unlink',
                'removeformat',
                'undo',
                'redo'
            ]),
        ];

        // Define 'Extended' toolbar with wider selection of TinyMCE buttons,
        // Includes bulleted lists and heading/paragraph formatting.
        $toolbars['Extended Formatting'] = [
            1 => \apply_filters('granola/acf/fields/wysiwyg/toolbars/extended', [
                'formatselect',
                'bold',
                'italic',
                'bullist',
                'numlist',
                'link',
                'unlink',
                'removeformat',
                'undo',
                'redo'
            ]),
        ];

        $toolbars['Heading'] = [
            1 => \apply_filters('granola/acf/fields/wysiwyg/toolbars/extended', [
                'bold',
            ]),
        ];

        return $toolbars;
    }

    /**
     * Fix a long-standing issue with ACF, where fields sometimes aren't shown in
     * previews (i.e. from Preview > Open in new tab).
     *
     * @link https://support.advancedcustomfields.com/forums/topic/custom-fields-on-post-preview/#post-150273
     */
    public static function fix_previews(): void
    {
        if (\current_user_can('edit_posts') && class_exists('acf_revisions')) {
            $acf_revs_cls = \acf()->revisions;
            \remove_filter('acf/validate_post_id', [$acf_revs_cls, 'acf_validate_post_id', 10]);
        }
    }

    /**
     * Disable ACF shortcode for security reasons.
     *
     * @link https://www.advancedcustomfields.com/blog/acf-6-0-3-release-security-changes-to-the-acf-shortcode-and-ui-improvements/
     */
    public static function disable_shortcode(): void
    {
        \acf_update_setting('enable_shortcode', false);
    }

    /**
     * Filter all ACF link fields to return a more consistent data type.
     *
     * By default, ACF returns an empty string for link fields that have been created but not filled in. This filter
     * ensures that link fields always return an array in order to help prevent fatal errors caused by unexpected data
     * type mismatches.
     *
     * @param mixed $value The link field value
     * @return array $value The link field value or an empty array, for consistency.
     */
    public static function filter_empty_link_field($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return $value;
    }

    /**
     * Add a custom "strip HTML" option to an ACF field's settings.
     *
     * @link https://www.advancedcustomfields.com/resources/acf_render_field_setting/
     *
     * @param array $field The field array.
     * @return void
     */
    public static function add_strip_html_field_setting($field): void
    {
        \acf_render_field_setting($field, [
            'label'        => 'Strip unwanted HTML tags?', // translations unlikely to be needed.
            'instructions' => htmlentities('Removes most tags from the loaded field value, including <p> tags.
                Useful for content that will be displayed in an <h1-6> tag.'),
            'name'         => 'strip_html_tags',
            'type'         => 'true_false',
            'ui'           => 1,
        ]);
    }

    /**
     * Conditionally strip unwanted HTML tags from a field value, based on custom field setting.
     *
     * Should happen *after* being loaded by a template function (such as `get_field()`) via the
     * `acf/format_value` filter.
     *
     * @param mixed $value The field value.
     * @param integer|string $post_id The post ID where the value is saved.
     * @param array $field The field array containing all settings.
     * @return mixed The filtered field value.
     */
    public static function strip_field_value_html_tags(mixed $value, int|string $post_id, array $field): mixed
    {
        // Bail early - no 'strip_html_tags' setting or set to false.
        if (empty($field['strip_html_tags'])) {
            return $value;
        }

        // Strip unwanted HTML tags, leaving only the listed tags.
        // Not an exhaustive list of allowed tags, but should cover most cases.
        return strip_tags($value, [
            'a',
            'br',
            'em',
            'span',
            'strong',
        ]);
    }

    /**
     * Renames the attachment ID array key for image fields to avoid component arg collisions.
     *
     * ACF provides a data array for image fields, including the attachment ID. The attachement ID is assigned to
     * `id` and `ID` keys, which clashes with Granola's component 'id' argument. This renames the attachment ID key to
     * `attachment_id`.
     *
     * @param mixed $value The default image field data array.
     * @return mixed The filtered array, or the original value if not an array.
     */
    public static function format_image_field_value(mixed $value): mixed
    {
        // Bail early - not in the expected format.
        if (!is_array($value)) {
            return $value;
        }

        if (!empty($value['id'])) {
            $value['attachment_id'] = $value['id'];
        } elseif (!empty($value['ID'])) {
            $value['attachment_id'] = $value['ID'];
        }

        unset($value['id']);
        unset($value['ID']);

        return $value;
    }

    /**
     * Disable the field in the editor.
     * This can be useful if you are, for example, storing data from a third party platform for use on the site.
     *
     * @param array $field The field array containing all settings.
     * @return array The filtered field array containing all settings.
     */
    public static function disable_field(array $field): array
    {
        $field['disabled'] = true;

        return $field;
    }

    /**
     * Add custom menu depth location rule type to ACF.
     *
     * @param array $choices The location rule type choices.
     * @return array The filtered location rule type choices.
     */
    public static function acf_location_rules_types(array $choices): array
    {
        $choices['Menu']['menu_level'] = 'Menu Depth';

        return $choices;
    }

    /**
     * Define available values for the menu depth location rule.
     *
     * @param array $choices The location rule value choices.
     * @return array The filtered location rule value choices.
     */
    public static function acf_location_rule_values_level(array $choices): array
    {
        $choices[0] = '0';
        $choices[1] = '1';

        return $choices;
    }

    /**
     * Match the menu depth location rule against the current menu item.
     *
     * @param bool $match Whether the rule matches.
     * @param array $rule The location rule being matched.
     * @param array $options The options containing menu item data.
     * @param array $field_group The field group being evaluated.
     * @return bool Whether the rule matches.
     */
    public static function acf_location_rule_match_level(bool $match, array $rule, array $options, array $field_group): bool
    {
        $current_screen = \get_current_screen();

        if ($current_screen->base === 'nav-menus') {
            if ($rule['operator'] === '==') {
                $match = ($options['nav_menu_item_depth'] == $rule['value']);
            }
        }

        return $match;
    }

    /**
     * Register custom taxonomy selector field.
     *
     * @return void
     */
    public static function register_custom_field_types(): void
    {
        new ACF\AcfFieldTaxonomySelector([
            'version' => '1.0.0',
            'url' => \get_theme_file_uri(__FILE__),
            'path' => \get_theme_file_path(__FILE__)
        ]);
    }
}
