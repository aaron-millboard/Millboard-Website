<?php
$link = $args['link'] ?? null;
$link_html = '';
if (!empty($link) && !empty($link['url'])) {
    $target = !empty($link['target']) ? ' target="' . esc_attr($link['target']) . '" rel="noopener"' : '';
    $text = !empty($link['title']) ? $link['title'] : \__('View full portfolio', 'granola');
    $link_html = '<a class="installer-projects__link" href="' . esc_url($link['url']) . '"' . $target . '><span>' . esc_html($text) . '</span><svg class="installer-projects__arrow" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></a>';
}

// Builds a full-tile "stretched" cover link, so the whole tile is clickable.
$cover_link = function ($lnk, $label) {
    if (empty($lnk) || empty($lnk['url'])) {
        return '';
    }
    $target = !empty($lnk['target']) ? ' target="' . esc_attr($lnk['target']) . '" rel="noopener"' : '';
    return '<a class="installer-projects__cover-link" href="' . esc_url($lnk['url']) . '"' . $target . ' aria-label="' . esc_attr($label) . '"></a>';
};

$featured_is_link = !empty($args['featured_link']) && !empty($args['featured_link']['url']);
?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="installer-projects__inner">

        <div class="installer-projects__head">
            <div class="installer-projects__heading-wrap">
                <span class="installer-projects__rule" aria-hidden="true"></span>
                <?php if (!empty($args['heading'])) { ?>
                    <h2 class="installer-projects__heading"><?= esc_html($args['heading']); ?></h2>
                <?php } ?>
            </div>
            <?= $link_html; ?>
        </div>

        <?php if (!empty($args['has_featured'])) { ?>
            <figure class="installer-projects__featured<?= $featured_is_link ? ' installer-projects__featured--linked' : ''; ?>">
                <?= wp_get_attachment_image($args['featured_image'], 'large', false, ['class' => 'installer-projects__featured-image']); ?>
                <?= $cover_link($args['featured_link'] ?? null, $args['featured_title'] ?: $args['featured_label']); ?>
                <figcaption class="installer-projects__caption installer-projects__caption--featured">
                    <?php if (!empty($args['featured_label'])) { ?>
                        <span class="installer-projects__eyebrow"><?= esc_html($args['featured_label']); ?></span>
                    <?php } ?>
                    <?php if (!empty($args['featured_title'])) { ?>
                        <span class="installer-projects__featured-title"><?= esc_html($args['featured_title']); ?></span>
                    <?php } ?>
                    <?php if (!empty($args['featured_subtitle'])) { ?>
                        <span class="installer-projects__featured-subtitle"><?= esc_html($args['featured_subtitle']); ?></span>
                    <?php } ?>
                </figcaption>
            </figure>
        <?php } ?>

        <?php if (!empty($args['projects'])) { ?>
            <div class="installer-projects__grid">
                <?php foreach ($args['projects'] as $project) {
                    $tile_link = $project['link'] ?? null;
                    $tile_is_link = !empty($tile_link) && !empty($tile_link['url']);
                    ?>
                    <figure class="installer-projects__tile<?= $tile_is_link ? ' installer-projects__tile--linked' : ''; ?>">
                        <?php if (!empty($project['image'])) { ?>
                            <?= wp_get_attachment_image($project['image'], 'medium_large', false, ['class' => 'installer-projects__tile-image']); ?>
                        <?php } ?>
                        <?= $cover_link($tile_link, $project['title'] ?? ''); ?>
                        <?php if (!empty($project['title']) || !empty($project['subtitle'])) { ?>
                            <figcaption class="installer-projects__caption">
                                <?php if (!empty($project['title'])) { ?>
                                    <span class="installer-projects__tile-title"><?= esc_html($project['title']); ?></span>
                                <?php } ?>
                                <?php if (!empty($project['subtitle'])) { ?>
                                    <span class="installer-projects__tile-subtitle"><?= esc_html($project['subtitle']); ?></span>
                                <?php } ?>
                            </figcaption>
                        <?php } ?>
                    </figure>
                <?php } ?>
            </div>
        <?php } ?>

    </div>
</section>
