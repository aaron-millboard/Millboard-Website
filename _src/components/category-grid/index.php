<?php
// The script rewrites these counts as the filters change, so the wording has to
// travel with the markup rather than being hardcoded in English in the JS.
$grid_strings = [
    'count_one' => \__('{n} product', 'granola'),
    'count_many' => \__('{n} products', 'granola'),
    'progress' => \__('Showing {n} of {total}', 'granola'),
    'status_one' => \__('{n} product matches', 'granola'),
    'status_many' => \__('{n} products match', 'granola'),
];
?>
<section
    <?= \Granola\Helpers::build_attributes($args['attributes']); ?>
    data-per-page="<?= \esc_attr((string) $args['per_page']); ?>"
    data-strings="<?= \esc_attr(\wp_json_encode($grid_strings)); ?>"
>
    <div class="category-grid__inner">
        <div class="category-grid__header">
            <div class="category-grid__titles nflm">
                <h2 class="category-grid__heading" id="<?= \esc_attr($args['uid']); ?>-heading">
                    <?= \esc_html($args['heading']); ?>
                </h2>

                <p class="category-grid__count" data-grid-count>
                    <?php
                    printf(
                        // translators: %s: number of products.
                        \esc_html(\_n('%s product', '%s products', count($args['product_ids']), 'granola')),
                        \esc_html(\number_format_i18n(count($args['product_ids'])))
                    );
                    ?>
                </p>
            </div>

            <?php if (!empty($args['show_sort'])) { ?>
                <div class="category-grid__sort" hidden data-grid-sort-wrap>
                    <label class="category-grid__sort-label" for="<?= \esc_attr($args['uid']); ?>-sort">
                        <?= \esc_html__('Sort', 'granola'); ?>
                    </label>
                    <select class="category-grid__sort-select" id="<?= \esc_attr($args['uid']); ?>-sort" data-grid-sort>
                        <option value="default"><?= \esc_html__('Name A to Z', 'granola'); ?></option>
                        <option value="price-asc"><?= \esc_html__('Price, low to high', 'granola'); ?></option>
                        <option value="price-desc"><?= \esc_html__('Price, high to low', 'granola'); ?></option>
                    </select>
                </div>
            <?php } ?>
        </div>

        <?php if (!empty($args['filter_groups'])) { ?>
            <div class="category-grid__filters" hidden data-grid-filters>
                <?php foreach ($args['filter_groups'] as $group) { ?>
                    <fieldset class="category-grid__filter-group">
                        <legend class="category-grid__filter-legend"><?= \esc_html($group['label']); ?></legend>

                        <?php foreach ($group['options'] as $option) { ?>
                            <button
                                type="button"
                                class="category-grid__chip"
                                aria-pressed="false"
                                data-filter-group="<?= \esc_attr($group['key']); ?>"
                                data-filter-value="<?= \esc_attr($option['slug']); ?>"
                            >
                                <?= \esc_html($option['label']); ?>
                                <span class="category-grid__chip-count">(<?= \esc_html((string) $option['count']); ?>)</span>
                            </button>
                        <?php } ?>
                    </fieldset>
                <?php } ?>

                <button type="button" class="category-grid__clear" hidden data-grid-clear>
                    <?= \esc_html__('Clear all', 'granola'); ?>
                </button>
            </div>
        <?php } ?>

        <?php
        // Every product is in the markup. The script hides what does not match
        // and what is past the first page, so with no JavaScript the whole
        // category is present and readable.
        ?>
        <ul class="category-grid__items" role="list" data-grid-items>
            <?php foreach ($args['product_ids'] as $product_id) { ?>
                <li class="category-grid__item">
                    <?= \Granola\Component::get('category-card', [
                        'product_id' => $product_id,
                        'sample_url' => $args['sample_url'],
                    ]); ?>
                </li>
            <?php } ?>
        </ul>

        <div class="category-grid__more" hidden data-grid-more-wrap>
            <p class="category-grid__progress" data-grid-progress></p>
            <button type="button" class="g-button g-button--secondary category-grid__more-button" data-grid-more>
                <?= \esc_html__('Load more', 'granola'); ?>
            </button>
        </div>

        <div class="category-grid__empty" hidden data-grid-empty>
            <h3 class="category-grid__empty-heading">
                <?= \esc_html($args['empty_heading'] ?: \__('Nothing matches that combination', 'granola')); ?>
            </h3>

            <?php if (!empty($args['empty_text'])) { ?>
                <p class="category-grid__empty-text"><?= \esc_html($args['empty_text']); ?></p>
            <?php } ?>

            <div class="category-grid__empty-actions">
                <button type="button" class="g-button g-button--primary" data-grid-clear>
                    <?= \esc_html__('Clear all filters', 'granola'); ?>
                </button>

                <?php if (!empty($args['sample_url'])) { ?>
                    <a class="g-button g-button--secondary" href="<?= \esc_url($args['sample_url']); ?>">
                        <?= \esc_html__('Order free samples', 'granola'); ?>
                    </a>
                <?php } ?>
            </div>
        </div>

        <p class="visually-hidden" role="status" aria-live="polite" data-grid-status></p>
    </div>
</section>
