<header <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="page-header__inner">
        <?php if (!empty($args['image']) && $args['image_position'] === 'inset') { ?>
            <div class="page-header__inset-image">
                <div class="page-header__inset-image-inner img-fit">
                    <?= \Granola\Component::get('image', $args['image']); ?>
                </div>
            </div>
        <?php } ?>

        <?php if (!empty($args['heading'])) { ?>
            <?= \Granola\Component::get('heading', $args['heading']); ?>
        <?php } ?>

        <?php if (!empty($args['subheading'])) { ?>
            <div class="page-header__subheading">
                <?= wp_kses_post($args['subheading']); ?>
            </div>
        <?php } ?>

        <?php if (!empty($args['meta'])) { ?>
            <div class="page-header__meta is-style-typestyle-meta">
                <?= wp_kses_post($args['meta']); ?>
            </div>
        <?php } ?>

        <?php if (!empty($args['labels'])) { ?>
            <div class="page-header__labels">
                <div class="page-header__labels__items flex-list">
                    <?php foreach ($args['labels'] as $label) {
                        echo \Granola\Component::get('link', [
                            'title' => $label['name'],
                            'url' => $label['url'],
                            'classes' => [
                                'g-button',
                                'g-button--label',
                            ],
                        ]);
                    } ?>
                </div>
            </div>
        <?php } ?>

        <?php if (!empty($args['primary_call_to_action'])) { ?>
            <div class="page-header__ctas">
                <?= \Granola\Component::get('link', $args['primary_call_to_action']); ?>
            </div>
        <?php } ?>
    </div>

    <?php if (!empty($args['image']) && $args['image_position'] === 'background') { ?>
        <div class="page-header__background-image">
            <div class="page-header__background-image-inner img-fit">
                <?= \Granola\Component::get('image', $args['image']); ?>
            </div>
        </div>
    <?php } ?>
</header>
