<?php
$star = '<svg class="installer-reviews-summary__star" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 2.4l2.85 6.5 7.05.6-5.35 4.65 1.6 6.9L12 17.9 5.9 21.55l1.6-6.9L2.1 9.5l7.05-.6z"></path></svg>';
?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="installer-reviews-summary__inner">

        <div class="installer-reviews-summary__score">
            <?php if ($args['overall_rating'] !== '') { ?>
                <span class="installer-reviews-summary__rating"><?= esc_html($args['overall_rating']); ?></span>
            <?php } ?>
            <div class="installer-reviews-summary__score-body">
                <span class="installer-reviews-summary__stars" aria-hidden="true"><?php echo str_repeat($star, 5); ?></span>
                <?php if (!empty($args['review_count'])) { ?>
                    <p class="installer-reviews-summary__summary">
                        <?php
                        /* translators: 1: review count, 2: review sources, e.g. "Google & Trustpilot". */
                        printf(
                            wp_kses(__('Rated by <strong>%1$s verified clients</strong> across %2$s', 'granola'), ['strong' => []]),
                            esc_html($args['review_count']),
                            esc_html($args['sources_label'])
                        );
                        ?>
                    </p>
                <?php } ?>
            </div>
        </div>

        <?php if (!empty($args['sources'])) { ?>
            <div class="installer-reviews-summary__sources">
                <?php foreach ($args['sources'] as $source) {
                    $accent = ($source['accent'] ?? '') === 'apple' ? 'apple' : 'olivegreen';
                    ?>
                    <div class="installer-reviews-summary__source">
                        <span class="installer-reviews-summary__source-name"><?= esc_html($source['name']); ?></span>
                        <?php if (!empty($source['score'])) { ?>
                            <span class="installer-reviews-summary__source-score"><?= esc_html($source['score']); ?></span>
                        <?php } ?>
                        <svg class="installer-reviews-summary__source-star installer-reviews-summary__source-star--<?= esc_attr($accent); ?>" width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 2.4l2.85 6.5 7.05.6-5.35 4.65 1.6 6.9L12 17.9 5.9 21.55l1.6-6.9L2.1 9.5l7.05-.6z"></path></svg>
                        <?php if (!empty($source['count'])) { ?>
                            <span class="installer-reviews-summary__source-count">· <?= esc_html($source['count']); ?></span>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>

    </div>
</section>
