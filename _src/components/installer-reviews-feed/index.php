<?php
$star = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 2.4l2.85 6.5 7.05.6-5.35 4.65 1.6 6.9L12 17.9 5.9 21.55l1.6-6.9L2.1 9.5l7.05-.6z"></path></svg>';

$stars_markup = function ($count) use ($star) {
    $count = (int) $count;
    if ($count < 1) {
        $count = 5;
    }
    if ($count > 5) {
        $count = 5;
    }
    return str_repeat($star, $count);
};

// Build the summary line: "4.9 from 63 reviews · Google & Trustpilot".
$summary = '';
if ($args['overall_rating'] !== '') {
    $summary = '<strong>' . esc_html($args['overall_rating']) . '</strong>';
    $parts = [];
    if (!empty($args['review_count'])) {
        /* translators: %s: number of reviews. */
        $parts[] = esc_html(sprintf(\__('from %s reviews', 'granola'), $args['review_count']));
    }
    if (!empty($args['sources_label'])) {
        $parts[] = esc_html($args['sources_label']);
    }
    if (!empty($parts)) {
        $summary .= ' ' . implode(' &middot; ', $parts);
    }
}

$link = $args['link'] ?? null;
?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="installer-reviews-feed__inner">

        <div class="installer-reviews-feed__head">
            <span class="installer-reviews-feed__rule" aria-hidden="true"></span>
            <?php if (!empty($args['heading'])) { ?>
                <h2 class="installer-reviews-feed__heading"><?= esc_html($args['heading']); ?></h2>
            <?php } ?>
            <?php if ($summary !== '') { ?>
                <div class="installer-reviews-feed__summary">
                    <span class="installer-reviews-feed__summary-stars" aria-hidden="true"><?= $stars_markup(5); ?></span>
                    <span class="installer-reviews-feed__summary-text"><?= $summary; ?></span>
                </div>
            <?php } ?>
        </div>

        <div class="installer-reviews-feed__grid">
            <?php foreach ($args['reviews'] as $review) { ?>
                <figure class="installer-reviews-feed__card">
                    <div class="installer-reviews-feed__card-head">
                        <span class="installer-reviews-feed__card-stars" aria-hidden="true"><?= $stars_markup($review['stars'] ?? 5); ?></span>
                        <?php if (!empty($review['source'])) { ?>
                            <span class="installer-reviews-feed__card-source"><?= esc_html($review['source']); ?></span>
                        <?php } ?>
                    </div>
                    <?php if (!empty($review['quote'])) { ?>
                        <blockquote class="installer-reviews-feed__card-quote"><?= esc_html($review['quote']); ?></blockquote>
                    <?php } ?>
                    <figcaption class="installer-reviews-feed__card-meta">
                        <?php if (!empty($review['name'])) { ?>
                            <span class="installer-reviews-feed__card-name"><?= esc_html($review['name']); ?></span>
                        <?php } ?>
                        <?php if (!empty($review['date'])) { ?>
                            <span class="installer-reviews-feed__card-date"><?= esc_html($review['date']); ?></span>
                        <?php } ?>
                    </figcaption>
                </figure>
            <?php } ?>
        </div>

        <?php if (!empty($link) && !empty($link['url'])) {
            $target = !empty($link['target']) ? ' target="' . esc_attr($link['target']) . '" rel="noopener"' : '';
            $text = !empty($link['title']) ? $link['title'] : \__('Read all reviews', 'granola');
            ?>
            <div class="installer-reviews-feed__footer">
                <a class="installer-reviews-feed__link" href="<?= esc_url($link['url']); ?>"<?= $target; ?>><span><?= esc_html($text); ?></span><svg class="installer-reviews-feed__arrow" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></a>
            </div>
        <?php } ?>

    </div>
</section>
