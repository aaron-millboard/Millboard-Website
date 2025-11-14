<?php

namespace Granola;

class Component extends Partial
{
    /**
     * Constructor.
     *
     * @param string $name The Component's name.
     * @param array $args The arguments to pass to the Component.
     */
    public function __construct(public readonly string $name, $args = [])
    {
        parent::__construct("assets/components/$name", $args);
    }

    /**
     * Initialise class to set up hooks and filters for all Components.
     */
    public static function init(): void
    {
        \add_action('after_setup_theme', [__CLASS__, 'load_component_functions']);
        \add_action('after_setup_theme', [__CLASS__, 'load_component_hooks']);

        \add_action('acf/init', [__CLASS__, 'load_component_blocks']);

        \add_filter('acf/settings/load_json', [__CLASS__, 'load_block_field_group_json']);
        \add_filter('acf/json/save_paths', [__CLASS__, 'save_block_field_group_json'], 10, 2);

        \add_action('granola/partial/before', [__CLASS__, 'enqueue_scripts'], 10, 3);
        \add_action('granola/partial/before', [__CLASS__, 'enqueue_styles'], 10, 3);

        // Enable saving non-block ACF groups into component directories.
        \add_filter('acf/field_group/additional_group_settings_tabs', [__CLASS__, 'add_save_location_settings_tab']);
        \add_action('acf/field_group/render_group_settings_tab/granola_acf_field_group_settings', [__CLASS__, 'render_save_location_dropdown_fields']);

        // Add a dynamic filter which enables filtering specific components' args.
        \add_filter('granola/partial', [__CLASS__, 'add_dynamic_component_args_filter'], 10, 2);

        // Add a dynamic filter which enables filtering specific partials' output.
        \add_filter('granola/partial/output', [__CLASS__, 'add_dynamic_component_output_filter'], 10, 3);
    }

    /**
     * Load all components' functions.php files.
     *
     * @return void
     */
    public static function load_component_functions(): void
    {
        self::require(
            glob(\Granola\Paths::theme_asset_path('components/{**/*,*}/functions.php'), GLOB_BRACE)
        );
    }

    /**
     * Load all components' hooks.php files.
     *
     * @return void
     */
    public static function load_component_hooks(): void
    {
        self::require(
            glob(\Granola\Paths::theme_asset_path('components/{**/*,*}/hooks.php'), GLOB_BRACE)
        );
    }

    /**
     * Load all component's block.json files to register their ACF blocks.
     *
     * @return void
     */
    public static function load_component_blocks(): void
    {
        $block_json_files = \apply_filters('granola/component/load_blocks_files', glob(
            \Granola\Paths::theme_asset_path('components/*/block.json')
        ));

        foreach ($block_json_files as $file_path) {
            \register_block_type($file_path);
        }
    }

    /**
     * Helper function to load multiple 'required' files, e.g. functions.php, hook.php, etc.
     *
     * Uses require_once, despite the function name.
     *
     * @param array $files File paths to require.
     * @return void
     */
    private static function require(array $files): void
    {
        $files = \apply_filters('granola/component/require_files', $files);

        foreach ($files as $key => $file) {
            require_once $file;
        }
    }

    /**
     * Enqueue a component's block.js file, if one exists, when it is rendered.
     *
     * @param string $path The component path.
     * @param array $args The component arguments
     * @param Component $component The component object
     * @return void
     */
    public static function enqueue_scripts(string $path, array $args, Component $component): void
    {
        self::enqueue_script_by_filename($component->name);
    }

    /**
     * Enqueue a JS file from a component's scripts directory, if it exists.
     *
     * @param string $name The component name.
     * @param string $script The script file name. Default 'block'.
     * @return void
     */
    public static function enqueue_script_by_filename(string $name, string $script = 'block'): void
    {
        $js_path = \Granola\Asset::extract("components/$name/scripts/$script.js");

        if (empty($js_path)) {
            return;
        }

        if (!file_exists(\Granola\Paths::theme_asset_path($js_path))) {
            return;
        }

        \wp_enqueue_script(
            "$name-scripts",
            \Granola\Asset::URL($js_path, true),
            \apply_filters("granola/partial/$name/enqueue_script_dependencies", []),
            \apply_filters("granola/partial/$name/enqueue_script_in_footer", false),
        );
    }

    /**
     * Enqueue a component's block.css file, if one exists, when it is rendered.
     *
     * @param string $path The component path.
     * @param array $args The component arguments
     * @param Component $component The component object
     * @return void
     */
    public static function enqueue_styles(string $path, array $args, Component $component): void
    {
        self::enqueue_style_by_filename($component->name);
    }

    /**
     * Enqueue a CSS file from a component's styles directory, if it exists.
     *
     * @param string $name The component name.
     * @param string $script The style file name. Default 'block'.
     * @return void
     */
    public static function enqueue_style_by_filename(string $name, string $style = 'block'): void
    {
        $css_path = \Granola\Asset::extract("components/$name/styles/$style.css");

        if (empty($css_path)) {
            return;
        }

        if (!file_exists(\Granola\Paths::theme_asset_path($css_path))) {
            return;
        }

        \wp_enqueue_style(
            "$name-styles",
            \Granola\Asset::URL($css_path, true),
            \apply_filters("granola/partial/$name/enqueue_style_dependencies", []),
        );
    }

    /**
     * Retrieve the current Component or the closest Component ancestor of the current Partial.
     *
     * @return Component|null The current Component in the stack or null if none set.
     */
    public static function get_current_component(): ?Component
    {
        if (empty(static::$partial_stack)) {
            return null;
        }

        foreach (static::$partial_stack as $partial) {
            if ($partial instanceof Component) {
                return $partial;
            }
        }

        return null;
    }

    /**
     * Load ACF block field groups from components' JSON files.
     *
     * @see save_block_field_group_json()
     * @link https://www.advancedcustomfields.com/resources/local-json/
     *
     * @param array $paths An array of potential paths to load JSON from.
     * @return array The filtered array of paths to load JSON from.
     */
    public static function load_block_field_group_json(array $paths): array
    {
        return array_merge(
            $paths,
            glob(\Granola\Paths::theme_asset_path('components/*'))
        );
    }

    /**
     * Conditionally save ACF field group JSON files into custom directories.
     *
     * @see load_block_field_group_json()
     * @link https://www.advancedcustomfields.com/resources/local-json/
     *
     * @param array $paths An array of possible save paths for the JSON file.
     * @param array $group The settings for the field group, post type, taxonomy, or options page.
     * @param array $paths The filtered array of possible save paths for the JSON file.
     */
    public static function save_block_field_group_json(array $paths, array $group): array
    {
        $component_name = '';

        if (!empty($group['granola_save_location'])) {
            $component_name = $group['granola_save_location'];
        } elseif (!empty($group['location'][0][0]['param']) && $group['location'][0][0]['param'] === 'block') {
            $component_name = str_replace('acf/', '', $group['location'][0][0]['value']);
        }

        $component_path = self::get_full_component_path($component_name);

        if (empty($component_path)) {
            return $paths;
        }

        return [$component_path];
    }

    /**
     * Retrieve a full path for a specific component.
     *
     * @param string $component_name The name of the component.
     * @return string|null The full path of the component. Null if nothing found.
     */
    public static function get_full_component_path(string $component_name): ?string
    {
        if (empty($component_name)) {
            return null;
        }

        // Should only return a one-item array (unless there is a component name collision).
        $component_paths = glob(\get_theme_file_path("_src/components*/$component_name"), GLOB_ONLYDIR);

         // Bail early - no directory found via glob.
        if (empty($component_paths) || !is_array($component_paths)) {
            return null;
        }

        // Bail early - invalid component path found.
        if (empty($component_paths[0]) || !is_dir($component_paths[0])) {
            return null;
        }

        return $component_paths[0];
    }

    /**
     * Add a Granola Settings tab to all field groups settings.
     *
     * @param array $tabs An array of custom ACF field group tabs.
     * @return array The filtered array of custom ACF field group tabs.
     */
    public static function add_save_location_settings_tab(array $tabs): array
    {
        $tabs['granola_acf_field_group_settings'] = \__('Granola Settings', 'granola');
        return $tabs;
    }

    /**
     * Render save location dropdown in custom ACF Granola Settings tab.
     *
     * @param array $field_group An array of field group settings.
     * @return void
     */
    public static function render_save_location_dropdown_fields(array $field_group): void
    {
        $component_dir_paths = glob(\get_theme_file_path("_src/components*/*"));

        // Bail early - no component directories found.
        if (empty($component_dir_paths) || !is_array($component_dir_paths)) {
            return;
        }

        // Generate dropdown options from all component directory names.
        $choices_array = [];
        foreach ($component_dir_paths as $component_dir_path) {
            $component_name = substr($component_dir_path, strrpos($component_dir_path, '/') + 1);

            // Skip '_template' component.
            if (\str_starts_with($component_name, '_')) {
                continue;
            }

            $choices_array[$component_name] = ucwords(str_replace(['-', 'wp'], [' ', 'WP'], $component_name));
        }

        ksort($choices_array); // Alphabetise components.

        \acf_render_field_wrap(
            [
                'label' => \__('Save Location', 'granola'),
                'type' => 'select',
                'prefix' => 'acf_field_group',
                'name' => 'granola_save_location',
                'value' => isset($field_group['granola_save_location']) ? $field_group['granola_save_location'] : 0,
                'choices' => [
                    \__('Default save location', 'granola'),
                    \__('Component Folder', 'granola') => $choices_array, // Component group.
                ],
                'instructions' => \__("Select a custom location to save this field group's JSON file.", 'granola'),
            ],
            'div',
            'label',
        );
    }

    /**
     * The default render callback used for ACF blocks.
     *
     * @param array $block The array of block attributes passed to ACF's render_callback.
     * @param string $content The block content.
     * @param boolean $is_preview Whether the block is being rendered for editing preview.
     * @param integer $post_id The current post being edited or viewed.
     * @return void
     */
    public static function acf_render_callback(array $block, string $content = '', bool $is_preview = false, int $post_id = 0): void
    {
        $args = parent::generate_args_from_block($block, \get_fields(), $content, $is_preview, $post_id);

        echo self::get(str_replace('acf/', '', $block['name']), $args);
    }

    /**
     * Adds a dynamic filter to enable filtering the arguments of a specific component.
     *
     * The dynamic portion of the hook name, `$name`, refers to the component name.
     *
     * @param ?array $args An array of component args.
     * @param self $component The component instance.
     * @return ?array The filtered array of component args.
     */
    public static function add_dynamic_component_args_filter(?array $args, self $component): ?array
    {
        return \apply_filters("granola/component/{$component->name}", $args, $component);
    }

    /**
     * Adds a dynamic filter to enable filtering the output of a specific component.
     *
     * The dynamic portion of the hook name, `$name`, refers to the component name.
     *
     * @param string $output The component output string.
     * @param ?array $args An array of component args.
     * @param self $component The component instance.
     * @return string The filtered component output string.
     */
    public static function add_dynamic_component_output_filter(string $output, ?array $args, self $component): string
    {
        return \apply_filters("granola/component/output/{$component->name}", $output, $args, $component);
    }
}
