<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="spec-table__inner">
        <?php if (!empty($args['preheading']) || !empty($args['heading']) || !empty($args['intro'])) { ?>
            <div class="spec-table__header">
                <span class="spec-table__rule" aria-hidden="true"></span>

                <?php if (!empty($args['preheading'])) { ?>
                    <p class="spec-table__preheading"><?= esc_html($args['preheading']); ?></p>
                <?php } ?>

                <?php if (!empty($args['heading'])) { ?>
                    <h2 class="spec-table__heading"><?= wp_kses_post($args['heading']); ?></h2>
                <?php } ?>

                <?php if (!empty($args['intro'])) { ?>
                    <p class="spec-table__intro"><?= esc_html($args['intro']); ?></p>
                <?php } ?>
            </div>
        <?php } ?>

        <?php // A scroll region, so a wide table scrolls inside the block instead of widening the page. ?>
        <div
            class="spec-table__scroll"
            role="region"
            tabindex="0"
            <?php if (!empty($args['heading'])) { ?>
                aria-label="<?= esc_attr(wp_strip_all_tags($args['heading'])); ?>"
            <?php } ?>
        >
            <table class="spec-table__table">
                <thead>
                    <tr>
                        <?php // A header cell is never left empty: a screen reader would announce a blank column. ?>
                        <th scope="col">
                            <?php if ($args['corner'] !== '') { ?>
                                <?= esc_html($args['corner']); ?>
                            <?php } else { ?>
                                <span class="visually-hidden"><?= esc_html__('Specification', 'granola'); ?></span>
                            <?php } ?>
                        </th>
                        <?php foreach ($args['columns'] as $label) { ?>
                            <th scope="col"><?= esc_html($label); ?></th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($args['rows'] as $row) { ?>
                        <tr>
                            <th scope="row"><?= esc_html($row['label']); ?></th>
                            <?php foreach ($row['cells'] as $cell) { ?>
                                <td>
                                    <?php if (!empty($cell['link'])) { ?>
                                        <a
                                            href="<?= esc_url($cell['link']['url']); ?>"
                                            <?php if (!empty($cell['link']['target'])) { ?>
                                                target="<?= esc_attr($cell['link']['target']); ?>" rel="noopener"
                                            <?php } ?>
                                        ><?= esc_html($cell['text'] !== '' ? $cell['text'] : $cell['link']['title']); ?></a>
                                    <?php } else { ?>
                                        <?= esc_html($cell['text']); ?>
                                    <?php } ?>
                                </td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
