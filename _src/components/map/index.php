<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="map__header">
        <?= Granola\Component::get('button', $args['buttons']['filter']); ?>

        <div class="map__header__search">
            <input
                id="map--map--search_input"
                type="text"
                name="map--map--search"
                form="map-filters-form"
                placeholder="<?= _x('Search', 'Map search input placeholder', 'granola'); ?>"
            >
        </div>

        <?= Granola\Component::get('map/filters'); ?>
    </div>

    <div class="map__body">
        <div class="map__items">
            <?php foreach ($args['items'] as $item) { ?>
                <?= Granola\Component::get('map/listing', $item); ?>
            <?php } ?>
        </div>

        <div id="map-map-container" class="map__map-container">
            <!-- data-map-world-svg-url="<?= \Granola\Paths::theme_asset_url('svgs/world-2d-map-basic.svg'); ?>"> -->
            <div id="leaflet-map-container"></div>
        </div>
    </div>
</div>
