<div <?= \Granola\Helpers::build_attributes($args['attributes']) ?>>
    <ul class="social-icons__icons list-reset--hard flex-column">
        <?php foreach ($args['networks'] as $key => $network) { ?>
            <?php $value = $network['network']['value']; ?>
            <li class="social-icons__icon social-icons__icon--<?= esc_attr($value) ?>">
                <a target="_blank" rel="noopener noreferrer" href="<?= esc_url($network['url']) ?>">
                    <?php if (!empty($network['title'])) { ?>
                        <span class="visually-hidden">
                            <?= wp_kses_post($network['title']); ?>
                        </span>
                    <?php } ?>

                    <?= \Granola\SVG::get("social/$value.svg"); ?>
                </a>
            </li>
        <?php } ?>
    </ul>
</div>
