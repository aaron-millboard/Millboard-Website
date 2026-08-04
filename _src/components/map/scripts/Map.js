import throttle from 'lodash.throttle';
import L from 'leaflet/dist/leaflet.js';
import { FullScreen } from 'leaflet.fullscreen';
import isElementVisible from '../../../scripts/helpers/isElementVisible.js';

// Inline SVGs for the marker popup. Stroke uses currentColor so each icon
// inherits the colour of the element it sits in.
const TOOLTIP_ICONS = {
    pin: '<svg class="map__marker-tooltip__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>',
    directions: '<svg class="map__marker-tooltip__icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="3 11 22 2 13 21 11 13 3 11"></polygon></svg>',
    mail: '<svg class="map__marker-tooltip__icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16"></rect><path d="m2 7 10 6 10-6"></path></svg>',
};

// https://leafletjs.com/reference.html
class Map {
    constructor(element) {
        // Elements
        this.el = element;

        // Listings.
        this.listingContainer = this.el.querySelector('.map__items');
        this.listingEls = this.el.querySelectorAll('.map__listing');
        this.listingsHeading = this.el.querySelector('.map__sidebar__heading');

        // Map container.
        this.mapContainerEl = this.el.querySelector('.map__map-container');
        this.mapPanelEl = this.mapContainerEl ? this.mapContainerEl.closest('.map__tab-panel') : null;

        // Search.
        this.searchInput = this.el.querySelector('#map-search-input');
        this.searchSubmit = this.el.querySelector('.map__search__submit');

        // Distance.
        this.distanceSelect = this.el.querySelector('.map__distance__input');

        // Geolocate button.
        this.geolocateButton = this.el.querySelector('.map__search__geolocate');

        // Mobile Tabs.
        this.tablist = this.el.querySelector('.map__tablist');
        this.tabs = this.tablist ? this.tablist.querySelectorAll('.map__tab') : [];
        this.tabPanels = this.el.querySelectorAll('.map__tab-panel');

        this.mobileMediaQueryRefEl = this.tablist;

        this.appliedFilterSlugsByFiltergroup = {};
        this.markerSubGroupsByFilterableValue = {};

        // Variables for the Leaflet Map.
        this.LMAP_ZOOM_DELTA = 1.4; // Ws 0.8
        this.LMAP_ZOOM_SNAP = this.LMAP_ZOOM_DELTA;
        this.LMAP_INITIAL_ZOOM = 8; // Closer default view.
        this.LMAP_GB_INITIAL_ZOOM = 9; // Even closer view for GB/Coventry.
        this.LMAP_MIN_ZOOM = 2.4;
        this.LMAP_MAX_ZOOM = 15; // Was 7.2
        this.LMAP_INITIAL_CENTER = [52.4068, -1.5197]; // Center on Coventry.
        this.LMAP_DISTANCE_CENTER = L.latLng(52.4068, -1.5197);

        this.localeCountryCode = this.getCountryCodeFromUrl() || 'gb';
        this.urlLocaleLatLng = this.getLatLngFromLocaleCode();
        this.urlLocationQuery = this.getLocationQueryFromUrl();

        if (this.urlLocaleLatLng) {
            this.LMAP_INITIAL_CENTER = [this.urlLocaleLatLng.lat, this.urlLocaleLatLng.lng];
            this.LMAP_DISTANCE_CENTER = this.urlLocaleLatLng;
        }

        if (this.localeCountryCode === 'gb') {
            this.LMAP_INITIAL_ZOOM = this.LMAP_GB_INITIAL_ZOOM;
        }

        this.LMAP_MARKER_WIDTH = 31;
        this.LMAP_MARKER_HEIGHT = 40;

        this.METERS_TO_MILES_RATIO = 0.000621371;

        this.allMarkersGroup = new L.FeatureGroup();
        this.filteredMarkersGroup = new L.FeatureGroup();

        this.activePostTypeFilter = ''; // Empty string means all post types

        this.googleApiKey = window.params.google_api_key;
        this.roadDistancesEndpoint = window.params.road_distances_endpoint;

        // Road distances are only fetched once the user has picked a real
        // location (search, geolocate, or URL param) - never for the default
        // locale-centred view - to keep Routes API usage down.
        this.hasUserSearchLocation = false;
        this.roadDistanceRequestId = 0;
        this.ROAD_DISTANCE_LIMIT = 25; // Routes API matrix cap per request.
        // Overall ceiling on destinations we price per search, batched in
        // ROAD_DISTANCE_LIMIT chunks. Straight-line distance used to decide the
        // radius filter, which meant a branch on the far side of a sea counted as
        // near: 34 miles Calais to Dover as the crow flies, about 90 by road. Road
        // distance now drives both the ordering and the radius, so enough of the
        // list has to be priced for that to be reliable rather than just the first
        // screenful. Capped because each chunk is a billed request and nobody
        // scrolls past this; anything beyond keeps straight-line ordering.
        // Held at 50, i.e. two requests per search, to keep Routes API spend down.
        this.ROAD_DISTANCE_MAX_DESTINATIONS = 50;

        // Result grading (brief §2/§3).
        this.EC_SURFACE_RADIUS_MILES = 30; // Experience Centres within this range surface first.
        this.TOP_RESULTS = 3; // List view shows this many by default; the rest sit behind "Show more".
        // Ceiling on how far the map zooms in when framing a search, so a town with
        // three branches close together does not land on street level.
        this.SEARCH_FIT_MAX_ZOOM = 11;
        this.showMoreButton = null;
        this.currentOverflowCount = 0;

        if (typeof L === 'object') {
            this.init();
        }
    }

    init() {
        this.initLeafletMap();
        this.initLeafletMarkers();
        this.initListingSelectionSync();
        this.initSearch();
        this.initDistanceFilter();
        this.initPostTypeFilters();
        this.initShowMore();
        this.initTablist();
        this.initClickTracking();

        this.applyLocationFromUrl();

        if (this.tablist) {
            window.addEventListener('resize', throttle(() => {
                this.updateTabPanelVisibility();

                this.syncMapViewport();
            }, 100));
        }

        this.syncMapViewport();

        if (this.geolocateButton) {
            this.geolocateButton.addEventListener('click', () => {
                this.lmap.locate();
            });

            this.lmap.addEventListener('locationfound', ({latlng}) => {
                this.LMAP_DISTANCE_CENTER = latlng;
                this.hasUserSearchLocation = true;
                this.filterByDistanceAndPostType();
            });
        }
    }

    getUrlSearchParams() {
        return new URLSearchParams(window.location.search || '');
    }

    getUrlParamValue(keys) {
        const searchParams = this.getUrlSearchParams();

        for (const key of keys) {
            const value = searchParams.get(key);

            if (value && value.trim()) {
                return value.trim();
            }
        }

        return null;
    }

    getLocationQueryFromUrl() {
        return this.getUrlParamValue(['location', 'search', 'q', 'postcode']);
    }

    getLocaleCodeFromUrl() {
        const pathnameLocaleCode = this.getLocaleCodeFromPathname();

        if (pathnameLocaleCode) {
            return pathnameLocaleCode;
        }

        const documentLangLocaleCode = this.getLocaleCodeFromDocumentLang();

        if (documentLangLocaleCode) {
            return documentLangLocaleCode;
        }

        const explicitParamValue = this.getUrlParamValue([
            'country',
            'locale',
            'lang',
            'language',
            'market',
            'region',
        ]);

        const localeCodeRegex = /^[a-z]{2}[_-][a-z]{2}$/i;

        if (explicitParamValue && localeCodeRegex.test(explicitParamValue)) {
            return explicitParamValue;
        }

        return null;
    }

    getLocaleCodeFromPathname() {
        const path = (window.location.pathname || '').toLowerCase();
        const segments = path.split('/').filter(Boolean);
        const localeCodeRegex = /^[a-z]{2}[-_][a-z]{2}$/;

        const localeSegment = segments.find((segment) => localeCodeRegex.test(segment));

        return localeSegment || null;
    }

    getLocaleCodeFromDocumentLang() {
        const htmlLang = (document.documentElement.lang || '').trim();
        const localeCodeRegex = /^[a-z]{2}[-_][a-z]{2}$/i;

        if (!htmlLang || !localeCodeRegex.test(htmlLang)) {
            return null;
        }

        return htmlLang;
    }

    getLatLngFromLocaleCode() {
        const countryCode = this.localeCountryCode;
        const countryCentersByCode = {
            at: [47.5162, 14.5501],
            au: [-25.2744, 133.7751],
            be: [50.5039, 4.4699],
            ca: [56.1304, -106.3468],
            ch: [46.8182, 8.2275],
            de: [51.1657, 10.4515],
            dk: [56.2639, 9.5018],
            es: [40.4637, -3.7492],
            fi: [61.9241, 25.7482],
            fr: [46.2276, 2.2137],
            gb: [52.4068, -1.5197],
            ie: [53.1424, -7.6921],
            it: [41.8719, 12.5674],
            nl: [52.1326, 5.2913],
            no: [60.472, 8.4689],
            nz: [-40.9006, 174.886],
            pl: [51.9194, 19.1451],
            pt: [39.3999, -8.2245],
            se: [60.1282, 18.6435],
            us: [37.0902, -95.7129],
        };

        const center = countryCentersByCode[countryCode] || null;

        if (!center) {
            return null;
        }

        return L.latLng(center[0], center[1]);
    }

    getCountryCodeFromUrl() {
        const localeCode = this.getLocaleCodeFromUrl();

        if (!localeCode) {
            return null;
        }

        const normalizedCode = localeCode.toLowerCase().replace('-', '_');
        const [, countryCode] = normalizedCode.split('_');

        return countryCode || null;
    }

    getBoundsFromCountryCode() {
        const countryBoundsByCode = {
            at: [[46.3723, 9.5307], [49.0206, 17.1608]],
            au: [[-43.7405, 112.9111], [-10.6845, 153.6393]],
            be: [[49.497, 2.5417], [51.5055, 6.4081]],
            ca: [[41.6766, -141.0019], [83.3362, -52.3232]],
            ch: [[45.8179, 5.9559], [47.8085, 10.4923]],
            de: [[47.2701, 5.8663], [55.0992, 15.0418]],
            dk: [[54.5591, 8.0728], [57.7518, 12.690]],
            es: [[27.4335, -18.3937], [43.9934, 4.5919]],
            fi: [[59.4542, 20.5569], [70.0923, 31.5867]],
            fr: [[41.3253, -5.1422], [51.1242, 9.5593]],
            gb: [[49.8647, -8.6494], [60.8607, 1.7689]],
            ie: [[51.4194, -10.7506], [55.4359, -5.9941]],
            it: [[35.4897, 6.6273], [47.092, 18.7845]],
            nl: [[50.7504, 3.3316], [53.5546, 7.2275]],
            no: [[57.9596, 4.0875], [71.3849, 31.2934]],
            nz: [[-52.6107, 165.8700], [-29.2228, 178.5597]],
            pl: [[49.002, 14.1229], [54.8358, 24.1458]],
            pt: [[36.8383, -31.2689], [42.1543, -6.1892]],
            se: [[55.337, 10.5931], [69.059, 24.1777]],
            us: [[24.3963, -124.8489], [49.3843, -66.8854]],
        };

        const bounds = countryBoundsByCode[this.localeCountryCode] || null;

        if (!bounds) {
            return null;
        }

        return L.latLngBounds(bounds);
    }

    async applyLocationFromUrl() {
        if (this.urlLatLng) {
            this.hasUserSearchLocation = true;
            this.filterListingsByDistance();
            return;
        }

        if (this.urlLocaleLatLng) {
            this.filterListingsByDistance(false);
            return;
        }

        if (!this.urlLocationQuery) {
            return;
        }

        if (this.searchInput && !this.searchInput.value) {
            this.searchInput.value = this.urlLocationQuery;
        }

        const response = await this.newRequest(this.urlLocationQuery);

        if (!response || !response.results[0]) {
            return;
        }

        const {lat, lng} = response.results[0].geometry.location;
        this.LMAP_DISTANCE_CENTER = new L.LatLng(lat, lng);
        this.LMAP_INITIAL_CENTER = [lat, lng];
        this.hasUserSearchLocation = true;
        this.filterListingsByDistance();
    }

    /**
     * Leaflet Map Init.
     */
    initLeafletMap() {
        const mapContainerNode = this.mapContainerEl ? this.mapContainerEl.querySelector('#leaflet-map-container') : null;

        if (!mapContainerNode) {
            return;
        }

        // https://leafletjs.com/reference.html#map-option
        this.lmap = L.map(mapContainerNode, {
            center: this.LMAP_INITIAL_CENTER,
            attributionControl: false,
            intertia: false,
            maxBoundsViscosity: 1.0,
            zoom: this.LMAP_INITIAL_ZOOM,
            zoomControl: false, // We add this manually later so set a different position.
            zoomDelta: this.LMAP_ZOOM_DELTA,
            zoomSnap: this.LMAP_ZOOM_SNAP,
            minZoom: this.LMAP_MIN_ZOOM,
            maxZoom: this.LMAP_MAX_ZOOM,
            // markerZoomAnimation: false,
            // worldCopyJump: true,
        });

        const countryBounds = this.getBoundsFromCountryCode();

        if (countryBounds && this.localeCountryCode !== 'gb') {
            this.lmap.fitBounds(countryBounds);
        }

        // Debuggers to help find zoom level and center position.
        // this.lmap.on('zoomend', function (event) {
        //     console.log(event.target.getZoom());
        // });
        // this.lmap.on('moveend', function (event) {
        //     console.log(event);
        //     console.log(event.target.getCenter());
        // });

        // CARTO Voyager: a clean but warmer, more detailed basemap than the raw
        // OpenStreetMap tiles, without the washed-out look of Positron.
        const mapTileProvider = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';
        const tileLayer = L.tileLayer(mapTileProvider, {
            maxZoom: 20,
            subdomains: 'abcd',
            // Load @2x tiles on high-DPI screens so the map stays crisp.
            detectRetina: true,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
        });
        this.lmap.addLayer(tileLayer);

        const lmapFullScreenControl = new FullScreen({
            position: 'topright',
        })
        this.lmap.addControl(lmapFullScreenControl);

        // Add leaflet zoom controller.
        // https://leafletjs.com/reference.html#control-zoom
        const lmapZoomControl = L.control.zoom({
            position: 'topright',
        });

        lmapZoomControl.addTo(this.lmap);
    }

    /**
     * Leaflet Markers setup.
     */
    initLeafletMarkers() {
        if (!this.lmap) {
            return;
        }

        const markerHoizontalMiddle = this.LMAP_MARKER_WIDTH / 2; // Icon width / 2 to center it.

        this.listingEls.forEach((el) => {
            // Get data.
            const listingData = Map.getDataFromRowElement(el);

            // Get data values.
            const listingLatLng = L.latLng(
                parseFloat(listingData.lat),
                parseFloat(listingData.lng)
            );
            const listingTitle = listingData.name;

            // Convert post type into filename format
const markerType = listingData.postType
    .toLowerCase()
    .replace(/\s+/g, '-');

// Example:
// Installer -> installer-marker.png
// Experience Centre -> experience-centre-marker.png
// Advanced installers get their own gold "AI" pin, so the map matches the
// "Approved / Advanced" key the same way the distributor types do.
const isAdvancedInstaller = el.dataset.mapItemAdvancedInstaller === '1';
// PHP resolves the pin (including its cache-busting version), so prefer that.
// The fallback keeps older markup working if the attribute is ever absent.
const markerFile = isAdvancedInstaller ? 'installer-advanced' : markerType;
const SVG_PIN_TYPES = ['installer', 'installer-advanced', 'distributor', 'experience_centre', 'showroom'];
const markerExtension = SVG_PIN_TYPES.includes(markerFile) ? 'svg' : 'png';
const markerIconUrl = el.dataset.mapItemMarkerUrl
    || `/wp-content/themes/millboard/assets/images/icons/${markerFile}-marker.${markerExtension}`;

let markerHtml = `
    <span class="leaflet-marker-icon__icon-container" aria-hidden="true">
        <img
            class="leaflet-marker-icon__icon"
            src="${markerIconUrl}"
            alt="${listingData.postType} marker"
            width="${this.LMAP_MARKER_WIDTH}"
            height="${this.LMAP_MARKER_HEIGHT}"
        />
        <span class="screen-reader-text">${listingTitle}</span>
    </span>
`;

            // https://leafletjs.com/reference.html#marker
            const marker = L.marker(listingLatLng, {
                autoPanOnFocus: true,
                icon: L.divIcon({
                    html: markerHtml,
                    iconSize: [this.LMAP_MARKER_WIDTH, this.LMAP_MARKER_HEIGHT],
                    iconAnchor: [markerHoizontalMiddle, this.LMAP_MARKER_HEIGHT], // Icon radius / 2 to center it.
                }),

                // Custom object data.
                themeData: {
                    listingElement: el,
                    distanceInMiles: this.calcLatLngDistanceMilesFromMapCenter(listingLatLng),
                    postType: listingData.postType,
                    advancedInstaller: isAdvancedInstaller,
                },
            });

            const markerTooltipHtml = this.getMarkerTooltipHtml(marker);

            if (markerTooltipHtml) {
                marker.bindPopup(markerTooltipHtml, {
                    className: 'map__marker-tooltip',
                    offset: [0, -this.LMAP_MARKER_HEIGHT],
                    // autoPan must stay off because these popups open on HOVER, not
                    // click. With it on, Leaflet pans the map so each popup fits, so
                    // simply moving the cursor across the pins walked the map away
                    // from where the user was looking: sweep upwards over a few
                    // markers and you end up in Scotland without having asked to go
                    // anywhere. Panning should only ever follow a deliberate click,
                    // which selectMarkerByListing still does.
                    autoPan: false,
                    closeButton: true,
                });

                // Leaflet's bindPopup auto-attaches a 'click' listener to open the
                // popup. Remove it here (before the selection click handler below
                // is registered) and open/close on hover instead.
                marker.off('click');

                marker.on('mouseover', () => {
                    this.cancelScheduledPopupClose();

                    // Popup content was rendered at page load; regenerate so it
                    // reflects the current search location and road distances.
                    marker.setPopupContent(this.getMarkerTooltipHtml(marker));
                    marker.openPopup();
                });
                // Delay closing so the cursor can travel from the marker into the
                // popup (see initPopupHoverPersistence, which cancels this).
                marker.on('mouseout', () => this.schedulePopupClose(marker));
            }

            marker.addEventListener('click', () => {
                this.selectListingByMarker(marker);
            });

            // Add the marker to the leaflet layer.
            this.allMarkersGroup.addLayer(marker);

            el.setAttribute('data-map-leaflet-id', this.allMarkersGroup.getLayerId(marker));
        });

        this.filteredMarkersGroup = this.allMarkersGroup;

        // Add all markers sub groups to map.
        this.lmap.addLayer(this.filteredMarkersGroup);

        this.initPopupHoverPersistence();
    }

    /**
     * Keeps a hover popup open while the cursor is inside it, so interactive
     * content (e.g. the "Get directions" link) is clickable. Without this the
     * marker's mouseout closes the popup before the cursor can reach it.
     */
    initPopupHoverPersistence() {
        this.lmap.on('popupopen', (event) => {
            const popupEl = event.popup.getElement();
            const popupMarker = event.popup._source;

            if (!popupEl) {
                return;
            }

            popupEl.addEventListener('mouseenter', () => this.cancelScheduledPopupClose());
            // Leaving the box itself dismisses quickly; the longer grace only
            // applies to the marker->box hop (see the marker mouseout handler).
            popupEl.addEventListener('mouseleave', () => this.schedulePopupClose(popupMarker, 80));
        });
    }

    /**
     * Tracks profile click-throughs by pushing a `map_listing_click` custom
     * event to the GTM dataLayer (which forwards to GA4). Covers both the popup
     * "Directions" / "Contact us" buttons and the equivalent "More info" link in
     * the left sidebar, so the same action is measured wherever it is clicked.
     * Delegated on the map root so it also catches popups created after load.
     */
    initClickTracking() {
        if (!this.el) {
            return;
        }

        this.el.addEventListener('click', (event) => {
            // Popup buttons carry their own tracking data attributes.
            const button = event.target.closest('.map__marker-tooltip__btn');

            if (button && this.el.contains(button)) {
                this.pushListingClick(
                    button.getAttribute('data-map-action') || '',
                    button.getAttribute('data-map-listing-name') || '',
                    button.getAttribute('data-map-listing-type') || ''
                );
                return;
            }

            // Sidebar card actions (email / phone / more info / directions).
            // Each carries its own data-map-action; the name and type come from
            // the listing row rather than the link itself.
            const cardAction = event.target.closest('.map__listing__action');

            if (cardAction && this.el.contains(cardAction)) {
                const listingRow = cardAction.closest('.map__listing');
                const titleEl = listingRow ? listingRow.querySelector('.map__listing__title') : null;

                this.pushListingClick(
                    cardAction.getAttribute('data-map-action') || '',
                    titleEl ? titleEl.textContent.trim() : '',
                    listingRow ? (listingRow.dataset.mapItemPostType || '') : ''
                );
            }
        });
    }

    pushListingClick(action, name, type) {
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event: 'map_listing_click',
            map_action: action,
            map_listing_name: name,
            map_listing_type: type,
        });
    }

    cancelScheduledPopupClose() {
        if (this._popupCloseTimer) {
            clearTimeout(this._popupCloseTimer);
            this._popupCloseTimer = null;
        }
    }

    schedulePopupClose(marker, delay = 150) {
        this.cancelScheduledPopupClose();

        this._popupCloseTimer = setTimeout(() => {
            this._popupCloseTimer = null;

            if (marker) {
                marker.closePopup();
            }
        }, delay);
    }

    initListingSelectionSync() {
        if (!this.listingEls || this.listingEls.length === 0) {
            return;
        }

        this.listingEls.forEach((listingEl) => {
            listingEl.addEventListener('click', () => {
                this.selectMarkerByListing(listingEl);
            });
        });
    }

    /**
     * Inits the filter event listeners.
     */
    initSearch() {
        if (!this.searchInput || !this.searchSubmit) {
            return;
        }

        this.searchSubmit.addEventListener('click', async (event) => {
            event.preventDefault();
            event.stopPropagation();

            const searchQuery = this.searchInput?.value;

            // Bail early - invalid query: reset view.
            if (!searchQuery) {
                this.resetMapView();
                return;
            }

            const response = await this.newRequest(searchQuery);

            // Bail early - no valid response found.
            if (!response || !response.results[0]) {
                this.resetMapView();
                console.warn('Invalid response for geocoding query.');
                return;
            }

            const {lat, lng} = response.results[0].geometry.location;

            this.LMAP_DISTANCE_CENTER = new L.LatLng(lat, lng);
            this.hasUserSearchLocation = true;
            this.filterListingsByDistance();
        });
    }

    initDistanceFilter() {
        if (!this.distanceSelect) {
            return;
        }

        this.distanceSelect.addEventListener('change', () => {
            this.filterByDistanceAndPostType();
        });
    }

    initPostTypeFilters() {
        const filterButtons = this.el.querySelectorAll('.map__filter');
        if (!filterButtons || filterButtons.length === 0) {
            return;
        }

        const initiallyActiveButton = [...filterButtons].find((button) => button.classList.contains('map__filter--active'));
        this.setActivePostTypeFilter(initiallyActiveButton ? initiallyActiveButton.dataset.filterValue : '');

        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const filterValue = button.dataset.filterValue || '';

                this.setActivePostTypeFilter(filterValue);
                this.filterByDistanceAndPostType();
            });
        });
    }

    setActivePostTypeFilter(filterValue = '') {
        this.activePostTypeFilter = filterValue;

        const filterButtons = this.el.querySelectorAll('.map__filter');
        filterButtons.forEach((button) => {
            const buttonValue = button.dataset.filterValue || '';
            button.classList.toggle('map__filter--active', buttonValue === this.activePostTypeFilter);
        });
    }

    /**
     * Does a marker pass the active category filter?
     *
     * The distributor map filters by post type; the installer map has a single
     * post type and filters by tier instead ("installer-approved" /
     * "installer-advanced"). Both go through here so the filter bar, the key and
     * the listing/marker filtering stay one mechanism.
     */
    matchesCategoryFilter(marker) {
        const active = this.activePostTypeFilter;

        if (active === '') {
            return true;
        }

        if (active === 'installer-advanced') {
            return marker.options.themeData.advancedInstaller === true;
        }

        if (active === 'installer-approved') {
            return marker.options.themeData.advancedInstaller !== true;
        }

        return marker.options.themeData.postType === active;
    }

    initTablist() {
        if (!this.tablist) {
            return;
        }

        this.updateTabPanelVisibility();

        [...this.tabs].forEach((tab) => {
            tab.addEventListener('click', ({currentTarget}) => {
                const clickedTab = currentTarget;

                if (!clickedTab) {
                    return;
                }

                const panelId = clickedTab.getAttribute('aria-controls');

                if (!panelId) {
                    return;
                }

                this.activateTabByPanelId(panelId);
            });
        });
    }

    activateTabByPanelId(panelId) {
        if (!panelId || !this.tablist) {
            return;
        }

        const targetTab = this.tablist.querySelector(`.map__tab[aria-controls="${panelId}"]`);

        if (!targetTab) {
            return;
        }

        [...this.tabs].forEach((tab) => {
            tab.classList.remove('map__tab--active');
        });

        targetTab.classList.add('map__tab--active');
        this.updateTabPanelVisibility(panelId);

        const panel = this.el.querySelector(`#${panelId}`);

        if (panel && panel.contains(this.mapContainerEl)) {
            this.syncMapViewport();
        }
    }

    updateTabPanelVisibility(forcedPanelId = null) {
        if (!this.tablist || !this.tabPanels || this.tabPanels.length === 0) {
            return;
        }

        if (!this.isMobileViewport()) {
            [...this.tabPanels].forEach((panel) => {
                panel.removeAttribute('hidden');
            });
            return;
        }

        const activeTab = this.tablist.querySelector('.map__tab--active');
        const activePanelId = forcedPanelId || activeTab?.getAttribute('aria-controls') || this.tabPanels[0].id;

        [...this.tabPanels].forEach((panel) => {
            if (panel.id === activePanelId) {
                panel.removeAttribute('hidden');
                return;
            }

            panel.setAttribute('hidden', '');
        });
    }

    syncMapViewport() {
        if (!this.lmap) {
            return;
        }

        if (this.mapPanelEl && this.mapPanelEl.hasAttribute('hidden')) {
            return;
        }

        // Leaflet needs a visible container to compute the correct map center on mobile/tab layouts.
        requestAnimationFrame(() => {
            this.lmap.invalidateSize(false);
        });
    }

    filterListingsByDistance(shouldAdjustMapBounds = true) {
        if (!this.distanceSelect) {
            return;
        }

        // Clean up map.
        this.lmap.removeLayer(this.filteredMarkersGroup);

        // Reset filters markers.
        this.filteredMarkersGroup = new L.FeatureGroup();

        const distance = parseFloat(this.distanceSelect.value) || 0;

        // Process all markers.
        this.allMarkersGroup.eachLayer((marker) => {
            // Updating marker distance data.
            const distanceInMiles = this.calcLatLngDistanceMilesFromMapCenter(marker.getLatLng());
            marker.options.themeData.distanceInMiles = distanceInMiles;
            marker.options.themeData.roadDistanceInMiles = null;
            marker.options.themeData.roadDistanceUnroutable = false;

            if (distance === 0 || distanceInMiles <= distance) {
                this.filteredMarkersGroup.addLayer(marker);
                marker.options.themeData.listingElement.removeAttribute('hidden', '');
                marker.options.themeData.distanceInMiles = distanceInMiles;
            } else {
                marker.options.themeData.listingElement.setAttribute('hidden', '');
            }

            this.updateMarkerDistanceMeta(marker);

            if (marker.getPopup() && marker.isPopupOpen()) {
                marker.setPopupContent(this.getMarkerTooltipHtml(marker));
            }
        });

        this.lmap.addLayer(this.filteredMarkersGroup);

        const filteredLayers = this.filteredMarkersGroup.getLayers();
        const markerCount = filteredLayers.length;

        this.updateResultsCount(markerCount);

        if (shouldAdjustMapBounds) {
            this.fitToResults(filteredLayers);
        }

        this.sortlistingEls(filteredLayers);
        this.updateRoadDistances(filteredLayers);
    }

    filterByDistanceAndPostType(shouldAdjustMapBounds = true) {
        if (!this.distanceSelect) {
            return;
        }

        // Clean up map.
        this.lmap.removeLayer(this.filteredMarkersGroup);

        // Reset filters markers.
        this.filteredMarkersGroup = new L.FeatureGroup();

        const distance = parseFloat(this.distanceSelect.value) || 0;

        // Process all markers.
        this.allMarkersGroup.eachLayer((marker) => {
            // Updating marker distance data.
            const distanceInMiles = this.calcLatLngDistanceMilesFromMapCenter(marker.getLatLng());
            marker.options.themeData.distanceInMiles = distanceInMiles;
            marker.options.themeData.roadDistanceInMiles = null;
            marker.options.themeData.roadDistanceUnroutable = false;

            // Check distance filter
            const passesDistanceFilter = distance === 0 || distanceInMiles <= distance;

            // Check category filter (post type, or installer tier)
            const passesPostTypeFilter = this.matchesCategoryFilter(marker);

            // Show/hide based on both filters
            if (passesDistanceFilter && passesPostTypeFilter) {
                this.filteredMarkersGroup.addLayer(marker);
                marker.options.themeData.listingElement.removeAttribute('hidden', '');
                marker.options.themeData.distanceInMiles = distanceInMiles;
            } else {
                marker.options.themeData.listingElement.setAttribute('hidden', '');
            }

            this.updateMarkerDistanceMeta(marker);
        });

        this.lmap.addLayer(this.filteredMarkersGroup);

        const filteredLayers = this.filteredMarkersGroup.getLayers();
        const markerCount = filteredLayers.length;

        this.updateResultsCount(markerCount);

        if (shouldAdjustMapBounds) {
            this.fitToResults(filteredLayers);
        }

        this.sortlistingEls(filteredLayers);
        this.updateRoadDistances(filteredLayers);
    }

    /**
     * Frames the map on the results.
     *
     * After a user search, fits the searched point plus only the nearest few results.
     * Fitting every visible marker was fine when a site held only its own country's
     * partners, but every locale now carries all of them, so "Any distance" spanned
     * the globe: searching Calais zoomed out to the whole world instead of showing
     * northern France. With no user search, the default locale view still frames
     * everything.
     */
    fitToResults(markers) {
        if (!markers.length) {
            return;
        }

        const bounds = L.latLngBounds();
        bounds.extend(this.LMAP_DISTANCE_CENTER);

        const framed = this.hasUserSearchLocation
            ? [...markers]
                .sort((a, b) => a.options.themeData.distanceInMiles - b.options.themeData.distanceInMiles)
                .slice(0, this.TOP_RESULTS)
            : markers;

        framed.forEach((marker) => bounds.extend(marker.getLatLng()));

        this.lmap.fitBounds(bounds, {maxZoom: this.SEARCH_FIT_MAX_ZOOM});
    }

    calcLatLngDistanceMilesFromMapCenter(latLng) {
        const distance = latLng.distanceTo(this.LMAP_DISTANCE_CENTER);
        const distanceInMiles = Math.round(this.METERS_TO_MILES_RATIO * distance * 100) / 100; // 2 decimal points.

        return distanceInMiles;
    }

    /**
     * Re-ranks the closest filtered listings by driving distance.
     *
     * The straight-line pass has already filtered and sorted everything; this
     * fetches road distances for the nearest listings (the ones users act on)
     * and re-sorts. On any failure the straight-line order simply remains.
     */
    async updateRoadDistances(filteredLayers) {
        if (!this.roadDistancesEndpoint || !this.hasUserSearchLocation || !filteredLayers.length) {
            return;
        }

        const requestId = ++this.roadDistanceRequestId;

        const layers = [...filteredLayers]
            .sort((a, b) => a.options.themeData.distanceInMiles - b.options.themeData.distanceInMiles)
            .slice(0, this.ROAD_DISTANCE_MAX_DESTINATIONS);

        // The matrix endpoint takes ROAD_DISTANCE_LIMIT destinations at a time, so
        // walk the list in chunks. Sent in parallel: they are independent, and doing
        // them in sequence would make the list visibly re-order several times.
        const batches = [];
        for (let i = 0; i < layers.length; i += this.ROAD_DISTANCE_LIMIT) {
            batches.push(layers.slice(i, i + this.ROAD_DISTANCE_LIMIT));
        }

        const settled = await Promise.all(batches.map((batch) => this.fetchRoadDistances(batch)));

        // Bail early - a newer search has superseded this request.
        if (requestId !== this.roadDistanceRequestId) {
            return;
        }

        let anyBatchSucceeded = false;

        settled.forEach((results, batchIndex) => {
            // A failed batch leaves its markers on straight-line distance, which is
            // the safe fallback. Only a batch that came back can tell us a specific
            // destination is genuinely unreachable.
            if (!results || !Array.isArray(results.distances)) {
                return;
            }

            // The endpoint seeds every entry as null and fills in what the Routes
            // API returned, so a null means "no distance". A batch where nothing at
            // all resolved is far more likely an upstream problem than a whole page
            // of genuinely unreachable branches, so leave that batch on
            // straight-line rather than hiding valid results.
            const resolved = results.distances.filter((r) => r && Number.isFinite(r.meters)).length;

            if (resolved === 0) {
                return;
            }

            anyBatchSucceeded = true;

            batches[batchIndex].forEach((layer, index) => {
                const result = results.distances[index];

                if (result && Number.isFinite(result.meters)) {
                    layer.options.themeData.roadDistanceInMiles =
                        Math.round(this.METERS_TO_MILES_RATIO * result.meters * 100) / 100;
                } else {
                    layer.options.themeData.roadDistanceUnroutable = true;
                }

                this.updateMarkerDistanceMeta(layer);

                if (layer.getPopup() && layer.isPopupOpen()) {
                    layer.setPopupContent(this.getMarkerTooltipHtml(layer));
                }
            });
        });

        if (!anyBatchSucceeded) {
            return;
        }

        // Road distances can push a result outside the chosen radius, so the radius
        // has to be re-applied before re-ordering.
        this.applyRoadDistanceRadius();

        this.sortlistingEls(this.filteredMarkersGroup.getLayers());
    }

    /**
     * One matrix request. Resolves to the parsed body, or null on any failure so the
     * caller can tell a failed batch apart from an unreachable destination.
     */
    async fetchRoadDistances(batch) {
        const destinations = batch.map((layer) => {
            const latLng = layer.getLatLng();
            return {lat: latLng.lat, lng: latLng.lng};
        });

        try {
            const response = await fetch(this.roadDistancesEndpoint, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    origin: {
                        lat: this.LMAP_DISTANCE_CENTER.lat,
                        lng: this.LMAP_DISTANCE_CENTER.lng,
                    },
                    destinations,
                }),
            });

            if (!response.ok) {
                throw Error(response.status);
            }

            return await response.json();
        } catch (e) {
            console.log(e);

            return null;
        }
    }

    /**
     * Drops results that the chosen radius only admitted on straight-line distance.
     *
     * This is what stops a branch across a sea counting as nearby: it is inside the
     * radius as the crow flies but well outside it by road.
     */
    applyRoadDistanceRadius() {
        const distance = this.distanceSelect ? parseFloat(this.distanceSelect.value) || 0 : 0;

        // "Any distance" has no radius to re-apply; ordering alone handles it.
        if (!distance) {
            return;
        }

        let removed = false;

        this.filteredMarkersGroup.getLayers().forEach((marker) => {
            if (this.getSortDistance(marker) <= distance) {
                return;
            }

            this.filteredMarkersGroup.removeLayer(marker);
            marker.options.themeData.listingElement.setAttribute('hidden', '');
            removed = true;
        });

        if (removed) {
            this.updateResultsCount(this.filteredMarkersGroup.getLayers().length);
        }
    }

    /**
     * Result count heading and the empty state.
     */
    updateResultsCount(markerCount) {
        if (this.listingsHeading) {
            this.listingsHeading.textContent = markerCount === 1
                ? `Displaying: ${markerCount} result`
                : `Displaying: ${markerCount} results`;
        }

        if (!this.listingContainer) {
            return;
        }

        this.listingContainer.classList.toggle('no-results', markerCount === 0);
    }

    /**
     * The distance used for ordering and for the radius filter: road distance when
     * known, otherwise straight-line distance.
     *
     * A branch the routing service could not reach by road sorts last and fails the
     * radius, rather than falling back to its straight-line figure. Without that, an
     * unreachable branch across water looks like the closest result on the list.
     */
    getSortDistance(marker) {
        const data = marker.options.themeData;

        if (data.roadDistanceUnroutable) {
            return Infinity;
        }

        return Number.isFinite(data.roadDistanceInMiles)
            ? data.roadDistanceInMiles
            : data.distanceInMiles;
    }

    sortlistingEls(filteredLayers) {
        // Grade results (brief §2/§3): Experience Centres within range first,
        // then preferred stockists, then everything else — each ordered by
        // distance (road distance when known, else straight-line).
        const ordered = [...filteredLayers].sort((a, b) => {
            const rankDiff = this.getListingRank(a) - this.getListingRank(b);

            if (rankDiff !== 0) {
                return rankDiff;
            }

            return this.getSortDistance(a) - this.getSortDistance(b);
        });

        this.renderOrderedListings(ordered);
    }

    /**
     * Grade band for a marker's listing: 0 = Experience Centre within range,
     * 1 = preferred stockist, 2 = everything else.
     */
    getListingRank(marker) {
        const data = marker.options.themeData;
        const listingEl = data.listingElement;

        const isExperienceCentre = data.postType === 'experience_centre';
        // data-map-item-priority, not -preferred: the priority band covers a
        // distributor flagged as a preferred stockist AND an installer flagged as
        // Advanced. Reading the distributor-only flag meant Advanced installers were
        // never promoted.
        const isPrioritised = listingEl
            && listingEl.getAttribute('data-map-item-priority') === '1';

        if (isExperienceCentre && this.getSortDistance(marker) <= this.EC_SURFACE_RADIUS_MILES) {
            return 0;
        }

        if (isPrioritised) {
            return 1;
        }

        return 2;
    }

    /**
     * Re-orders the listing DOM, inserts category headings when both Experience
     * Centres and other listings are present, and collapses everything beyond
     * the top results behind the "Show more" toggle (list view only — the map
     * still shows every marker within range).
     */
    renderOrderedListings(orderedLayers) {
        if (!this.listingContainer) {
            return;
        }

        // Clear category headings from the previous render.
        this.listingContainer
            .querySelectorAll('.map__items__category')
            .forEach((el) => el.remove());

        const hasExperienceCentre = orderedLayers
            .some((layer) => layer.options.themeData.postType === 'experience_centre');
        const hasOther = orderedLayers
            .some((layer) => layer.options.themeData.postType !== 'experience_centre');
        const showCategories = hasExperienceCentre && hasOther;

        let lastCategory = null;
        let listingIndex = 0;

        orderedLayers.forEach((layer) => {
            const listingEl = layer.options.themeData.listingElement;

            if (!listingEl) {
                return;
            }

            const isOverflow = listingIndex >= this.TOP_RESULTS;

            const category = layer.options.themeData.postType === 'experience_centre'
                ? 'experience_centre'
                : 'other';

            if (showCategories && category !== lastCategory) {
                const heading = this.createCategoryHeading(category);

                if (isOverflow) {
                    heading.classList.add('map__listing--overflow');
                }

                this.listingContainer.appendChild(heading);
                lastCategory = category;
            }

            listingEl.classList.toggle('map__listing--overflow', isOverflow);
            this.listingContainer.appendChild(listingEl);

            listingIndex += 1;
        });

        this.updateShowMore(listingIndex);
    }

    createCategoryHeading(category) {
        const heading = document.createElement('p');
        heading.className = 'map__items__category';

        heading.textContent = category === 'experience_centre'
            ? 'Experience Centres'
            : 'Stockists & showspaces';

        return heading;
    }

    updateShowMore(totalVisibleCount) {
        if (!this.showMoreButton) {
            return;
        }

        this.currentOverflowCount = Math.max(0, totalVisibleCount - this.TOP_RESULTS);

        // Reset to the collapsed state whenever the result set changes.
        this.listingContainer.classList.remove('map__items--expanded');
        this.showMoreButton.setAttribute('aria-expanded', 'false');

        if (this.currentOverflowCount <= 0) {
            this.showMoreButton.setAttribute('hidden', '');
            return;
        }

        this.showMoreButton.removeAttribute('hidden');
        this.setShowMoreLabel(false);
    }

    setShowMoreLabel(isExpanded) {
        if (!this.showMoreButton) {
            return;
        }

        const labelEl = this.showMoreButton.querySelector('.map__show-more__label')
            || this.showMoreButton;

        labelEl.textContent = isExpanded
            ? 'Show fewer results'
            : `Show ${this.currentOverflowCount} more`;
    }

    initShowMore() {
        this.showMoreButton = this.el.querySelector('.map__show-more');

        if (!this.showMoreButton) {
            return;
        }

        this.showMoreButton.addEventListener('click', () => {
            const isExpanded = this.listingContainer.classList.toggle('map__items--expanded');
            this.showMoreButton.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
            this.setShowMoreLabel(isExpanded);
        });
    }

    updateMarkerDistanceMeta(marker) {
        const listingMetaEl = marker.options.themeData.listingElement.querySelector('.map__listing__meta');
        if (!listingMetaEl) {
            return;
        }

        const roadMiles = marker.options.themeData.roadDistanceInMiles;
        const distMiles = marker.options.themeData.distanceInMiles
        const distText = Number.isFinite(roadMiles)
            ? roadMiles + ' miles by road' // TODO: translate
            : distMiles + ' miles away'; // TODO: translate

        let distanceEl = listingMetaEl.querySelector('.map__listing__distance');
        if (!distanceEl) {
            const newEl = document.createElement('span')
            newEl.classList.add('map__listing__distance');
            listingMetaEl.appendChild(newEl);
            distanceEl = newEl;
        }

        distanceEl.textContent = distText;
    }

    selectListingByMarker(marker, shouldScrollIntoView = true) {
        if (!this.listingContainer) {
            return;
        }

        const markerId = this.allMarkersGroup.getLayerId(marker);

        [...this.listingEls].forEach((listingEl) => {
            listingEl.classList.remove('selected');
        });

        const selectedListingEl = this.listingContainer.querySelector(`[data-map-leaflet-id="${markerId}"]`);

        if (!selectedListingEl) {
            return;
        }

        selectedListingEl.classList.add('selected');

        if (this.isMobileViewport()) {
            const listPanel = selectedListingEl.closest('.map__tab-panel');

            if (listPanel?.id) {
                this.activateTabByPanelId(listPanel.id);
            }
        }

        if (shouldScrollIntoView) {
            requestAnimationFrame(() => {
                selectedListingEl.scrollIntoView({behavior: 'smooth', block: 'nearest'});
            });
        }

        this.highlightMarker(marker);
    }

    selectMarkerByListing(listingEl) {
        if (!this.lmap || !listingEl) {
            return;
        }

        const markerId = parseInt(listingEl.getAttribute('data-map-leaflet-id'), 10);

        if (Number.isNaN(markerId)) {
            return;
        }

        const marker = this.allMarkersGroup.getLayer(markerId);

        if (!marker) {
            return;
        }

        this.selectListingByMarker(marker, false);

        const minimumFocusZoom = 11;
        const targetZoom = Math.max(this.lmap.getZoom(), minimumFocusZoom);

        this.lmap.flyTo(marker.getLatLng(), targetZoom);
    }

    highlightMarker(activeMarker) {
        this.allMarkersGroup.eachLayer((marker) => {
            const markerEl = marker.getElement();

            if (!markerEl) {
                return;
            }

            markerEl.classList.remove('leaflet-marker-icon--selected');
            marker.closePopup();
        });

        if (!activeMarker) {
            return;
        }

        const activeMarkerEl = activeMarker.getElement();

        if (activeMarkerEl) {
            activeMarkerEl.classList.add('leaflet-marker-icon--selected');
        }

        activeMarker.setPopupContent(this.getMarkerTooltipHtml(activeMarker));
        activeMarker.openPopup();
    }

    getMarkerTooltipHtml(marker) {
        if (!marker || !marker.options || !marker.options.themeData || !marker.options.themeData.listingElement) {
            return '';
        }

        const listingEl = marker.options.themeData.listingElement;

        const titleEl = listingEl.querySelector('.map__listing__title');
        const addressEl = listingEl.querySelector('.map__listing__address');
        const linkEl = listingEl.querySelector('.map__listing__link');
        const tagEl = listingEl.querySelector('.map__listing__meta .g-tag');

        const title = titleEl ? this.escapeHtml(titleEl.textContent.trim()) : '';
        const address = addressEl ? this.escapeHtml(addressEl.textContent.trim()) : '';
        const tag = tagEl ? this.escapeHtml(tagEl.textContent.trim()) : '';

        const stockEl = listingEl.querySelector('.map__listing__stock');
        const stock = stockEl ? this.escapeHtml(stockEl.textContent.trim()) : '';

        const roadDistanceInMiles = marker.options.themeData.roadDistanceInMiles;
        const distanceInMiles = marker.options.themeData.distanceInMiles;

        let distance = '';

        if (Number.isFinite(roadDistanceInMiles)) {
            distance = `${roadDistanceInMiles} miles by road`;
        } else if (Number.isFinite(distanceInMiles)) {
            distance = `${distanceInMiles} miles away`;
        }

        let linkHref = '';
        let linkText = '';

        if (linkEl) {
            if (linkEl.matches('a')) {
                linkHref = linkEl.getAttribute('href') || '';
                linkText = linkEl.textContent.trim();
            } else {
                const anchorEl = linkEl.querySelector('a');

                if (anchorEl) {
                    linkHref = anchorEl.getAttribute('href') || '';
                    linkText = anchorEl.textContent.trim();
                }
            }
        }

        const safeLinkHref = this.escapeHtml(linkHref);
        const safeLinkText = this.escapeHtml(linkText);

        // Build a Google Maps directions link to this listing. Destination is
        // the marker's own coordinates; when the user has searched a location,
        // pre-fill it as the journey start so directions are ready immediately.
        let directionsHref = '';
        const latLng = marker.getLatLng();

        if (latLng) {
            directionsHref = `https://www.google.com/maps/dir/?api=1&destination=${latLng.lat},${latLng.lng}`;

            if (this.hasUserSearchLocation && this.LMAP_DISTANCE_CENTER) {
                directionsHref += `&origin=${this.LMAP_DISTANCE_CENTER.lat},${this.LMAP_DISTANCE_CENTER.lng}`;
            }
        }

        if (!title && !distance && !address && !safeLinkHref) {
            return '';
        }

        let html = '<div class="map__marker-tooltip__inner">';

        // Badge (e.g. "Premier Stockist") from the listing's taxonomy tag.
        if (tag) {
            html += `<p class="map__marker-tooltip__badge"><span class="map__marker-tooltip__badge-dot" aria-hidden="true"></span>${tag}</p>`;
        }

        if (title) {
            html += `<h4 class="map__marker-tooltip__title">${title}</h4>`;
        }

        if (distance) {
            html += `<p class="map__marker-tooltip__distance">${TOOLTIP_ICONS.pin}<span>${distance}</span></p>`;
        }

        html += '<hr class="map__marker-tooltip__divider" aria-hidden="true">';

        if (address) {
            html += `<p class="map__marker-tooltip__address">${address}</p>`;
        }

        if (stock) {
            html += `<p class="map__marker-tooltip__stock">${stock}</p>`;
        }

        // Actions. The phone number is intentionally not shown; "More info"
        // routes visitors through the listing's own profile page instead.
        // Data attributes feed the click tracking (see initClickTracking).
        const postType = marker.options.themeData && marker.options.themeData.postType
            ? this.escapeHtml(marker.options.themeData.postType)
            : '';
        const trackAttrs = `data-map-listing-name="${title}" data-map-listing-type="${postType}"`;

        html += '<div class="map__marker-tooltip__actions">';

        if (directionsHref) {
            html += `<a class="map__marker-tooltip__btn map__marker-tooltip__btn--primary" href="${directionsHref}" target="_blank" rel="noopener noreferrer" data-map-action="directions" ${trackAttrs}>${TOOLTIP_ICONS.directions}<span class="map__marker-tooltip__btn-text"><span class="map__marker-tooltip__btn-prefix">Get </span>Directions</span></a>`; // TODO: translate
        }

        if (safeLinkHref) {
            html += `<a class="map__marker-tooltip__btn map__marker-tooltip__btn--secondary" href="${safeLinkHref}" data-map-action="contact" ${trackAttrs}><span class="map__marker-tooltip__btn-text">More info</span></a>`; // TODO: translate
        }

        html += '</div>';

        html += '</div>';

        return html;
    }

    escapeHtml(content) {
        if (!content) {
            return '';
        }

        return content
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Helpers
    static getDataFromRowElement(element) {
        return {
            lat: element.dataset.mapItemLat,
            lng: element.dataset.mapItemLng,
            name: element.querySelector('.map__listing__title').textContent,
            postType: element.dataset.mapItemPostType,
        };
    }

    isMobileViewport() {
        return isElementVisible(this.mobileMediaQueryRefEl);
    }

    resetMapView() {
        if (!this.lmap) {
            return;
        }

        this.lmap.setView(new L.LatLng(
            this.LMAP_INITIAL_CENTER[0],
            this.LMAP_INITIAL_CENTER[1]),
            this.LMAP_INITIAL_ZOOM
        );
    }

    async newRequest(data) {
        // Bail early - invalid date or no API key.
        if (!data || !this.googleApiKey) {
            return null;
        }

        const normalizedData = data.trim();

        if (!normalizedData) {
            return null;
        }

        // Run the API request because there is no cached result available.
        let countryCode = (this.localeCountryCode || 'gb').toUpperCase();

        // Guernsey (GG) and Jersey (JE) are Crown Dependencies, not part of
        // Great Britain (GB). Bias towards them explicitly so Google geocodes
        // them correctly instead of resolving to a mainland GB location.
        if (countryCode === 'GB') {
            countryCode = this.getChannelIslandCountryCode(normalizedData) || countryCode;
        }

        // Bias the geocode to the current locale's country, but do NOT restrict
        // to it. The partner directory is global: every locale lists all
        // partners worldwide, so a hard `components=country:` filter made
        // overseas records unreachable — searching "Bordeaux" on /en-gb/ was
        // forced to return a Great Britain match and landed in Lanarkshire.
        // `region` is a preference, so "Manchester" still resolves to England
        // for a UK visitor while "Bordeaux" correctly resolves to France.
        const regionBias = countryCode.toLowerCase();

        const response = fetch(`https://maps.googleapis.com/maps/api/geocode/json?region=${regionBias}&address=${encodeURI(normalizedData)}&key=${this.googleApiKey}`)
            .then((r) => {
                if (!r.ok) {
                    throw Error(r);
                }
                return r.json();
            })
            .catch((e) => {
                // Log error responses.
                console.log(e);
            });

        return response;
    }

    /**
     * Returns the ISO country code for Channel Island / Isle of Man queries
     * when the base locale is GB, or null if the query is not for these territories.
     * Google treats GG (Guernsey), JE (Jersey) and IM (Isle of Man) as separate
     * country codes from GB, so a gb region bias alone pulls these queries to
     * the mainland. Returning the specific code biases them correctly.
     */
    getChannelIslandCountryCode(query) {
        if (/^GY\d/i.test(query) || /\bguernsey\b/i.test(query)) return 'GG';
        if (/^JE\d/i.test(query) || /\bjersey\b/i.test(query)) return 'JE';
        if (/^IM\d/i.test(query) || /\bisle\s+of\s+man\b/i.test(query)) return 'IM';
        return null;
    }
}

export default Map;
