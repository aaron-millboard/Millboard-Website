<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="comparison-table__inner">
        <div class="comparison-table__header nflm">
            <hr class="comparison-table__rule" aria-hidden="true">

            <?php if (!empty($args['meta_prefix'])) { ?>
                <p class="comparison-table__meta"><?= \esc_html($args['meta_prefix']); ?></p>
            <?php } ?>

            <?php if (!empty($args['heading'])) { ?>
                <h2 class="comparison-table__heading" id="<?= \esc_attr($args['uid']); ?>-heading">
                    <?= \esc_html($args['heading']); ?>
                </h2>
            <?php } ?>

            <?php if (!empty($args['intro'])) { ?>
                <div class="comparison-table__intro"><?= \wp_kses_post(\wpautop($args['intro'])); ?></div>
            <?php } ?>
        </div>

        <?php if (!empty($args['rows']) && !empty($args['columns'])) { ?>
            <?php
            // Keyboard users need to be able to scroll a wide table, which means
            // the scroll container has to be focusable and named.
            ?>
            <div
                class="comparison-table__scroll"
                role="region"
                tabindex="0"
                <?php if (!empty($args['heading'])) { ?>
                    aria-labelledby="<?= \esc_attr($args['uid']); ?>-heading"
                <?php } else { ?>
                    aria-label="<?= \esc_attr__('Comparison table', 'granola'); ?>"
                <?php } ?>
            >
                <table class="comparison-table__table">
                    <thead>
                        <tr>
                            <td class="comparison-table__corner"></td>
                            <?php foreach ($args['columns'] as $column) { ?>
                                <th scope="col"><?= \esc_html($column); ?></th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($args['rows'] as $row) { ?>
                            <tr>
                                <th scope="row"><?= \esc_html($row['label']); ?></th>
                                <?php foreach ($row['cells'] as $cell) { ?>
                                    <td><?= \esc_html($cell); ?></td>
                                <?php } ?>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>

        <?php if (!empty($args['footnote'])) { ?>
            <p class="comparison-table__footnote"><?= \wp_kses_post($args['footnote']); ?></p>
        <?php } ?>

        <?php if (!empty($args['best_for'])) { ?>
            <div class="comparison-table__best-for">
                <?php if (!empty($args['best_for_heading'])) { ?>
                    <h3 class="comparison-table__best-for-heading"><?= \esc_html($args['best_for_heading']); ?></h3>
                <?php } ?>

                <ul class="comparison-table__needs" role="list">
                    <?php foreach ($args['best_for'] as $item) { ?>
                        <li class="comparison-table__need">
                            <p class="comparison-table__need-label">
                                <?php
                                printf(
                                    // translators: %s: a customer need, for example "slip resistance".
                                    \esc_html__('Best for %s', 'granola'),
                                    \esc_html($item['need'])
                                );
                                ?>
                            </p>

                            <p class="comparison-table__need-range">
                                <?php if (!empty($item['url'])) { ?>
                                    <a href="<?= \esc_url($item['url']); ?>"><?= \esc_html($item['range']); ?></a>
                                <?php } else { ?>
                                    <?= \esc_html($item['range']); ?>
                                <?php } ?>
                            </p>

                            <?php if (!empty($item['why'])) { ?>
                                <p class="comparison-table__need-why"><?= \esc_html($item['why']); ?></p>
                            <?php } ?>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>
    </div>
</section>
