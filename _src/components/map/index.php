<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.fullscreen/dist/Control.FullScreen.css" />

<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?php if (!empty($args['subtitle'])) { ?>
        <p class="map__subtitle alignwide">
            <?= esc_html($args['subtitle']); ?>
        </p>
    <?php } ?>

    <div class="map__search alignwide">
        <form class="map__search__form" method="post">
            <div class="map__search__input-wrapper">
                <label class="map__search-label visually-hidden" for="map-search-input">
                    <?= esc_html_x('Search', 'Map search input label', 'granola'); ?>
                </label>

                <input
                    id="map-search-input"
                    type="text"
                    name="map--map--search"
                    class="map__search__input"
                    placeholder="<?= esc_html_x('Enter your postcode, town or city', 'Map search input placeholder', 'granola'); ?>"
                >

                <button type="submit" class="map__search__submit" aria-label="<?= esc_attr_x('Search', 'Map search submit', 'granola'); ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.5" y2="16.5"></line></svg>
                </button>
            </div>

            <?php if (!empty($args['search_geolocate_text'])) { ?>
                <button type="button" class="map__search__geolocate">
                    <span class="map__search__geolocate__text"><?= esc_html($args['search_geolocate_text']); ?></span>
                </button>
            <?php } ?>

            <div class="map__distance">
                <label class="map__distance__label" for="map-distance-select">
                    <?= esc_html_x('Distance', 'Map distance dropdown label', 'granola'); ?>
                </label>

                <select id="map-distance-select" class="map__distance__input">
                    <option value="">
                        <?= esc_html_x('Any', 'Map distance selector default option text', 'granola'); ?>
                    </option>

                    <?php foreach ($args['distances'] as $value) { ?>
                        <option value="<?= esc_attr($value); ?>">
                            <?= esc_html(
                                sprintf(
                                    // translators: Distance amount.
                                    _x('%d miles', 'Map distance selector option text', 'granola'),
                                    $value
                                )
                            ); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </form>
    </div>

    <div class="map__body">
        <div class="map__tablist">
            <?= \Granola\Component::get('button', [
                'content' => \_x('List', 'Map mobile tab button label', 'granola'),
                'classes' => [
                    'map__tab',
                    'map__tab--active',
                    'map__tab--list',
                ],
                'attributes' => [
                    'aria-controls' => 'map-sidebar'
                ],
                ]); ?>

            <?= \Granola\Component::get('button', [
                'content' => \_x('Map', 'Map mobile tab button label', 'granola'),
                'classes' => [
                    'map__tab',
                    'map__tab--map',
                ],
                'attributes' => [
                    'aria-controls' => 'map-container'
                ],
            ]); ?>
        </div>

        <?php if (!empty($args['filters'])) { ?>
            <div class="map__filters map__filters--mobile alignwide">
                <?php foreach ($args['filters'] as $filter) { ?>
                    <button
                        type="button"
                        class="g-button map__filter <?= $filter['active'] ? 'map__filter--active' : ''; ?>"
                        data-filter-value="<?= esc_attr($filter['value']); ?>"
                    >
                        <?= esc_html($filter['label']); ?>
                        <span class="map__filter__count"><?= esc_html($filter['count']); ?></span>
                    </button>
                <?php } ?>
            </div>
        <?php } ?>

        <div class="map__content alignwide">
            <div id="map-sidebar" class="map__sidebar map__tab-panel">
                <?= \Granola\Component::get('heading', $args['sidebar_heading']) ?>

                <?php if (!empty($args['filters'])) { ?>
                    <div class="map__filters map__filters--sidebar">
                        <?php foreach ($args['filters'] as $filter) { ?>
                            <button 
                                type="button"
                                class="g-button map__filter <?= $filter['active'] ? 'map__filter--active' : ''; ?>"
                                data-filter-value="<?= esc_attr($filter['value']); ?>"
                            >
                                <?= esc_html($filter['label']); ?>
                                <span class="map__filter__count"><?= esc_html($filter['count']); ?></span>
                            </button>
                        <?php } ?>
                    </div>
                <?php } ?>

                <div class="map__items">
                    <?php if (!empty($args['items'])) { ?>
                        <?php foreach ($args['items'] as $item) { ?>
                            <?= Granola\Component::get('map/listing', $item); ?>
                        <?php } ?>
                    <?php } ?>

                    <strong class="map__sidebar__no-content">
                        <?= esc_html__('Please try removing filters or widening your search area.', 'granola'); ?>
                    </strong>
                </div>

                <div class="map__show-more-wrap">
                    <button
                        type="button"
                        class="map__show-more"
                        aria-expanded="false"
                        hidden
                    >
                        <span class="map__show-more__label"></span>
                    </button>
                </div>
            </div>

            <div id="map-container" class="map__map-panel map__map-container map__tab-panel">
                <?php if (!empty($args['filters'])) { ?>
                    <div class="map__legend">
                        <span class="map__legend__label">
                            <?= esc_html_x('Key', 'Map legend label', 'granola'); ?>
                        </span>

                        <?php foreach ($args['filters'] as $filter) { ?>
                            <?php if (empty($filter['value'])) { continue; } ?>
                            <?php // Tier filters share one pin, so they name their marker explicitly. ?>
                            <?php $marker = !empty($filter['marker']) ? $filter['marker'] : $filter['value']; ?>
                            <span class="map__legend__item">
                                <img
                                    class="map__legend__marker <?= esc_attr($filter['marker_class'] ?? ''); ?>"
                                    src="<?= esc_url(\get_template_directory_uri() . '/assets/images/icons/' . $marker . '-marker.png'); ?>"
                                    alt=""
                                    width="17"
                                    height="21"
                                    loading="lazy"
                                />
                                <?= esc_html($filter['label']); ?>
                            </span>
                        <?php } ?>
                    </div>
                <?php } ?>

                <div id="leaflet-map-container"></div>
            </div>
        </div>
    </div>
</div>
