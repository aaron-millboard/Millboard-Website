<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="list-specifications__inner">
        <div class="list-specifications__header">
            <?php if (!empty($args['preheading'])) { ?>
                <div class="list-specifications__preheading is-style-typestyle-h6">
                    <?= wp_kses_post($args['preheading']); ?>
                </div>
            <?php } ?>
            
            <?php if (!empty($args['heading'])) { ?>
                <h2 class="list-specifications__heading">
                    <?= wp_kses_post($args['heading']); ?>
                </h2>
            <?php } ?>
        </div>

        <?php if (!empty($args['specifications']) && is_array($args['specifications'])) { ?>
            <div class="list-specifications__specifications">
                <?php foreach ($args['specifications'] as $specification) { ?>
                    <div class="list-specifications__specification">
                        <?php if (!empty($specification['name'])) { ?>
                            <div class="list-specifications__specification-name">
                                <?= wp_kses_post($specification['name']); ?>
                            </div>
                        <?php } ?>
                        
                        <?php if (!empty($specification['value'])) { ?>
                            <div class="list-specifications__specification-value">
                                <?= wp_kses_post($specification['value']); ?>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>
