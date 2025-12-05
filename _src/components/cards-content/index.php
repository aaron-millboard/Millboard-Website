<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="cards-content__inner">
        <div class="cards-content__header">
            <div class="cards-content__header-content">
                <?php if (!empty($args['preheading'])) { ?>
                    <div class="cards-content__preheading is-style-typestyle-h6">
                        <?= wp_kses_post($args['preheading']); ?>
                    </div>
                <?php } ?>
                
                <?php if (!empty($args['heading'])) { ?>
                    <h2 class="cards-content__heading">
                        <?= wp_kses_post($args['heading']); ?>
                    </h2>
                <?php } ?>
            </div>

            <?php if (!empty($args['header_cta_data'])) { ?>
                <?= \Granola\Component::get('link', $args['header_cta_data']); ?>
            <?php } ?>
        </div>

        <?php if (!empty($args['cards']) && is_array($args['cards'])) { ?>
            <div class="cards-content__cards">
                <?php foreach ($args['cards'] as $card) { ?>
                    <div class="cards-content__card">
                        <?php if (!empty($card['title'])) { ?>
                            <h3 class="cards-content__card-title">
                                <?= wp_kses_post($card['title']); ?>
                            </h3>
                        <?php } ?>

                        <div class="cards-content__card-content">
                            <?php if (!empty($card['image_data'])) { ?>
                                <div class="cards-content__card-image-wrapper">
                                    <div class="cards-content__card-image-wrapper--placeholder">
                                        <?= \Granola\Component::get('image', $card['image_data']); ?>
                                    </div>
                                </div>
                            <?php } ?>

                            <?php if (!empty($card['description'])) { ?>
                                <div class="cards-content__card-description">
                                    <?= wp_kses_post($card['description']); ?>
                                </div>
                            <?php } ?>
                        </div>

                        <div class="cards-content__card-footer">
                            <?php if (!empty($card['cta_message'])) { ?>
                                <div class="cards-content__card-footer--message">
                                    <?= wp_kses_post($card['cta_message']); ?>
                                </div>
                            <?php } ?>

                            <?php if (!empty($card['cta_data'])) { ?>
                                <div class="cards-content__card-footer--cta">
                                    <?= \Granola\Component::get('link', $card['cta_data']); ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>
