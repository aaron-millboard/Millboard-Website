<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="spec-cards__inner">
        <?php if (!empty($args['preheading']) || !empty($args['heading']) || !empty($args['intro'])) { ?>
            <div class="spec-cards__header">
                <?php if (!empty($args['preheading'])) { ?>
                    <div class="spec-cards__preheading is-style-typestyle-h6"><?= wp_kses_post($args['preheading']); ?></div>
                <?php } ?>

                <?php if (!empty($args['heading'])) { ?>
                    <h2 class="spec-cards__heading"><?= wp_kses_post($args['heading']); ?></h2>
                <?php } ?>

                <?php if (!empty($args['intro'])) { ?>
                    <p class="spec-cards__intro"><?= esc_html($args['intro']); ?></p>
                <?php } ?>
            </div>
        <?php } ?>

        <div class="spec-cards__cards">
            <?php foreach ($args['cards'] as $card) { ?>
                <div class="spec-cards__card">
                    <h3 class="spec-cards__card-title"><?= wp_kses_post($card['title']); ?></h3>

                    <?php if (!empty($card['image_data'])) { ?>
                        <div class="spec-cards__image img-fit">
                            <?= \Granola\Component::get('image', $card['image_data']); ?>
                        </div>
                    <?php } ?>

                    <?php if (!empty($card['description'])) { ?>
                        <div class="spec-cards__description"><?= wp_kses_post($card['description']); ?></div>
                    <?php } ?>

                    <?php if (!empty($card['facts'])) { ?>
                        <dl class="spec-cards__facts">
                            <?php foreach ($card['facts'] as $fact) { ?>
                                <div class="spec-cards__fact">
                                    <dt><?= esc_html($fact['label']); ?></dt>
                                    <dd><?= esc_html($fact['value']); ?></dd>
                                </div>
                            <?php } ?>
                        </dl>
                    <?php } ?>

                    <?php if (!empty($card['links'])) { ?>
                        <ul class="spec-cards__links" role="list">
                            <?php foreach ($card['links'] as $link) { ?>
                                <li>
                                    <a
                                        href="<?= esc_url($link['url']); ?>"
                                        <?php if (!empty($link['target'])) { ?>
                                            target="<?= esc_attr($link['target']); ?>" rel="noopener"
                                        <?php } ?>
                                    ><?= esc_html($link['title']); ?><span class="visually-hidden"> (<?= esc_html(wp_strip_all_tags($card['title'])); ?>)</span></a>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } ?>

                    <?php if (!empty($card['cta_data'])) { ?>
                        <div class="spec-cards__footer">
                            <?= \Granola\Component::get('link', $card['cta_data']); ?>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>
</section>
