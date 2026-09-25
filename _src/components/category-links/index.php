<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="category-links__inner">
        <div class="category-links__header nflm">
            <hr class="category-links__rule" aria-hidden="true">

            <?php if (!empty($args['meta_prefix'])) { ?>
                <p class="category-links__meta"><?= \esc_html($args['meta_prefix']); ?></p>
            <?php } ?>

            <?php if (!empty($args['heading'])) { ?>
                <h2 class="category-links__heading"><?= \esc_html($args['heading']); ?></h2>
            <?php } ?>
        </div>

        <ul class="category-links__items" role="list">
            <?php foreach ($args['items'] as $item) { ?>
                <li class="category-links__item">
                    <a
                        class="category-links__link"
                        href="<?= \esc_url($item['url']); ?>"
                        <?php if (!empty($item['target'])) { ?>
                            target="<?= \esc_attr($item['target']); ?>" rel="noopener"
                        <?php } ?>
                    >
                        <span class="category-links__label"><?= \esc_html($item['label']); ?></span>

                        <?php if (!empty($item['detail'])) { ?>
                            <span class="category-links__detail"><?= \esc_html($item['detail']); ?></span>
                        <?php } ?>
                    </a>
                </li>
            <?php } ?>
        </ul>
    </div>
</section>
