<label <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <input id="<?= esc_attr($args['id']); ?>-input" type="checkbox" name="<?= esc_attr($args['name']); ?>" value="<?= esc_attr($args['value']); ?>" />

    <span class="toggle-field__indicator" hidden>
        <?= \Granola\SVG::get('check.svg'); ?>
        <?= \Granola\SVG::get('cross.svg'); ?>
    </span>

    <span class="toggle-field__label">
        <?= wp_kses_post($args['label']); ?>
    </span>
</label>
