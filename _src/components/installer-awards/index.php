<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="installer-awards__inner">

        <div class="installer-awards__head">
            <span class="installer-awards__rule" aria-hidden="true"></span>
            <?php if (!empty($args['heading'])) { ?>
                <h2 class="installer-awards__heading"><?= esc_html($args['heading']); ?></h2>
            <?php } ?>
        </div>

        <div class="installer-awards__grid">
            <?php foreach ($args['awards'] as $award) { ?>
                <div class="installer-awards__card">
                    <svg class="installer-awards__icon" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="6"></circle><path d="M8.2 13.4 7 22l5-3 5 3-1.2-8.6"></path></svg>
                    <?php if (!empty($award['year'])) { ?>
                        <span class="installer-awards__year"><?= esc_html($award['year']); ?></span>
                    <?php } ?>
                    <?php if (!empty($award['title'])) { ?>
                        <p class="installer-awards__title"><?= esc_html($award['title']); ?></p>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>

    </div>
</section>
