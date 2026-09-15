<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="category-header__inner">
        <div class="category-header__layout">
            <div class="category-header__text nflm">
                <hr class="category-header__rule" aria-hidden="true">

                <?php if (!empty($args['trail'])) { ?>
                    <nav class="category-header__trail" aria-label="<?= \esc_attr__('Breadcrumb', 'granola'); ?>">
                        <ol class="category-header__trail-list" role="list">
                            <?php foreach ($args['trail'] as $crumb) { ?>
                                <li class="category-header__crumb">
                                    <?php if (!empty($crumb['url'])) { ?>
                                        <a href="<?= \esc_url($crumb['url']); ?>"><?= \esc_html($crumb['label']); ?></a>
                                    <?php } else { ?>
                                        <?= \esc_html($crumb['label']); ?>
                                    <?php } ?>
                                </li>
                            <?php } ?>
                        </ol>
                    </nav>
                <?php } ?>

                <h1 class="category-header__heading"><?= \esc_html($args['heading']); ?></h1>

                <?php if (!empty($args['intro'])) { ?>
                    <div class="category-header__intro">
                        <?= \wp_kses_post(\wpautop($args['intro'])); ?>
                    </div>
                <?php } ?>

                <?php if (!empty($args['buttons'])) { ?>
                    <div class="category-header__buttons">
                        <?php foreach ($args['buttons'] as $button) { ?>
                            <?= \Granola\Component::get('link', $button); ?>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>

            <?php if (!empty($args['image_id'])) { ?>
                <div class="category-header__media">
                    <?= \Granola\Component::get('image', [
                        'attachment_id' => $args['image_id'],
                        'size' => 'large',
                        'alt' => '',
                        'loading' => 'eager',
                        'classes' => ['category-header__image'],
                    ]); ?>
                </div>
            <?php } ?>
        </div>
    </div>
</section>
