<?php
/**
 * Build accessibility attributes for a logo link.
 *
 * Logo links wrap an image only, so if that image has no alt text the link has
 * no accessible name (WCAG 2.4.4 / 4.1.2 — link-name). Provide a fallback name
 * ONLY when the image genuinely has no alt, so a proper alt added later is never
 * overridden.
 */
$logo_link_a11y_attributes = function ($item): array {
    $image = $item['image'] ?? [];
    $attachment_id = null;

    if (is_numeric($image)) {
        $attachment_id = (int) $image;
    } elseif (is_array($image)) {
        $attachment_id = $image['attachment_id'] ?? $image['id'] ?? $image['ID'] ?? null;
    }

    $existing_alt = $attachment_id
        ? trim((string) \get_post_meta($attachment_id, '_wp_attachment_image_alt', true))
        : '';

    // Image already has a meaningful alt — let it name the link.
    if ($existing_alt !== '') {
        return [];
    }

    $label = '';
    if (!empty($item['link']['title'])) {
        $label = $item['link']['title'];
    } elseif (!empty($item['link']['url'])) {
        $host = \parse_url($item['link']['url'], PHP_URL_HOST);
        $label = $host ? preg_replace('/^www\./', '', $host) : '';
    }

    return $label !== '' ? ['aria-label' => $label] : [];
};
?>
<?php if (!empty($args['items'])) { ?>
    <section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
        <div class="logo-grid__inner">
            <?php if (!empty($args['heading']) || !empty($args['subheading'])) { ?>
                <div class="logo-grid__header">
                    <?php if (!empty($args['heading'])) { ?>
                        <?= \Granola\Component::get('heading', [
                            'content' => $args['heading'],
                            'classes' => ['logo-grid__heading'],
                        ]); ?>
                    <?php } ?>

                    <?php if (!empty($args['subheading'])) { ?>
                        <div class="logo-grid__subheading">
                            <?= wp_kses_post($args['subheading']) ?>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>

            <div class="logo-grid__items-wrapper">

                <div class="logo-grid__items-prewrapper">

                    <div class="logo-grid__items">
                        <?php foreach ($args['items'] as $item) { ?>
                            <div class="logo-grid__item-wrapper">
                                <?php if (!empty($item['link'])) { ?>
                                    <?= \Granola\Component::get('link', array_merge($item['link'], [
                                        'classes' => ['logo-grid__item'],
                                        'content' => \Granola\Component::get('image', $item['image']),
                                        'content_filter' => false,
                                        'attributes' => $logo_link_a11y_attributes($item),
                                    ])); ?>
                                <?php } else { ?>
                                    <div class="logo-grid__item">
                                        <?= \Granola\Component::get('image', $item['image']); ?>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>

                    <?php
                    // Clone items for marquee layout only
                    if ($args['layout'] === 'marquee') {
                        ?>
                        <div class="logo-grid__items logo-grid__items--clone">
                        <?php foreach ($args['items'] as $index => $item) { ?>
                                <?php if (!empty($item['link'])) { ?>
                                    <?= \Granola\Component::get('link', array_merge($item['link'], [
                                        'classes' => ['logo-grid__item'],
                                        'content' => \Granola\Component::get('image', $item['image']),
                                        'content_filter' => false,
                                        'attributes' => $logo_link_a11y_attributes($item),
                                    ])); ?>
                                <?php } else { ?>
                                    <div class="logo-grid__item">
                                        <?= \Granola\Component::get('image', $item['image']); ?>
                                    </div>
                                <?php } ?>
                        <?php } ?>
                        </div>
                    <?php } ?>

                </div>

            </div>

        </div>
    </section>
<?php } ?>
