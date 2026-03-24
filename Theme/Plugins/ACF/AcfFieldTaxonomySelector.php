<?php

namespace Theme\Plugins\ACF;

class AcfFieldTaxonomySelector extends \acf_field
{
    /**
     * Store field information.
     *
     * @var array
     */
    protected array $settings;

    /**
     * Instantiate a new ACF Field Type.
     *
     * This method will setup the field type data.
     *
     * @param array $settings Plugin information.
     */
    public function __construct(array $settings)
    {
        $this->name = 'taxonomy_selector';
        $this->label = \__('Taxonomy Selector');
        $this->category = 'relational';
        $this->defaults = [
            'field_type' => 'checkbox',
            'layout' => 'vertical',
            'optgroup' => false,
            'multiple' => 0,
            'allow_null' => 0,
            'return_format' => 'id',
            'allow_private' => false,
            'post_type' => [],
        ];

        parent::__construct();

        $this->settings = $settings;
    }

    /**
     * Create the field's settings.
     *
     * @param array $field The field being edited.
     * @return void
     */
    public function render_field_settings(array $field): void
    {
        $field = array_merge($this->defaults, $field);

        \acf_render_field_setting($field, [
            'label' => \__('Filter by Post Type', 'acf'),
            'instructions' => '',
            'type' => 'select',
            'name' => 'post_type',
            'choices' => acf_get_pretty_post_types(),
            'multiple' => 1,
            'ui' => 1,
            'allow_null' => 1,
            'placeholder' => \__('All post types', 'acf'),
        ]);

        \acf_render_field_setting($field, [
            'label' => \__('Return Value', 'acf'),
            'instructions' => '',
            'type' => 'radio',
            'name' => 'return_format',
            'layout' => 'horizontal',
            'choices' => [
                'object' => \__('Taxonomy Object', 'acf'),
                'id' => \__('Taxonomy Name', 'acf')
            ],
        ]);

        \acf_render_field_setting($field, [
            'label' => \__('Appearance', 'acf'),
            'instructions' => \__('Select the appearance of this field', 'acf'),
            'type' => 'select',
            'name' => 'field_type',
            'optgroup' => true,
            'choices' => [
                \__('Multiple Values', 'acf') => [
                    'checkbox' => \__('Checkbox', 'acf'),
                    'multi_select' => \__('Multi Select', 'acf')
                ],
                \__('Single Value', 'acf') => [
                    'radio' => \__('Radio Buttons', 'acf'),
                    'select' => \__('Select', 'acf')
                ]
            ]
        ]);

        \acf_render_field_setting($field, [
            'label' => \__('Allow Null?', 'acf'),
            'instructions' => '',
            'type' => 'true_false',
            'name' => 'public',
            'ui' => 1,
        ]);

        \acf_render_field_setting($field, [
            'label' => \__('Allow Private?', 'granola'),
            'instructions' => 'Whether to allow privately registered taxonomies.',
            'type' => 'true_false',
            'name' => 'allow_private',
            'ui' => 1,
        ]);
    }

    /**
     * Create the HTML interface for the field.
     *
     * @param array $field The field being rendered.
     * @return void
     */
    public function render_field($field)
    {
        $field = array_merge($this->defaults, $field);

        $args = [
            'public' => !$field['allow_private'],
        ];

        if (!empty($field['post_type'])) {
            $args['object_type'] = $field['post_type'];
        }

        /**
         * Filters the arguments for retrieving a list of registered taxonomy objects.
         *
         * @param array  $args   Array of arguments to match against the taxonomy objects.
         * @param array  $field  An array holding all the field's data.
         */
        $args = \apply_filters('acf/fields/taxonomy_selector/args', $args, $field);
        $args = \apply_filters("acf/fields/taxonomy_selector/args/name={$field['_name']}", $args, $field);
        $args = \apply_filters("acf/fields/taxonomy_selector/args/key={$field['key']}", $args, $field);

        $excluded = [ 'post_format', 'nav_menu', 'link_category' ];

        /**
         * Filters the list taxonomies to exclude.
         *
         * @param string[]  $excluded   Array of taxonomy names.
         * @param array     $field      An array holding all the field's data.
         */
        $excluded = \apply_filters('acf/fields/taxonomy_selector/excluded_taxonomies', $excluded, $field);
        $excluded = \apply_filters("acf/fields/taxonomy_selector/excluded_taxonomies/name={$field['_name']}", $excluded, $field);
        $excluded = \apply_filters("acf/fields/taxonomy_selector/excluded_taxonomies/key={$field['key']}", $excluded, $field);

        $taxonomies = \get_taxonomies($args, 'objects');

        foreach ($taxonomies as $taxonomy) {
            if (in_array($taxonomy->name, $excluded)) {
                continue;
            }

            $field['choices'][ $taxonomy->name ] = $taxonomy->labels->name;
        }

        switch ($field['field_type']) {
            case 'select':
            case 'multi_select':
                $field['type']     = 'select';
                $field['multiple'] = intval('multi_select' === $field['field_type']);
                $field['ui'] = 1;
                $field['ajax'] = 0;

                break;

            case 'radio':
            case 'checkbox':
                $field['type']     = $field['field_type'];
                $field['multiple'] = intval('checkbox' === $field['field_type']);

                break;
        }

        \acf_render_field($field);
    }

    /**
     * Update value.
     *
     * This filter is applied to the $value before it is updated in the DB.
     *
     * @param string[] $value The value which will be saved in the database.
     * @param integer|string $post_id The $post_id to which the value will be saved. 'options' if an option value.
     * @param array $field The field array holding all the field options.
     *
     * @return $value - the modified value.
     */
    public function update_value($value, int|string $post_id, array $field)
    {
        if (is_array($value)) {
            $value = array_filter($value);
        }

        return $value;
    }

    /**
     * Format the value
     *
     * This filter is appied to the $value after it is loaded from the DB and
     * before it is returned to the template.
     *
     * @type    filter
     *
     * @param string[] $value The value which was loaded from the database.
     * @param integer $post_id The $post_id from which the value was loaded.
     * @param array $field The field array holding all the field options.
     *
     * @return array[]   The modified value.
     */
    public function format_value($value, $post_id, $field)
    {
        if (empty($value)) {
            return $value;
        }

        $value = \acf_get_array($value);

        if ($field['return_format'] == 'object') {
            foreach ($value as &$val) {
                $tax = \get_taxonomy($val);

                $val = ( $tax ? $tax : null );
            }
        }

        $value = array_filter($value);

        if (in_array($field['field_type'], [ 'select', 'radio' ])) {
            $value = array_shift($value);
        }

        return $value;
    }
}
