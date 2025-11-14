<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="accordion__inner">
        <?php if (!empty($args['heading'])) { ?>
            <div class="accordion__header">
                <?= \Granola\Component::get('heading', $args['heading']); ?>
            </div>
        <?php } ?>

        <?php if (!empty($args['accordion_items'])) { ?>
            <div class="accordion__items">
                <?php foreach ($args['accordion_items'] as $key => $item) { ?>
                    <div class="accordion__item">
                        <?= \Granola\Component::get('button', $item['button']);?>

                        <div <?= \Granola\Helpers::build_attributes($item['panel_attributes']); ?>>
                            <div class="accordion__item__panel-inner">
                                <?= wp_kses_post($item['content']) ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</section>
