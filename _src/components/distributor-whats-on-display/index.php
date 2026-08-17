<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="distributor-whats-on-display__inner">

        <div class="distributor-whats-on-display__content">
            <span class="distributor-whats-on-display__rule" aria-hidden="true"></span>

            <h2 class="distributor-whats-on-display__heading"><?= esc_html($args['heading']); ?></h2>

            <?php if (!empty($args['intro'])) { ?>
                <div class="distributor-whats-on-display__intro nflm"><?= wp_kses_post($args['intro']); ?></div>
            <?php } ?>

            <div class="distributor-whats-on-display__groups">
                <?php foreach ($args['groups'] as $group) { ?>
                    <div class="distributor-whats-on-display__group">
                        <?php if (!empty($group['label'])) { ?>
                            <h3 class="distributor-whats-on-display__group-heading"><?= esc_html($group['label']); ?></h3>
                        <?php } ?>

                        <ul class="distributor-whats-on-display__chips">
                            <?php foreach ($group['items'] as $item) { ?>
                                <li class="distributor-whats-on-display__chip"><?= esc_html($item); ?></li>
                            <?php } ?>
                        </ul>
                    </div>
                <?php } ?>
            </div>
        </div>

        <?php if (!empty($args['image'])) { ?>
            <div class="distributor-whats-on-display__media">
                <?= \Granola\Component::get('image', [
                    'attachment_id' => $args['image'],
                    'classes' => ['distributor-whats-on-display__image'],
                    'size' => 'large',
                    'alt' => '',
                ]); ?>
            </div>
        <?php } ?>

    </div>
</section>