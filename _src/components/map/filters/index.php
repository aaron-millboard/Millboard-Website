<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <form id="map-filters-form" class="map__filters__form">
        <?php if (!empty($args['filters'])) { ?>
            <?php foreach ($args['filters'] as $filter_group) { ?>
                <?php $filter_group_name = 'map-filtergroup-' . $filter_group['id']; ?>

                <fieldset
                    name="<?= esc_attr($filter_group_name); ?>"
                    form="map_filters_form"
                    class="map__filters__fieldset"
                >
                    <?= \Granola\Component::get('heading', [
                        'el' => 'h3',
                        'heading' => $filter_group['label'],
                        'classes' => ['map__filters__fieldset__heading']
                    ]); ?>

                    <div class="map__filters__fieldset__inner">
                        <?php foreach ($filter_group['facets'] as $filter_group_filter) { ?>
                            <?php
                            $filter_field_name = 'map-filter-' . $filter_group['id'];
                            $filter_field_id = $filter_field_name . '-' . $filter_group_filter['slug'];
                            ?>
                            <div class="map__filters__filter">
                                <input
                                    type="checkbox"
                                    name="<?= esc_attr($filter_field_name); ?>"
                                    value="<?= esc_attr($filter_group_filter['slug']); ?>"
                                    id="<?= esc_attr($filter_field_id); ?>">

                                <label
                                    class="<?= \Granola\Helpers::build_classes([
                                        'map__filters__filter__label',
                                        'map__filters__filter--' . $filter_group_filter['slug'],
                                    ]); ?>"
                                    for="<?= esc_attr($filter_field_id); ?>">
                                    <span class="map__filters__filter__text">
                                        <?= esc_html($filter_group_filter['label']); ?>
                                    </span>
                                </label>
                            </div>
                        <?php } ?>
                    </div>
                </fieldset>
            <?php } ?>
        <?php } ?>

        <div class="map__filters__buttons">
            <?php if (isset($args['buttons']['apply'])) {?>
                <?= Granola\Component::get('button', $args['buttons']['apply']); ?>
            <?php } ?>

            <?= Granola\Component::get('button', $args['buttons']['clear']); ?>
        </div>
    </form>
</div>
