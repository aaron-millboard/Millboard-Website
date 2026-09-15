<?php
// Filter values travel as data attributes so the grid can filter and sort what
// is already rendered, rather than fetching a second time.
$filter_attributes = '';

foreach ($args['filters'] as $group => $values) {
    $filter_attributes .= sprintf(
        ' data-filter-%s="%s"',
        \esc_attr($group),
        \esc_attr(implode(' ', $values))
    );
}
?>
<article
    class="<?= \esc_attr(implode(' ', $args['classes'])); ?>"
    data-sort-name="<?= \esc_attr($args['name']); ?>"
    <?php if ($args['sort_price'] !== null) { ?>
        data-sort-price="<?= \esc_attr((string) $args['sort_price']); ?>"
    <?php } ?>
    <?= $filter_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
>
    <div class="category-card__media">
        <?php if (!empty($args['image_id'])) { ?>
            <?= \Granola\Component::get('image', [
                'attachment_id' => $args['image_id'],
                'size' => 'medium_large',
                'alt' => '',
                'classes' => ['category-card__image'],
            ]); ?>
        <?php } ?>

        <?php if (!empty($args['flag'])) { ?>
            <span class="category-card__flag"><?= \esc_html($args['flag']); ?></span>
        <?php } ?>
    </div>

    <div class="category-card__body">
        <?php if (!empty($args['range'])) { ?>
            <p class="category-card__range"><?= \esc_html($args['range']); ?></p>
        <?php } ?>

        <h3 class="category-card__name">
            <a class="category-card__link" href="<?= \esc_url($args['url']); ?>">
                <?= \esc_html($args['name']); ?>
            </a>
        </h3>

        <?php if (!empty($args['spec'])) { ?>
            <p class="category-card__spec"><?= \esc_html($args['spec']); ?></p>
        <?php } ?>

        <div class="category-card__footer">
            <?php if (!empty($args['price'])) { ?>
                <p class="category-card__price">
                    <span class="category-card__price-label"><?= \esc_html__('From', 'granola'); ?></span>
                    <span class="category-card__price-value"><?= \wp_kses_post($args['price']); ?></span>
                </p>
                <p class="category-card__price-note"><?= \esc_html__('Per board, inc VAT', 'granola'); ?></p>
            <?php } ?>

            <div class="category-card__actions">
                <span class="category-card__action" aria-hidden="true"><?= \esc_html__('View board', 'granola'); ?></span>

                <?php if (!empty($args['sample_url'])) { ?>
                    <a class="category-card__sample" href="<?= \esc_url($args['sample_url']); ?>">
                        <?= \esc_html__('Free sample', 'granola'); ?>
                        <span class="visually-hidden"><?= \esc_html(' ' . $args['name']); ?></span>
                    </a>
                <?php } ?>
            </div>
        </div>
    </div>
</article>
