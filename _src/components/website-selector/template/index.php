<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="website-selector__inner">
        <?php if (!empty($args['preheading']) || !empty($args['heading'])) { ?>
            <div class="website-selector__header">
                <?php if (!empty($args['preheading'])) { ?>
                    <h2 class="website-selector__preheading">
                        <?= wp_kses_post($args['preheading']); ?>
                    </h2>
                <?php } ?>

                <?php if (!empty($args['heading'])) { ?>
                    <h2 class="website-selector__heading">
                        <?= wp_kses_post($args['heading']); ?>
                    </h2>
                <?php } ?>
            </div>
        <?php } ?>

        <div class="website-selector__columns">
            <?php foreach ($args['columns'] as $column) { ?>
                <div class="website-selector__column">
                    <?php if (!empty($column['description'])) { ?>
                        <div class="website-selector__column__description">
                            <?= wp_kses_post($column['description']); ?>
                        </div>
                    <?php } ?>

                    <div class="website-selector__column--widget">

                        <?php if (!empty($column['image_data'])) { ?>
                            <div class="website-selector__column__image">
                                <?= \Granola\Component::get('image', $column['image_data']); ?>
                            </div>
                        <?php } ?>

                        <?php
                        // Check if either CTA exists
                        $cta1 = get_field('website_selector_column_1_cta', 'options');
                        $cta2 = get_field('website_selector_column_2_cta', 'options');

                        if (!empty($cta1) || !empty($cta2)) { ?>
                            <div class="website-selector__column__cta">
                                <?php if (!empty($cta1)) { ?>
                                    <?= \Granola\Component::get('link', [
                                        'url' => $cta1['url'],
                                        'content' => $cta1['title'],
                                        'target' => $cta1['target'] ?? '_self',
                                        'classes' => ['website-selector__column__cta__button'],
                                    ]); ?>
                                <?php } ?>

                                <?php if (!empty($cta2)) { ?>
                                    <?= \Granola\Component::get('link', [
                                        'url' => $cta2['url'],
                                        'content' => $cta2['title'],
                                        'target' => $cta2['target'] ?? '_self',
                                        'classes' => ['website-selector__column__cta__button'],
                                    ]); ?>
                                <?php } ?>
                            </div>
                        <?php } ?>

                    </div>

                </div>
            <?php } ?>
        </div>

        <div class="website-selector__background">
            <div class="website-selector__background-left"></div>
            <div class="website-selector__background-top"></div>
            <div class="website-selector__background-bottom"></div>
        </div>
    </div>
</div>
