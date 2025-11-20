<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>

    <?= \Granola\Component::get('button', $args['button']);?>

    <div <?= \Granola\Helpers::build_attributes($args['panel_attributes']); ?>>

        <div class="accordion__item__panel__inner">

            <?= wp_kses_post($args['content']) ?>

            <?php if (!empty($args['cta'])) { ?>
                <?= \Granola\Component::get('link', $args['cta']); ?>
            <?php } ?>
            
        </div>

    </div>

</div>
