<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="spec-references__inner">
        <?php if (!empty($args['preheading']) || !empty($args['heading']) || !empty($args['intro'])) { ?>
            <div class="spec-references__header">
                <?php if (!empty($args['preheading'])) { ?>
                    <div class="spec-references__preheading is-style-typestyle-h6"><?= wp_kses_post($args['preheading']); ?></div>
                <?php } ?>

                <?php if (!empty($args['heading'])) { ?>
                    <h2 class="spec-references__heading"><?= wp_kses_post($args['heading']); ?></h2>
                <?php } ?>

                <?php if (!empty($args['intro'])) { ?>
                    <p class="spec-references__intro"><?= esc_html($args['intro']); ?></p>
                <?php } ?>
            </div>
        <?php } ?>

        <ul class="spec-references__list" role="list">
            <?php foreach ($args['items'] as $item) { ?>
                <li class="spec-references__item">
                    <a class="spec-references__link" href="<?= esc_url($item['url']); ?>" target="_blank" rel="noopener noreferrer">
                        <?= esc_html($item['title']); ?><span class="visually-hidden"> (<?= esc_html__('opens in a new tab', 'granola'); ?>)</span>
                    </a>

                    <?php if ($item['source'] !== '') { ?>
                        <span class="spec-references__source"><?= esc_html($item['source']); ?></span>
                    <?php } ?>

                    <?php if ($item['description'] !== '') { ?>
                        <p class="spec-references__description"><?= esc_html($item['description']); ?></p>
                    <?php } ?>
                </li>
            <?php } ?>
        </ul>
    </div>
</section>
