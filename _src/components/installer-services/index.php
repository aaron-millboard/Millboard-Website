<?php
$arrow = '<svg class="installer-services__arrow" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>';

$render_link = function ($link, $fallback) use ($arrow) {
    if (empty($link) || empty($link['url'])) {
        return '';
    }
    $target = !empty($link['target']) ? ' target="' . esc_attr($link['target']) . '" rel="noopener"' : '';
    $text = !empty($link['title']) ? $link['title'] : $fallback;
    return '<a class="installer-services__card-link" href="' . esc_url($link['url']) . '"' . $target . '><span>' . esc_html($text) . '</span>' . $arrow . '</a>';
};
?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="installer-services__inner">

        <div class="installer-services__intro">
            <span class="installer-services__rule" aria-hidden="true"></span>
            <?php if (!empty($args['heading'])) { ?>
                <h2 class="installer-services__heading"><?= esc_html($args['heading']); ?></h2>
            <?php } ?>
            <?php if (!empty($args['intro'])) { ?>
                <p class="installer-services__lead"><?= esc_html($args['intro']); ?></p>
            <?php } ?>
        </div>

        <div class="installer-services__grid">
            <?php foreach ($args['services'] as $service) { ?>
                <article class="installer-services__card">
                    <?php if (!empty($service['image'])) { ?>
                        <div class="installer-services__card-media">
                            <?= wp_get_attachment_image($service['image'], 'medium_large', false, ['class' => 'installer-services__card-image']); ?>
                        </div>
                    <?php } ?>
                    <div class="installer-services__card-body">
                        <?php if (!empty($service['title'])) { ?>
                            <h3 class="installer-services__card-title"><?= esc_html($service['title']); ?></h3>
                        <?php } ?>
                        <?php if (!empty($service['description'])) { ?>
                            <p class="installer-services__card-text"><?= esc_html($service['description']); ?></p>
                        <?php } ?>
                        <?= $render_link($service['link'] ?? null, \__('Learn more', 'granola')); ?>
                    </div>
                </article>
            <?php } ?>

            <?php if (!empty($args['has_cta'])) { ?>
                <article class="installer-services__cta installer-services__cta--<?= esc_attr($args['cta_style']); ?>">
                    <h3 class="installer-services__cta-title"><?= esc_html($args['cta_heading']); ?></h3>
                    <?php if (!empty($args['cta_text'])) { ?>
                        <p class="installer-services__cta-text"><?= esc_html($args['cta_text']); ?></p>
                    <?php } ?>
                    <?= $render_link($args['cta_link'] ?? null, \__('Get in touch', 'granola')); ?>
                </article>
            <?php } ?>
        </div>

    </div>
</section>
