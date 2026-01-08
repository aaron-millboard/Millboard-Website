<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="header-search__inner">
        <?= \Granola\Component::get('button', $args['close_button']); ?>

        <?= \Granola\Component::get('search-form'); ?>

        <div class="header-search__label">
            <?= esc_html__('Search for products, inspiration, brochures, guides and help articles.', 'granola'); ?>
        </div>
    </div>
</div>
