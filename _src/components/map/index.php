<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="map__header">
        <form class="map__search" method="post">
            <input
                id="map--map--search_input"
                type="text"
                name="map--map--search"
                form="map-filters-form"
                placeholder="<?= esc_html_x('Search...', 'Map search input placeholder', 'granola'); ?>"
            >

            <?= \Granola\Component::get('button', $args['search_submit']); ?>

            <div class="map__distance">
                <label class="map__distance__label" for="map-distance-select">
                    <?= esc_html_x('Distance', 'Map distance dropdown label', 'granola'); ?>
                </label>

                <select id="map-distance-select" class="map__distance__input">
                    <option value="10">10 miles</option>
                    <option value="25">25 miles</option>
                    <option value="50">50 miles</option>
                    <option value="100">100 miles</option>
                    <option value="150">150 miles</option>
                    <option value="250">250 miles</option>
                    <option value="500">500 miles</option>
                </select>
            </div>
        </form>
    </div>

    <div class="map__body">
        <div class="map__sidebar">
            <?= \Granola\Component::get('heading', $args['sidebar_heading']) ?>

            <div class="map__items">
                <?php if (!empty($args['items'])) { ?>
                    <?php foreach ($args['items'] as $item) { ?>
                        <?= Granola\Component::get('map/listing', $item); ?>
                    <?php } ?>
                <?php } else { ?>
                    <strong class="map__sidebar__no-content">
                        <?= esc_html__('No listings found...', 'granola'); ?>
                    </strong>
                <?php } ?>
            </div>
        </div>

        <div id="map-map-container" class="map__map-container">
            <div id="leaflet-map-container"></div>
        </div>
    </div>
</div>
