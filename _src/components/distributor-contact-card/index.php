<?php

/**
 * Inline icons. Sized by class, never by the SVG alone, because the theme resets
 * `svg { width: 100% }` and an unsized icon fills its button.
 */
$icon = static function (string $name, string $class): void {
    if ($name === '') {
        return;
    }

    $paths = [
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.6a2 2 0 0 1-.5 2.1L8 9.6a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.8.3 1.7.5 2.6.6a2 2 0 0 1 1.7 2Z"></path>',
        'email' => '<rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path>',
        'external' => '<path d="M7 17 17 7M8 7h9v9"></path>',
    ];

    if (!isset($paths[$name])) {
        return;
    }

    printf(
        '<svg class="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>',
        esc_attr($class),
        $paths[$name]
    );
};

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="distributor-contact-card__card">

        <?php if (!empty($args['champion'])) { ?>
            <div class="distributor-contact-card__person">

                <?php if (!empty($args['champion']['photo'])) { ?>
                    <div class="distributor-contact-card__photo">
                        <?= \Granola\Component::get('image', [
                            'attachment_id' => $args['champion']['photo'],
                            'classes' => ['distributor-contact-card__photo-image'],
                            'size' => 'medium',
                            'alt' => $args['champion']['name'],
                        ]); ?>
                    </div>
                <?php } ?>

                <div class="distributor-contact-card__identity">
                    <p class="distributor-contact-card__eyebrow"><?= esc_html($args['heading']); ?></p>
                    <p class="distributor-contact-card__name"><?= esc_html($args['champion']['name']); ?></p>
                    <p class="distributor-contact-card__role"><?= esc_html($args['champion']['role']); ?></p>
                </div>

            </div>
        <?php } ?>

        <?php if (!empty($args['actions'])) { ?>
            <div class="distributor-contact-card__actions">
                <?php foreach ($args['actions'] as $action) { ?>
                    <a
                        class="distributor-contact-card__action distributor-contact-card__action--<?= !empty($action['primary']) ? 'primary' : 'secondary'; ?>"
                        href="<?= esc_url($action['url']); ?>"
                        data-partner-action="<?= esc_attr($action['data']); ?>"
                        <?php /* tel: does nothing on a desktop, so these reveal the number on click instead. */ ?>
                        <?php if (strpos($action['url'], 'tel:') === 0) { ?>data-reveal-phone<?php } ?>
                        <?php if (!empty($action['external'])) { ?>target="_blank" rel="noopener noreferrer"<?php } ?>
                    >
                        <?php $icon($action['icon'], 'distributor-contact-card__action-icon'); ?>
                        <span data-reveal-phone-label><?= esc_html($action['label']); ?></span>
                    </a>
                <?php } ?>
            </div>
        <?php } ?>

        <?php if (!empty($args['branch'])) { ?>
            <div class="distributor-contact-card__branch">
                <p class="distributor-contact-card__branch-heading"><?= esc_html($args['branch_heading']); ?></p>

                <ul class="distributor-contact-card__branch-links">
                    <?php foreach ($args['branch'] as $link) { ?>
                        <li>
                            <a
                                class="distributor-contact-card__branch-link"
                                href="<?= esc_url($link['url']); ?>"
                                data-partner-action="<?= esc_attr($link['data']); ?>"
                                <?php if (strpos($link['url'], 'tel:') === 0) { ?>data-reveal-phone<?php } ?>
                                <?php if (!empty($link['external'])) { ?>target="_blank" rel="noopener noreferrer"<?php } ?>
                            >
                                <?php if ($link['icon'] !== 'external') { ?>
                                    <?php $icon($link['icon'], 'distributor-contact-card__branch-icon'); ?>
                                <?php } ?>
                                <span data-reveal-phone-label><?= esc_html($link['label']); ?></span>
                                <?php if ($link['icon'] === 'external') { ?>
                                    <?php $icon('external', 'distributor-contact-card__branch-icon'); ?>
                                <?php } ?>
                            </a>
                        </li>
                    <?php } ?>
                </ul>

                <?php if (!empty($args['note'])) { ?>
                    <p class="distributor-contact-card__note"><?= esc_html($args['note']); ?></p>
                <?php } ?>
            </div>
        <?php } ?>

    </div>
</section>
