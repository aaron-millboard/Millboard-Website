<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="list-values__inner">
        <?php if (!empty($args['preheading']) || !empty($args['heading'])) { ?>
            <div class="list-values__header">
                <?php if (!empty($args['preheading'])) { ?>
                    <div class="list-values__preheading is-style-typestyle-h6">
                        <?= wp_kses_post($args['preheading']); ?>
                    </div>
                <?php } ?>
                
                <?php if (!empty($args['heading'])) { ?>
                    <h2 class="list-values__heading">
                        <?= wp_kses_post($args['heading']); ?>
                    </h2>
                <?php } ?>
            </div>
        <?php } ?>

        <?php if (!empty($args['values']) && is_array($args['values'])) { ?>
            <div class="list-values__values">
                <?php foreach ($args['values'] as $value) { ?>
                    <div class="list-values__value">
                        <?php if (!empty($value['icon'])) { ?>
                            <div class="list-values__value-icon">
                                <?= $value['icon']; ?>
                            </div>
                        <?php } ?>
                        
                        <div class="list-values__value-content">
                            <?php if (!empty($value['heading'])) { ?>
                                <h3 class="list-values__value-heading">
                                    <?= wp_kses_post($value['heading']); ?>
                                </h3>
                            <?php } ?>
                            
                            <?php if (!empty($value['description'])) { ?>
                                <div class="list-values__value-description">
                                    <?= wp_kses_post($value['description']); ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>
