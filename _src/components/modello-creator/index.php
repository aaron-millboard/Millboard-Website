<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?> data-modello-creator="<?= \esc_attr(\wp_json_encode($args['config'])); ?>">
    <div class="modello-creator__inner">
        <div class="modello-creator__intro">
            <hr class="modello-creator__rule" aria-hidden="true">

            <h2 class="modello-creator__heading"><?= \esc_html($args['heading']); ?></h2>

            <div class="modello-creator__copy"><?= \wp_kses_post(\wpautop($args['intro'])); ?></div>
        </div>

        <div class="modello-creator__controls">
            <label class="modello-creator__field">
                <span class="modello-creator__label"><?= \esc_html_x('Collection', 'Modello pattern tool', 'granola'); ?></span>
                <select class="modello-creator__select" data-modello-collection>
                    <?php foreach ($args['collections'] as $value => $label) { ?>
                        <option value="<?= \esc_attr($value); ?>" <?= \selected($value, $args['default_collection'], false); ?>><?= \esc_html($label); ?></option>
                    <?php } ?>
                </select>
            </label>

            <label class="modello-creator__field">
                <span class="modello-creator__label"><?= \esc_html_x('Colour', 'Modello pattern tool', 'granola'); ?></span>
                <select class="modello-creator__select" data-modello-colour>
                    <?php foreach ($args['colours'] as $value => $label) { ?>
                        <option value="<?= \esc_attr($value); ?>" <?= \selected($value, $args['default_colour'], false); ?>><?= \esc_html($label); ?></option>
                    <?php } ?>
                </select>
            </label>
        </div>

        <p class="modello-creator__instruction"><?= \esc_html($args['instruction']); ?></p>

        <div
            class="modello-creator__board"
            role="group"
            aria-label="<?= \esc_attr($args['instruction']); ?>"
            data-modello-board
        >
            <?php for ($i = 0; $i < $args['rows']; $i++) { ?>
                <div
                    class="modello-creator__row"
                    role="button"
                    tabindex="<?= $i === 0 ? '0' : '-1'; ?>"
                    data-modello-row
                ></div>
            <?php } ?>
        </div>

        <div class="modello-creator__presets">
            <button class="modello-creator__nav modello-creator__nav--prev" type="button" data-modello-prev aria-label="<?= \esc_attr__('Previous patterns', 'granola'); ?>"></button>

            <div class="modello-creator__track" data-modello-track>
                <?php foreach ($args['preset_ids'] as $index => $id) { ?>
                    <button class="modello-creator__preset" type="button" data-modello-preset="<?= \esc_attr($index); ?>" data-pattern-id="<?= \esc_attr($id); ?>" aria-pressed="false">
                        <canvas class="modello-creator__thumb" width="360" height="240" aria-hidden="true"></canvas>
                        <span class="modello-creator__preset-label"></span>
                    </button>
                <?php } ?>
            </div>

            <button class="modello-creator__nav modello-creator__nav--next" type="button" data-modello-next aria-label="<?= \esc_attr__('Next patterns', 'granola'); ?>"></button>
        </div>

        <div class="modello-creator__actions">
            <button class="modello-creator__reset" type="button" data-modello-reset><?= \esc_html__('Reset boards', 'granola'); ?></button>
        </div>
    </div>
</section>
