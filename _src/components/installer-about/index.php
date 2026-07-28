<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="installer-about__inner">

        <div class="installer-about__body">
            <span class="installer-about__rule" aria-hidden="true"></span>
            <h2 class="installer-about__heading"><?= esc_html($args['heading']); ?></h2>

            <?php if (!empty($args['body'])) { ?>
                <div class="installer-about__text"><?= wp_kses_post($args['body']); ?></div>
            <?php } ?>

            <?php if (!empty($args['tags'])) { ?>
                <div class="installer-about__tags">
                    <?php foreach ($args['tags'] as $tag) { ?>
                        <span class="installer-about__tag"><?= esc_html($tag); ?></span>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>

        <?php if ($args['aside'] === 'facts') { ?>
            <dl class="installer-about__facts">
                <?php foreach ($args['facts'] as $fact) { ?>
                    <div class="installer-about__fact">
                        <dt class="installer-about__fact-label"><?= esc_html($fact['label']); ?></dt>
                        <dd class="installer-about__fact-value"><?= esc_html($fact['value']); ?></dd>
                    </div>
                <?php } ?>
            </dl>
        <?php } elseif ($args['aside'] === 'image') { ?>
            <div class="installer-about__media">
                <?= wp_get_attachment_image(
                    $args['image'],
                    'large',
                    false,
                    ['class' => 'installer-about__image']
                ); ?>
            </div>
        <?php } ?>

    </div>
</section>
