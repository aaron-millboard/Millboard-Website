<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="spec-credentials__inner">
        <div class="spec-credentials__row">
            <div class="spec-credentials__person">
                <span class="spec-credentials__avatar" aria-hidden="true">
                    <?php if (!empty($args['author_person']['portrait'])) { ?>
                        <?= \Granola\Component::get('image', $args['author_person']['portrait']); ?>
                    <?php } else { ?>
                        <?= esc_html($args['author_person']['initials']); ?>
                    <?php } ?>
                </span>

                <div class="spec-credentials__text">
                    <span class="spec-credentials__label"><?= esc_html__('Written by', 'granola'); ?></span>
                    <span class="spec-credentials__name"><?= esc_html($args['author_person']['name']); ?></span>

                    <?php if (!empty($args['author_person']['role'])) { ?>
                        <span class="spec-credentials__detail"><?= esc_html(sprintf(
                            // translators: %s: a person's job title.
                            __('%s, Millboard', 'granola'),
                            $args['author_person']['role']
                        )); ?></span>
                    <?php } ?>
                </div>
            </div>

            <?php if (!empty($args['reviewer_person'])) { ?>
                <?php // Drawn only when a reviewer and the day of the review are both on the block. ?>
                <div class="spec-credentials__text spec-credentials__text--reviewer">
                    <span class="spec-credentials__label"><?= esc_html__('Reviewed by', 'granola'); ?></span>
                    <span class="spec-credentials__name"><?= esc_html($args['reviewer_person']['role']
                        ? $args['reviewer_person']['name'] . ', ' . $args['reviewer_person']['role']
                        : $args['reviewer_person']['name']); ?></span>
                    <span class="spec-credentials__detail"><?= esc_html($args['review_line']); ?></span>
                </div>
            <?php } ?>

            <?php if (!empty($args['published'])) { ?>
                <div class="spec-credentials__text spec-credentials__text--dates">
                    <time class="spec-credentials__detail" datetime="<?= esc_attr($args['published_iso']); ?>"><?= esc_html($args['published']); ?></time>

                    <?php if (!empty($args['updated'])) { ?>
                        <time class="spec-credentials__detail" datetime="<?= esc_attr($args['updated_iso']); ?>"><?= esc_html($args['updated']); ?></time>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>

        <?php if (!empty($args['author_person']['bio'])) { ?>
            <p class="spec-credentials__bio"><?= esc_html($args['author_person']['bio']); ?></p>
        <?php } ?>
    </div>
</section>
