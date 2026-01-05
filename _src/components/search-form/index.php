<form <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="search-form__input-wrapper">
        <input
            id="<?= esc_attr($args['input_id']); ?>"
            class="search-form__input"
            type="text"
            name="s"
            aria-label="<?= esc_attr__('Search', 'granola'); ?>"
            placeholder="<?= esc_attr__('Search...', 'granola'); ?>"
            required
        >

        <?= \Granola\Component::get('button', $args['submit_button']); ?>
    </div>
</form>
