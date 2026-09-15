<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="category-header__inner">
        <?php
        // The theme's own breadcrumbs component, in the same position
        // page-header puts it, so this page type reads identically to every
        // other one rather than inventing its own trail.
        ?>
        <div class="category-header__breadcrumbs">
            <?= \Granola\Component::get('breadcrumbs'); ?>
        </div>

        <div class="category-header__layout">
            <div class="category-header__text nflm">
                <hr class="category-header__rule" aria-hidden="true">

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
                        'alt' => $args['image_alt'],
                        'loading' => 'eager',
                        'classes' => ['category-header__image'],
                    ]); ?>
                </div>
            <?php } ?>
        </div>
    </div>
</section>
