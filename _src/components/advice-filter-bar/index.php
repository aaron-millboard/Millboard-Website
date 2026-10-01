<?php

/**
 * Advice filter bar.
 *
 * Topic chips over the category's article grid. Pressing one hides the
 * guides outside that topic, and the featured guide with them, without
 * reloading. The chips are toggle buttons, so a screen reader hears which is
 * pressed, and the count beside them is announced as it changes.
 *
 * Without the script the chips do nothing and the grid shows every guide,
 * which is also what "All guides" shows, so nothing is lost.
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?> data-advice-filter>
    <div class="advice-filter-bar__inner">
        <div class="advice-filter-bar__header">
            <p class="advice-filter-bar__label" id="<?= esc_attr($args['label_id']); ?>"><?= esc_html($args['label']); ?></p>
            <p class="advice-filter-bar__status" aria-live="polite" data-advice-filter-status data-template="<?= esc_attr($args['status_template']); ?>"><?= esc_html($args['status']); ?></p>
        </div>

        <div class="advice-filter-bar__chips" role="group" aria-labelledby="<?= esc_attr($args['label_id']); ?>">
            <button type="button" class="advice-filter-bar__chip" aria-pressed="true" data-advice-topic="" data-advice-slug="">
                <?= esc_html($args['all_label']); ?>
            </button>

            <?php foreach ($args['chips'] as $chip) : ?>
                <button type="button" class="advice-filter-bar__chip" aria-pressed="false" data-advice-topic="<?= (int) $chip['id']; ?>" data-advice-slug="<?= esc_attr($chip['slug']); ?>">
                    <?= esc_html($chip['label']); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</section>
