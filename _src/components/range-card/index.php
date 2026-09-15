<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="range-card__inner">
        <?php if (!empty($args['heading']) || !empty($args['intro'])) { ?>
            <div class="range-card__header nflm">
                <hr class="range-card__rule" aria-hidden="true">

                <?php if (!empty($args['meta_prefix'])) { ?>
                    <p class="range-card__meta"><?= \esc_html($args['meta_prefix']); ?></p>
                <?php } ?>

                <?php if (!empty($args['heading'])) { ?>
                    <h2 class="range-card__heading"><?= \esc_html($args['heading']); ?></h2>
                <?php } ?>

                <?php if (!empty($args['intro'])) { ?>
                    <div class="range-card__intro"><?= \wp_kses_post(\wpautop($args['intro'])); ?></div>
                <?php } ?>
            </div>
        <?php } ?>

        <ul class="range-card__items" role="list">
            <?php foreach ($args['items'] as $item) { ?>
                <li class="range-card__item">
                    <article class="range-card__card">
                        <?php if (!empty($item['image_id'])) { ?>
                            <div class="range-card__media">
                                <?= \Granola\Component::get('image', [
                                    'attachment_id' => $item['image_id'],
                                    'size' => 'medium_large',
                                    'alt' => '',
                                    'classes' => ['range-card__image'],
                                ]); ?>
                            </div>
                        <?php } ?>

                        <div class="range-card__body">
                            <div class="range-card__text">
                                <h3 class="range-card__name">
                                    <a class="range-card__link" href="<?= \esc_url($item['url']); ?>">
                                        <?= \esc_html($item['name']); ?>
                                    </a>
                                </h3>

                                <?php if (!empty($item['line'])) { ?>
                                    <p class="range-card__line"><?= \esc_html(\wp_strip_all_tags($item['line'])); ?></p>
                                <?php } ?>
                            </div>

                            <?php if (!empty($item['chips'])) { ?>
                                <ul class="range-card__chips" role="list">
                                    <?php foreach ($item['chips'] as $chip) { ?>
                                        <li class="range-card__chip"><?= \esc_html($chip); ?></li>
                                    <?php } ?>
                                </ul>
                            <?php } ?>

                            <?php if (!empty($item['swatches'])) { ?>
                                <div class="range-card__swatches">
                                    <ul class="range-card__swatch-list" role="list">
                                        <?php foreach ($item['swatches'] as $swatch) { ?>
                                            <li class="range-card__swatch">
                                                <?= \Granola\Component::get('image', [
                                                    'attachment_id' => $swatch['image_id'],
                                                    'size' => 'thumbnail',
                                                    'alt' => '',
                                                    'classes' => ['range-card__swatch-image'],
                                                ]); ?>
                                            </li>
                                        <?php } ?>
                                    </ul>

                                    <?php if (!empty($item['colour_count'])) { ?>
                                        <p class="range-card__colour-note">
                                            <?php
                                            printf(
                                                // translators: %s: number of products in the collection.
                                                \esc_html(\_n('%s option', '%s options', $item['colour_count'], 'granola')),
                                                \esc_html(\number_format_i18n($item['colour_count']))
                                            );
                                            ?>
                                        </p>
                                    <?php } ?>
                                </div>
                            <?php } ?>

                            <div class="range-card__footer">
                                <?php if (!empty($item['from_price'])) { ?>
                                    <p class="range-card__price">
                                        <span class="range-card__price-label"><?= \esc_html__('From', 'granola'); ?></span>
                                        <span class="range-card__price-value"><?= \wp_kses_post($item['from_price']); ?></span>
                                    </p>
                                <?php } ?>

                                <span class="range-card__cta" aria-hidden="true"><?= \esc_html__('Explore', 'granola'); ?></span>
                            </div>
                        </div>
                    </article>
                </li>
            <?php } ?>
        </ul>
    </div>
</section>
