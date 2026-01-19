<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="case-study-details__inner">
        <div class="case-study-details__header">
            <?php if (!empty($args['heading'])) { ?>
                <?= \Granola\Component::get('heading', $args['heading']); ?>
            <?php } ?>

            <?php if (!empty($args['tags'])) { ?>
                <div class="case-study-tags__tags">
                    <?php foreach ($args['tags'] as $tag) { ?>
                        <?= \Granola\Component::get('element', $tag); ?>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>

        <?php if (!empty($args['details'])) { ?>
            <table class="case-study-details__details">
                <colgroup>
                    <col class="case-study-details__header-col"/>
                    <col />
                </colgroup>

                <tbody>
                    <?php foreach ($args['details'] as $item) { ?>
                    <tr>
                        <th>
                            <?= esc_html($item['detail_label']); ?>
                        </th>
                        <td>
                            <?= wp_kses_post($item['detail_content']); ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    </div>
</div>
