<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="category-split-list__inner">
        <div class="category-split-list__aside nflm">
            <hr class="category-split-list__rule" aria-hidden="true">

            <?php if (!empty($args['meta_prefix'])) { ?>
                <p class="category-split-list__meta"><?= \esc_html($args['meta_prefix']); ?></p>
            <?php } ?>

            <?php if (!empty($args['heading'])) { ?>
                <h2 class="category-split-list__heading"><?= \esc_html($args['heading']); ?></h2>
            <?php } ?>

            <?php if (!empty($args['intro'])) { ?>
                <div class="category-split-list__intro"><?= \wp_kses_post(\wpautop($args['intro'])); ?></div>
            <?php } ?>

            <?php if (!empty($args['link']['url']) && !empty($args['link']['title'])) { ?>
                <p class="category-split-list__link">
                    <?= \Granola\Component::get('link', [
                        'url' => $args['link']['url'],
                        'title' => $args['link']['title'],
                        'target' => $args['link']['target'] ?? '',
                        'classes' => ['g-button', 'g-button--secondary'],
                    ]); ?>
                </p>
            <?php } ?>

            <?php if (!empty($args['image_id'])) { ?>
                <div class="category-split-list__media">
                    <?= \Granola\Component::get('image', [
                        'attachment_id' => $args['image_id'],
                        'size' => 'medium_large',
                        'alt' => '',
                        'classes' => ['category-split-list__image'],
                    ]); ?>
                </div>
            <?php } ?>
        </div>

        <dl class="category-split-list__items">
            <?php foreach ($args['items'] as $item) { ?>
                <div class="category-split-list__item">
                    <dt class="category-split-list__term"><?= \esc_html($item['title']); ?></dt>
                    <dd class="category-split-list__detail"><?= \esc_html($item['detail']); ?></dd>
                </div>
            <?php } ?>
        </dl>
    </div>
</section>
