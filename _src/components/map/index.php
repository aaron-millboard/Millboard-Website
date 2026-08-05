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
                <?php // Icon-only control; the label is kept for assistive tech. ?>
                <button
                    type="button"
                    class="map__search__geolocate"
                    aria-label="<?= esc_attr($args['search_geolocate_text']); ?>"
                    title="<?= esc_attr($args['search_geolocate_text']); ?>"
                >
                    <span class="map__search__geolocate__text visually-hidden"><?= esc_html($args['search_geolocate_text']); ?></span>
                </button>
            <?php } ?>

            <?php if (!empty($args['filters'])) { ?>
                <div class="map__legend">
                    <span class="map__legend__label">
                        <?= esc_html_x('Key', 'Map legend label', 'granola'); ?>
                    </span>

                    <?php foreach ($args['filters'] as $filter) { ?>
                        <?php if (empty($filter['value'])) { continue; } ?>
                        <?php $marker = !empty($filter['marker']) ? $filter['marker'] : $filter['value']; ?>
                        <span class="map__legend__item">
                            <img
                                class="map__legend__marker"
                                src="<?= esc_url(\Granola\Components\Map\marker_icon_url($marker)); ?>"
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

        <?php
        /**
         * Appointed market distributor notice.
         *
         * Shown only when a search lands in a country served by one appointed distributor.
         * The shell is rendered here rather than built in JavaScript so the copy goes
         * through translation on all six locales; Map.js fills in the country, the partner
         * and the territory from the matched listing.
         */
        ?>
        <div class="map__territory-banner alignwide" data-map-territory-banner hidden>
            <p class="map__territory-banner__eyebrow">
                <span data-map-territory-country></span>
                <?= esc_html_x('Appointed market distributor', 'Map territory banner', 'granola'); ?>
            </p>

            <p class="map__territory-banner__text">
                <?= esc_html_x('Our appointed distributor for this country is listed below. Contact them for details of local stockists.', 'Map territory banner', 'granola'); ?>
            </p>
        </div>

        <div class="map__content alignwide">
            <div id="map-sidebar" class="map__sidebar map__tab-panel">
                <?= \Granola\Component::get('heading', $args['sidebar_heading']) ?>

                <?php /* translators: %s: the countries the appointed distributor covers. */ ?>
                <p
                    class="map__territory-subheading"
                    data-map-territory-subheading
                    data-template="<?= esc_attr_x('%s territory, distance filter not used', 'Map territory subheading', 'granola'); ?>"
                    hidden
                ></p>

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

                <?php
                /**
                 * Sits where the other result cards would have been, so a single result
                 * reads as deliberate rather than as a search that found almost nothing.
                 */
                ?>
                <div class="map__territory-note" data-map-territory-note hidden>
                    <svg class="map__territory-note__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"></path>
                        <path d="m9 12 2 2 4-4"></path>
                    </svg>

                    <p class="map__territory-note__heading">
                        <?= esc_html_x('This is our preferred partner for your search', 'Map territory note', 'granola'); ?>
                    </p>

                    <?php
                    /**
                     * Describes the territory rather than naming the partner: Benelux is
                     * served by two Wooddeck entities, so a sentence built around one name
                     * would either hide the other or need singular and plural forms in
                     * every locale.
                     *
                     * translators: %s: the countries the appointment covers.
                     */
                    ?>
                    <p
                        class="map__territory-note__text"
                        data-map-territory-note-text
                        data-template="<?= esc_attr_x('We supply %s through appointed distributors, so only they are listed. They will point you to your nearest stockist and advise on availability and lead times.', 'Map territory note', 'granola'); ?>"
                    ></p>

                    <p class="map__territory-note__small">
                        <?= esc_html_x('Distributors in neighbouring countries are not listed, even where they are closer by road.', 'Map territory note', 'granola'); ?>
                    </p>

                    <?php if (!empty($args['global_distributors_url'])) { ?>
                        <a class="map__territory-note__link" href="<?= esc_url($args['global_distributors_url']); ?>">
                            <?= esc_html_x('View all global distributors', 'Map territory note', 'granola'); ?>
                        </a>
                    <?php } ?>
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

                <div id="leaflet-map-container"></div>
            </div>
        </div>
    </div>
</div>
