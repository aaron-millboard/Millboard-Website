<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="trust-strip__inner">
        <ul class="trust-strip__items" role="list">
            <?php foreach ($args['items'] as $item) { ?>
                <li class="trust-strip__item">
                    <?php if ($item['type'] === 'rating') { ?>
                        <p class="trust-strip__rating">
                            <?php
                            // The stars are decoration. The score is read out in
                            // words below so it is never only a picture.
                            ?>
                            <span class="trust-strip__stars" aria-hidden="true">
                                <?php for ($star = 1; $star <= 5; $star++) { ?>
                                    <span class="trust-strip__star<?= $star <= $item['stars'] ? ' is-filled' : ''; ?>">&#9733;</span>
                                <?php } ?>
                            </span>

                            <span class="trust-strip__score"><?= \esc_html($item['rating_display']); ?></span>

                            <span class="visually-hidden">
                                <?php
                                printf(
                                    // translators: 1: review score. 2: the maximum score.
                                    \esc_html__('Rated %1$s out of %2$s', 'granola'),
                                    \esc_html($item['rating_display']),
                                    \esc_html(\number_format_i18n($item['out_of']))
                                );
                                ?>
                            </span>
                        </p>

                        <?php if (!empty($item['detail'])) { ?>
                            <p class="trust-strip__detail">
                                <?php if (!empty($item['url'])) { ?>
                                    <a href="<?= \esc_url($item['url']); ?>"><?= \esc_html($item['detail']); ?></a>
                                <?php } else { ?>
                                    <?= \esc_html($item['detail']); ?>
                                <?php } ?>
                            </p>
                        <?php } ?>
                    <?php } else { ?>
                        <p class="trust-strip__label">
                            <?php if (!empty($item['url'])) { ?>
                                <a href="<?= \esc_url($item['url']); ?>"><?= \esc_html($item['label']); ?></a>
                            <?php } else { ?>
                                <?= \esc_html($item['label']); ?>
                            <?php } ?>
                        </p>

                        <?php if (!empty($item['detail'])) { ?>
                            <p class="trust-strip__detail"><?= \esc_html($item['detail']); ?></p>
                        <?php } ?>
                    <?php } ?>
                </li>
            <?php } ?>
        </ul>
    </div>
</section>
