<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="downloads__inner">
        <?php if (!empty($args['preheading']) || !empty($args['heading']) || !empty($args['cta'])) { ?>
            <div class="downloads__header">
                <div class="downloads__header-content">
                    <?php if (!empty($args['preheading'])) { ?>
                        <div class="downloads__preheading is-style-typestyle-h6"><?= wp_kses_post($args['preheading']); ?></div>
                    <?php } ?>

                    <?php if (!empty($args['heading'])) { ?>
                        <h2 class="downloads__heading"><?= wp_kses_post($args['heading']); ?></h2>
                    <?php } ?>
                </div>

                <?php if (!empty($args['cta'])) { ?>
                    <?= \Granola\Component::get('link', $args['cta']); ?>
                <?php } ?>
            </div>
        <?php } ?>

        <?php if (!empty($args['tabs'])) { ?>
            <?php // Hidden until the script runs: with no JavaScript every document is listed and there is nothing to filter. ?>
            <div class="spec-downloads__tabs" role="group" aria-label="<?= esc_attr__('Filter documents by type', 'granola'); ?>" hidden>
                <button type="button" class="g-button g-button--small g-button--solid" data-filter="all" aria-pressed="true"><?= esc_html__('All', 'granola'); ?></button>
                <?php foreach ($args['tabs'] as $slug => $label) { ?>
                    <button type="button" class="g-button g-button--small" data-filter="<?= esc_attr($slug); ?>" aria-pressed="false"><?= esc_html($label); ?></button>
                <?php } ?>
            </div>
        <?php } ?>

        <?php // Told to screen readers when a filter is chosen, since the rows change without the page moving. ?>
        <p class="visually-hidden" role="status" aria-live="polite" data-filter-status></p>

        <div class="spec-downloads__files">
            <?php foreach ($args['rows'] as $row) { ?>
                <div class="spec-downloads__file" data-type="<?= esc_attr($row['type']); ?>">
                    <span class="spec-downloads__type"><?= esc_html($row['type_label']); ?></span>
                    <span class="spec-downloads__title"><?= esc_html($row['title']); ?></span>
                    <span class="spec-downloads__meta"><?= esc_html($row['meta']); ?></span>
                    <span class="spec-downloads__actions">
                        <?php foreach ($row['actions'] as $action) { ?>
                            <?php $action['content'] = $action['icon'] . $action['content']; ?>
                            <?= \Granola\Component::get('link', $action); ?>
                        <?php } ?>
                    </span>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
