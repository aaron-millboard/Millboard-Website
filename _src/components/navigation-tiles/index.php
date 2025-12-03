<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="navigation-tiles__inner">
        <?php if (!empty($args['preheading']) || !empty($args['heading'])) { ?>
            <div class="navigation-tiles__header">
                <?php if (!empty($args['preheading'])) { ?>
                    <div class="navigation-tiles__preheading is-style-typestyle-h6">
                        <?= wp_kses_post($args['preheading']); ?>
                    </div>
                <?php } ?>
                
                <?php if (!empty($args['heading'])) { ?>
                    <h2 class="navigation-tiles__heading">
                        <?= wp_kses_post($args['heading']); ?>
                    </h2>
                <?php } ?>
            </div>
        <?php } ?>

        <?php if (!empty($args['tiles']) && is_array($args['tiles'])) { ?>
            <div class="navigation-tiles__tiles">
                <?php foreach ($args['tiles'] as $tile) { ?>
                    <div class="navigation-tiles__tile">
                        <?php if (!empty($tile['title'])) { ?>
                            <h3 class="navigation-tiles__tile-title">
                                <?= wp_kses_post($tile['title']); ?>
                            </h3>
                        <?php } ?>
                        
                        <?php if (!empty($tile['description'])) { ?>
                            <div class="navigation-tiles__tile-description">
                                <?= wp_kses_post($tile['description']); ?>
                            </div>
                        <?php } ?>
                        
                        <?php if (!empty($tile['cta_data'])) { ?>
                            <?= \Granola\Component::get('link', $tile['cta_data']); ?>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>
