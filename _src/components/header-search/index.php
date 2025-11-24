<form <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="header-search__inner">

        <?= \Granola\Component::get('button', $args['close_button']); ?>

        <div class="header-search__input-wrapper">

            <input
                id="<?= esc_attr($args['input_id']); ?>"
                class="header-search__input"
                type="text"
                name="s"
                aria-label="<?= esc_attr__('Search', 'granola'); ?>"
                placeholder="<?= esc_attr__('Search...', 'granola'); ?>"
                required
            >

            <?= \Granola\Component::get('button', $args['submit_button']); ?>

        </div>

        <div class="header-search__label">
            <?= esc_html__('Search for products, inspiration, brochures, guides and help articles.', 'granola'); ?>
        </div>

    </div>

</form>
