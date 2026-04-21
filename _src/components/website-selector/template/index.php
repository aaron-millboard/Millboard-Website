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
            <?php
            // Retrieve the popup links
            $popup_link_1 = get_field('popup_link_1', 'options');
            $popup_link_2 = get_field('popup_link_2', 'options');

            // Loop through the columns
            foreach ($args['columns'] as $index => $column) { ?>
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

                        <?php if (!empty($column['cta'])) { ?>
                            <div class="website-selector__column__cta">
                                <?= \Granola\Component::get('link', [
                                    'url' => $column['cta']['url'],
                                    'content' => $column['cta']['title'],
                                    'target' => $column['cta']['target'],
                                    'classes' => ['website-selector__column__cta__button'],
                                ]); ?>
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
