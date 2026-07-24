<?php
$link = $args['link'] ?? null;
$link_html = '';
if (!empty($link) && !empty($link['url'])) {
    $target = !empty($link['target']) ? ' target="' . esc_attr($link['target']) . '" rel="noopener"' : '';
    $text = !empty($link['title']) ? $link['title'] : \__('Order samples', 'granola');
    $link_html = '<a class="installer-ranges__link" href="' . esc_url($link['url']) . '"' . $target . '><span>' . esc_html($text) . '</span><svg class="installer-ranges__arrow" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></a>';
}
?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="installer-ranges__inner">

        <div class="installer-ranges__head">
            <div class="installer-ranges__heading-wrap">
                <span class="installer-ranges__rule" aria-hidden="true"></span>
                <?php if (!empty($args['heading'])) { ?>
                    <h2 class="installer-ranges__heading"><?= esc_html($args['heading']); ?></h2>
                <?php } ?>
            </div>
            <?= $link_html; ?>
        </div>

        <div class="installer-ranges__grid">
            <?php foreach ($args['ranges'] as $range) { ?>
                <div class="installer-ranges__card">
                    <?php if (!empty($range['image'])) { ?>
                        <div class="installer-ranges__card-media">
                            <?= wp_get_attachment_image($range['image'], 'medium', false, ['class' => 'installer-ranges__card-image']); ?>
                        </div>
                    <?php } ?>
                    <div class="installer-ranges__card-body">
                        <?php if (!empty($range['name'])) { ?>
                            <span class="installer-ranges__card-name"><?= esc_html($range['name']); ?></span>
                        <?php } ?>
                        <?php if (!empty($range['category'])) { ?>
                            <span class="installer-ranges__card-category"><?= esc_html($range['category']); ?></span>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>

    </div>
</section>
