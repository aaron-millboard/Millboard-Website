<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.fullscreen/dist/Control.FullScreen.css" />

<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
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

                <?= \Granola\Component::get('button', $args['search_submit']); ?>
            </div>

            <?php if (!empty($args['search_geolocate_text'])) { ?>
                <?= \Granola\Component::get('button', [
                    'content' => $args['search_geolocate_text'],
                    'classes' => [
                        'map__search__geolocate',
                    ],
                ]); ?>
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

        <div class="map__content alignwide">
            <div id="map-sidebar" class="map__sidebar map__tab-panel">
                <?= \Granola\Component::get('heading', $args['sidebar_heading']) ?>

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
            </div>

            <div id="map-container" class="map__map-container map__tab-panel">
                <div id="leaflet-map-container"></div>
            </div>
        </div>
    </div>
</div>
