<a href="#" class="product-calculator-cta">
    <?= \Granola\SVG::get('icons-custom/calculator.svg'); ?>
    <div class="product-calculator-cta--content">
        <?php if (!empty($args['heading'])) { ?>
            <div class="product-calculator-cta-heading">
                <?= esc_html($args['heading']); ?>
            </div>
        <?php } ?>

        <span class="product-calculator-cta-description">
            <?= esc_html__('How much do I need?', 'granola'); ?>
        </span>
    </div>
</a>