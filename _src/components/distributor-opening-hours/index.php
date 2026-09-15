<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="distributor-opening-hours__card">

        <h2 class="distributor-opening-hours__heading"><?= esc_html($args['heading']); ?></h2>

        <?php if (!empty($args['days'])) { ?>
            <dl class="distributor-opening-hours__week">
                <?php // Which row is today is decided by the browser, not here: this markup is
                // served from the full page cache, so a "today" worked out in PHP stays
                // stuck on whichever day the cache entry was written. Every row carries the
                // pill hidden and OpeningStatus.js shows the right one, which also keeps the
                // wording in PHP where Loco can translate it. ?>
                <?php foreach ($args['days'] as $row) { ?>
                    <div class="distributor-opening-hours__row" data-opening-day="<?= esc_attr($row['day']); ?>">
                        <dt class="distributor-opening-hours__day">
                            <?= esc_html($row['day']); ?>
                            <span class="distributor-opening-hours__pill" data-opening-today-pill hidden><?= esc_html__('Today', 'granola'); ?></span>
                        </dt>
                        <dd class="distributor-opening-hours__hours<?= $row['closed'] ? ' distributor-opening-hours__hours--closed' : ''; ?>">
                            <?= esc_html($row['hours']); ?>
                        </dd>
                    </div>
                <?php } ?>
            </dl>
        <?php } ?>

        <?php if (!empty($args['notes'])) { ?>
            <p class="distributor-opening-hours__notes">
                <svg class="distributor-opening-hours__notes-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M12 8v4M12 16h.01"></path>
                </svg>
                <?= esc_html($args['notes']); ?>
            </p>
        <?php } ?>

    </div>
</section>
