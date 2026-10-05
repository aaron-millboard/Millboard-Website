<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?= \Granola\Components\ProductVisualiser\trigger('tab'); ?>

    <div class="product-visualiser__overlay" data-visualiser-close hidden></div>

    <div
        class="product-visualiser__drawer"
        role="dialog"
        aria-modal="true"
        aria-label="<?= esc_attr__('See it on your property', 'granola'); ?>"
        tabindex="-1"
        hidden
    >
        <?php /* The visualiser has its own close button, so this one only appears if the embed has not loaded. */ ?>
        <button type="button" class="product-visualiser__close" data-visualiser-close hidden>
            <span class="screen-reader-text"><?= esc_html__('Close visualiser', 'granola'); ?></span>
            <?= \Granola\SVG::get('icons/cross.svg'); ?>
        </button>

        <?php /* The iframe is created on first open, so the page never loads the visualiser unprompted. */ ?>
        <div class="product-visualiser__body"></div>
    </div>
</div>
