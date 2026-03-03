<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>

    <div class="product-calculator__header">
        <h3 class="product-calculator__header--title">
            <?= esc_html($args['title']); ?>
        </h3>

        <a href="#" class="product-calculator__header--close" aria-label="<?= esc_attr__('Close calculator', 'granola'); ?>">
            <?= esc_html__('Close', 'granola'); ?>
            <?= \Granola\SVG::get('icons/cross.svg'); ?>
        </a>
    </div>

    <div class="product-calculator__body">

        <!-- Section 2 - Wastage -->
        <div class="product-calculator__section product-calculator__section--wastage">
            <div class="product-calculator__section__content product-calculator__section__content--column">
                <div>
                    <?= esc_html($args['description']); ?>
                </div>
                <div class="product-calculator-wastage">
                    <label class="product-calculator-checkbox">
                        <input class="quantity-wastage-checkbox" type="checkbox" name="calculator_wastage">
                        <span><?= esc_html__('Add 10% for wastage', 'granola'); ?></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Section 1 - Measurements -->
        <div class="product-calculator__section product-calculator__section--units">
            <div class="product-calculator__section__content">
                <div class="product-calculator__unit-selection">
                    <div class="product-calculator--label">
                        <?= esc_html__('Select unit:', 'granola'); ?>
                    </div>

                    <div class="product-calculator__radios">
                        <div class="product-calculator--radio">
                            <input id="calculator_unit_meters" type="radio" name="calculator_unit" value="sqm" checked>
                            <label for="calculator_unit_meters"><?= __('Meters (m²)', 'granola'); ?></label>
                        </div>

                        <div class="product-calculator--radio">
                            <input id="calculator_unit_feet" type="radio" name="calculator_unit" value="sqft">
                            <label for="calculator_unit_feet"><?= __('Feet (ft²)', 'granola'); ?></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3 - Input dimensions -->
        <div class="product-calculator__section product-calculator__section--inputs">
            <div class="product-calculator__section__content">

                <div class="product-calculator__input-group">
                    <input id="calculator_length" class="product-calculator__input" type="number" step="0.01" value="1.00">
                    <label for="calculator_length" class="product-calculator__label">
                        <?= esc_html__('Length', 'granola'); ?>
                    </label>
                </div>

                <span class="product-calculator__operator">
                    <?=  __('x', 'granola'); ?>
                </span>

                <div class="product-calculator__input-group">
                    <input id="calculator_width" class="product-calculator__input" type="number" step="0.01" value="1.00">
                    <label for="calculator_width" class="product-calculator__label">
                        <?= esc_html__('Width', 'granola'); ?>
                    </label>
                </div>

                <span class="product-calculator__operator">
                    <?=  __('=', 'granola'); ?>
                </span>

                <div class="product-calculator__input-group">
                    <input id="calculator_area" class="product-calculator__input" type="number" step="0.01" value="1.00">
                    <label for="calculator_area" class="product-calculator__label">
                        <?= esc_html__('Area', 'granola'); ?>
                    </label>
                </div>

            </div>
        </div>

        <div class="product-calculator__section product-calculator__section--result">
            <div class="product-calculator__section__content">
                <span class="product-calculator-result-label"><?= esc_html__('Total area (Square meters)', 'granola'); ?></span>
                <span class="product-calculator-result-value" data-result="area">0.00</span>
            </div>
        </div>

        <div class="product-calculator__section product-calculator__section--boards">
            <div class="product-calculator__section__content">
                <span class="product-calculator-result-label"><?= esc_html__('Total Boards', 'granola'); ?></span>
                <span class="product-calculator-result-value" data-result="boards">0</span>
            </div>
        </div>

        <div class="product-calculator__section product-calculator__section--total">
            <div class="product-calculator__section__content">
                <span class="product-calculator-result-label"><?= esc_html__('Total Price', 'granola'); ?></span>
                <span class="product-calculator-result-value" data-result="price">£0.00</span>
            </div>
        </div>

        <div class="product-calculator__tax-notice">

            <?php if (!$args['tax_rate']) : ?>
                <?php
                if ($args['tax_included']) {
                    $tax_notice = esc_html($args['incl_tax_label']);
                } else {
                    $tax_notice = esc_html($args['excl_tax_label']);
                }
                    echo $tax_notice;
                ?>
            <?php else : ?>
                <div class="product-calculator-tax">
                    <label class="product-calculator-checkbox">
                        <input class="include-tax-checkbox" type="checkbox" name="calculator_tax" <?= $args['tax_included'] ? 'checked' : ''; ?>>
                        <span><?= esc_html($args['tax_toggle_label']); ?></span>
                    </label>
                </div>

            <?php endif; ?>

        </div>

        <div class="product-calculator__notice">
            <div class="product-calculator__notice--icon">
                <?= \Granola\SVG::get('icons/warning.svg'); ?>
            </div>
            <span class="product-calculator__notice--text">
                <?= esc_html($args['disclaimer']); ?>
            </span>
        </div>
    </div>
</div>
