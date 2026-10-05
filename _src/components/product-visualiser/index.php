<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?= \Granola\Components\ProductVisualiser\trigger('tab'); ?>

    <div class="product-visualiser__overlay" data-visualiser-close hidden></div>

    <div
        class="product-visualiser__drawer"
        role="dialog"
        aria-modal="true"
        aria-labelledby="product-visualiser-title"
        tabindex="-1"
        hidden
    >
        <div class="product-visualiser__header">
            <span class="product-visualiser__title" id="product-visualiser-title">
                <?= esc_html__('See it on your property', 'granola'); ?>
            </span>
            <button type="button" class="product-visualiser__close" data-visualiser-close>
                <span class="screen-reader-text"><?= esc_html__('Close visualiser', 'granola'); ?></span>
                <?= \Granola\SVG::get('icons/cross.svg'); ?>
            </button>
        </div>

        <?php /* The iframe is created on first open, so the page never loads the visualiser unprompted. */ ?>
        <div class="product-visualiser__body"></div>
    </div>
</div>
