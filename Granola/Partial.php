<?php

namespace Granola;

class Partial implements \Stringable
{
    /**
     * The Partial's data and settings.
     */
    public ?array $args = [];

    /**
     * The current stack of partials. Used to track nested/descendant partials' ancestors.
     */
    protected static array $partial_stack = [];

    /**
     * The Partial's parent, if one exists.
     */
    public readonly ?Partial $parent;

    /**
     * Constructor.
     *
     * @param string $path The Partial's file path (relative to the root directory).
     * @param array $args The arguments to pass to the Partial.
     */
    public function __construct(public readonly string $path, array $args = [])
    {
        $this->parent = static::get_current_partial();

        // Push the current Partial onto the stack.
        // Enables Partials instantiated in other Partials' `$args` filter functions to set a parent Partial.
        static::push_partial($this);

        // Filter the `$args` data being used by the partial for output.
        $this->args = \apply_filters("granola/partial", $args, $this);

        // Pop the current partial off the stack.
        static::pop_partial();
    }

    /**
     * Initialise class to set up hooks and filters for all partials.
     */
    public static function init(): void
    {
        // Add a dynamic filter which enables filtering specific partials' args.
        \add_filter('granola/partial', [__CLASS__, 'add_dynamic_partial_args_filter'], 10, 2);

        // Add a dynamic filter which enables filtering specific partials' output.
        \add_filter('granola/partial/output', [__CLASS__, 'add_dynamic_partial_output_filter'], 10, 3);

        // Set class args for partials.
        \add_filter('granola/partial', [__CLASS__, 'build_partial_classes'], 40);

        // Set attribute args for partials.
        \add_filter('granola/partial', [__CLASS__, 'build_partial_attributes'], 100);
    }

    /**
     * Adds a dynamic filter to enable filtering the arguments of a specific partial.
     *
     * The dynamic portion of the hook name, `$path`, refers to the partial path.
     *
     * @param ?array $args An array of partial args.
     * @param self $partial The partial instance.
     * @return ?array The filtered array of partial args.
     */
    public static function add_dynamic_partial_args_filter(?array $args, self $partial): ?array
    {
        return \apply_filters("granola/partial/{$partial->path}", $args, $partial);
    }

    /**
     * Adds a dynamic filter to enable filtering the output of a specific partial.
     *
     * The dynamic portion of the hook name, `$path`, refers to the partial path.
     *
     * @param string $output The partial output string.
     * @param ?array $args An array of partial args.
     * @param self $partial The partial instance.
     * @return string The filtered partial output string.
     */
    public static function add_dynamic_partial_output_filter(string $output, ?array $args, self $partial): string
    {
        return \apply_filters("granola/partial/output/{$partial->path}", $output, $args, $partial);
    }

    /**
     * Returns the partial's template output.
     *
     * @return string The partial template ouput.
     */
    public function __toString(): string
    {
        $args = $this->args;

        // Bail early - args have been nulled, don't output partial.
        if ($args === null) {
            return '';
        }

        // Push the current Partial onto the stack.
        // Enables Partials instantiated in other Partials' templates to set a parent Partial.
        static::push_partial($this);

        ob_start();

        \do_action('granola/partial/before', $this->path, $args, $this);

        require \Granola\Paths::resolve($this->path); // Render the partial.

        \do_action('granola/partial/after', $this->path, $args, $this);

        // Pop the current partial off the stack.
        static::pop_partial();

        // Return the partial template output string.
        return \apply_filters('granola/partial/output', ob_get_clean(), $args, $this);
    }


    /**
     * Allow partial class instances to be treated like a function/callback which outputs the partial's template.
     *
     * This means a partial instance can be passed to a callback parameter, such as an action hook. For example:
     * \add_action('wp_body_open', \Granola\Partial::get('example'));
     *
     * @return void
     */
    public function __invoke(): void
    {
        echo $this;
    }

    /**
     * Returns a new Partial instance based on a partial file path and arguments.
     *
     * Can be echoed/output due to the __toString() method.
     * Uses the `static()` method so that child classes calling this function will use their own constructors.
     *
     * @param string $path The partial's path (relative to the root directory).
     * @param array  $args The arguments to pass to the partial.
     * @return Partial The Partial object instance.
     */
    public static function get(string $path, $args = []): Partial
    {
        return new static($path, $args);
    }

    /**
     * Retrieve the current Partial.
     *
     * @return Partial|null The current Partial in the stack or null if none set.
     */
    public static function get_current_partial(): ?Partial
    {
        if (empty(static::$partial_stack)) {
            return null;
        }

        return static::$partial_stack[\array_key_last(static::$partial_stack)];
    }

    /**
     * Append the given Partial to the partial stack.
     *
     * @param Partial $partial A Partial object instance.
     */
    protected static function push_partial($partial): void
    {
        static::$partial_stack[] = $partial;
    }

    /**
     * Remove the last (most recent) Partial from the partial stack.
     *
     * @return Partial|null The Partial that was removed from the stack or null if stack is empty.
     */
    protected static function pop_partial(): ?Partial
    {
        return array_pop(static::$partial_stack);
    }

    /**
     * Generate args for a Partial from block attributes and, optionally, associated ACF fields.
     *
     * Typically run during the block's `render_callback` function.
     *
     * @param array $block The array of block attributes passed to ACF's render_callback.
     * @param array|bool $acf_fields Usually whatever is returned from get_fields().
     * @param string $content The block content.
     * @param boolean $is_preview Whether the block is being rendered for editing preview.
     * @param integer $post_id The current post being edited or viewed.
     * @return array The filtered array of block attributes passed to ACF's render_callback.
     */
    public static function generate_args_from_block(array $block, $acf_fields, string $content = '', bool $is_preview = false, int $post_id = 0): array
    {
        $args = is_array($acf_fields) ? $acf_fields : [];

        $args['is_preview'] = $is_preview;
        $args['post_id'] = $post_id;

        if (!empty($block['anchor'])) {
            $args['anchor'] = $block['anchor'];
        }

        if (!empty($block['class_name'])) {
            $args['classes'] = [
                $block['class_name'],
            ];
        }

        if (!empty($args['anchor']) && empty($args['attributes']['id'])) {
            $args['attributes']['id'] = $args['anchor'];
        }

        if (!empty($block['align'])) {
            $args['align'] = $block['align'];
        }

        if (!empty($block['name'])) {
            $args['editor_block_name'] = $block['name'];
        }

        // $args['backgroundColor'] - the built-in WP Background Color settings.
        if (!empty($block['backgroundColor'])) {
            $args['background_color'] = $block['backgroundColor'];
        }

        // $args['theme_background_color'] - our acf field with auto-loaded options.
        if (!empty($block['theme_background_color'])) {
            $args['background_color'] = $block['theme_background_color'];
        }

        return $args;
    }

    /**
     * Adds some common HTML classes based on the given Partial args.
     *
     * Run after the partial filtering via the dynamic granola/partial/components/{block-name} filter.
     *
     * @param array|null $args An array of partial args.
     * @return array|null The filtered partial args, with common classes added.
     */
    public static function build_partial_classes(?array $args): ?array
    {
        if ($args === null) {
            return $args;
        }

        $args['classes'] = $args['classes'] ?? [];

        if (!empty($args['align'])) {
            $args['classes'][] = 'align' . $args['align'];
        }

        if (!empty($args['background_color']) && $args['background_color'] !== 'none') {
            $args['classes'][] = 'has-background';
            $args['classes'][] = 'has-' . $args['background_color'] . '-background-color';
        }

        return $args;
    }

    /**
     * Adds required properties to the $args['attributes'] array.
     *
     * Run after the partial filtering via the dynamic granola/partial/components/{block-name} filter.
     *
     * @param array|null $args An array of partial args.
     * @return array|null The filtered partial args, with the class attribute added.
     */
    public static function build_partial_attributes(?array $args): ?array
    {
        if ($args === null) {
            return $args;
        }

        $args['attributes'] = $args['attributes'] ?? [];

        if (!is_array($args['attributes'])) {
            return $args;
        }

        if (!empty($args['classes'])) {
            $args['attributes']['class'] = $args['classes'];
        }

        if (!empty($args['id'])) {
            $args['attributes']['id'] = $args['id'];
        }

        return $args;
    }
}
