<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="intro-tabs__inner">
        <div class="intro-tabs__list" role="tablist">
            <?php foreach ($args['tabs'] as $tab) { ?>
                <button
                    type="button"
                    class="intro-tabs__tab<?= $tab['active'] ? ' is-active' : ''; ?>"
                    id="<?= \esc_attr($tab['tab_id']); ?>"
                    role="tab"
                    aria-controls="<?= \esc_attr($tab['panel_id']); ?>"
                    aria-selected="<?= $tab['active'] ? 'true' : 'false'; ?>"
                    tabindex="<?= $tab['active'] ? '0' : '-1'; ?>"
                >
                    <span class="intro-tabs__tab-label"><?= \esc_html($tab['label']); ?></span>
                </button>
            <?php } ?>
        </div>

        <div class="intro-tabs__panels">
            <?php foreach ($args['tabs'] as $tab) { ?>
                <div
                    class="intro-tabs__panel<?= $tab['active'] ? ' is-active' : ''; ?>"
                    id="<?= \esc_attr($tab['panel_id']); ?>"
                    role="tabpanel"
                    aria-labelledby="<?= \esc_attr($tab['tab_id']); ?>"
                    tabindex="0"
                >
                    <div class="intro-tabs__content">
                        <?= \wp_kses_post(\wpautop($tab['content'])); ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</section>
