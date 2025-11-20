<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="accordion__inner">

        <div class="accordion__header">

            <?php if (!empty($args['preheading'])) { ?>
                <?= \Granola\Component::get('heading', [
                    'el' => 'div',
                    'content' => $args['preheading'],
                    'classes' => ['accordion__header__preheading'],
                ]); ?>
            <?php } ?>

            <?php if (!empty($args['heading'])) { ?>
                <?= \Granola\Component::get('heading', [
                    'content' => $args['heading'],
                    'el' => 'h3',
                    'classes' => ['accordion__header__heading'],
                ]); ?>
            <?php } ?>

            <?php if (!empty($args['description'])) { ?>
                <div class="accordion__header__description">
                    <?= wp_kses_post($args['description']['content']); ?>
                </div>
            <?php } ?>

            <?php if (!empty($args['cta'])) { ?>
                <?= \Granola\Component::get('link', $args['cta']); ?>
            <?php } ?>

        </div>

        <?= $args['innerblocks_tag']; ?>

    </div>
</section>
