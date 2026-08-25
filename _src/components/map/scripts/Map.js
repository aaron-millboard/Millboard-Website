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

        // Pin dimensions are per type now, see PIN_SIZES where the markers are built.

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
        // ISO country code of the last search, used to decide whether an appointed
        // market distributor owns that country. Empty until the visitor searches.
        this.searchedCountryCode = '';
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
        // Abort a matrix request that hangs, so the listing order is never waiting on
        // a call that will never answer. Generous on purpose: a cold search on a slow
        // host can take ten seconds or more, and cutting it short only to re-sort when
        // it finally lands is exactly the visible reorder this is meant to avoid.
        this.ROAD_DISTANCE_REQUEST_TIMEOUT_MS = 20000;
        this.showMoreButton = null;
        this.currentOverflowCount = 0;
        // The one prioritised result promoted above the distance ordering. Set per sort,
        // because which one is nearest depends on where the visitor searched.
        this.nearestPrioritised = null;

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
        this.searchedCountryCode = this.countryCodeFromGeocode(response);
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
// Installer -> installer-marker.svg
// Experience Centre -> experience_centre-marker.svg
// Advanced installers get their own pin, so the map matches the "Approved /
// Advanced" key the same way the distributor types do. The two differ by chevron
// count, two and three, which is how the accreditation itself distinguishes them.
const isAdvancedInstaller = el.dataset.mapItemAdvancedInstaller === '1';
// PHP resolves the pin (including its cache-busting version), so prefer that.
// The fallback keeps older markup working if the attribute is ever absent.
const markerFile = isAdvancedInstaller ? 'installer-advanced' : markerType;
// Every pin ships as SVG. Mirrors marker_icon_url() in PHP.
const SVG_PIN_TYPES = ['distributor', 'experience_centre', 'showroom', 'installer', 'installer-advanced'];
const markerExtension = SVG_PIN_TYPES.includes(markerFile) ? 'svg' : 'png';
const markerIconUrl = el.dataset.mapItemMarkerUrl
    || `/wp-content/themes/millboard/assets/images/icons/${markerFile}-marker.${markerExtension}`;

// Drawn at the size each pin was designed for, rather than squeezed to a common
// height. The badge pins are taller than the shields because they carry the
// wordmark and the chevrons (two for Approved, three for Advanced), and forcing
// them to the shields' height shrank the lettering below legibility.
// [width, height, anchorY]. anchorY is the row the pin's tip actually sits on,
// which is NOT the image height for the shields: they carry two pixels of shadow
// below the point, so anchoring at 42 floated them off their own coordinates.
//
// The installer heights come from the proportions of the Favicon marks plus the
// keyline built onto them, so the pins are never stretched. That artwork has no
// shadow, so the anchor is the bottom edge, which is the point of the last chevron.
// Re-derive these if the artwork is reissued; the build script prints the values.
const PIN_SIZES = {
    'installer': [36, 42, 42],
    'installer-advanced': [36, 48, 48],
};
const [markerWidth, markerHeight, markerAnchorY] = PIN_SIZES[markerFile] || [32, 42, 40];

let markerHtml = `
    <span class="leaflet-marker-icon__icon-container" aria-hidden="true">
        <img
            class="leaflet-marker-icon__icon"
            src="${markerIconUrl}"
            alt="${listingData.postType} marker"
            width="${markerWidth}"
            height="${markerHeight}"
        />
        <span class="screen-reader-text">${listingTitle}</span>
    </span>
`;

            // https://leafletjs.com/reference.html#marker
            const marker = L.marker(listingLatLng, {
                autoPanOnFocus: true,
                icon: L.divIcon({
                    html: markerHtml,
                    iconSize: [markerWidth, markerHeight],
                    // Anchored on the point of the pin: half its own width, and the row
                    // its tip is actually on (see PIN_SIZES).
                    iconAnchor: [Math.round(markerWidth / 2), markerAnchorY],
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
                    // Lifted by THIS pin's height, not a fixed one: the badge pins are
                    // taller than the shields, and a shared offset left the popup
                    // overlapping them.
                    offset: [0, -markerHeight],
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
            this.searchedCountryCode = this.countryCodeFromGeocode(response);
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

                // Deliberately does NOT re-frame the map (hence `false`). Switching from
                // All to Advanced Installers used to fit the map to every remaining
                // marker, which threw the visitor from the town they had zoomed into out
                // to a view of the whole world. Changing a category is a request to
                // filter the list, not to go somewhere else, so the map stays put and
                // only the pins and the cards change.
                this.filterByDistanceAndPostType(false);
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

        // Millboard is sold through appointed distributors in most countries, so a search
        // inside such a territory short-circuits the whole distance pipeline.
        const appointedMarkers = this.findAppointedDistributors();

        if (appointedMarkers.length) {
            this.applyTerritoryMode(appointedMarkers, shouldAdjustMapBounds);

            return;
        }

        this.clearTerritoryNotice();

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
            marker.options.themeData.roadDurationInSeconds = null;
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
        // This path applies no category filter, so the visible set is the in-scope set.
        // No-ops on the installer map, which has a single post type and so no chips.
        this.updateFilterCounts(filteredLayers);

        if (shouldAdjustMapBounds) {
            this.fitToResults(filteredLayers);
        }

        this.orderListings(filteredLayers);
    }

    filterByDistanceAndPostType(shouldAdjustMapBounds = true) {
        if (!this.distanceSelect) {
            return;
        }

        // Millboard is sold through appointed distributors in most countries, so a search
        // inside such a territory short-circuits the whole distance pipeline.
        const appointedMarkers = this.findAppointedDistributors();

        if (appointedMarkers.length) {
            this.applyTerritoryMode(appointedMarkers, shouldAdjustMapBounds);

            return;
        }

        this.clearTerritoryNotice();

        // Clean up map.
        this.lmap.removeLayer(this.filteredMarkersGroup);

        // Reset filters markers.
        this.filteredMarkersGroup = new L.FeatureGroup();

        const distance = parseFloat(this.distanceSelect.value) || 0;

        // Everything inside the distance, before the category filter is applied. The chip
        // counts are built from this, so each chip says how many results IT would give.
        const inRange = [];

        // Process all markers.
        this.allMarkersGroup.eachLayer((marker) => {
            // Updating marker distance data.
            const distanceInMiles = this.calcLatLngDistanceMilesFromMapCenter(marker.getLatLng());
            marker.options.themeData.distanceInMiles = distanceInMiles;
            marker.options.themeData.roadDistanceInMiles = null;
            marker.options.themeData.roadDurationInSeconds = null;
            marker.options.themeData.roadDistanceUnroutable = false;

            // Check distance filter
            const passesDistanceFilter = distance === 0 || distanceInMiles <= distance;

            // Check category filter (post type, or installer tier)
            const passesPostTypeFilter = this.matchesCategoryFilter(marker);

            // Counted before the category filter, so each chip reports its own total
            // rather than the total for whichever chip happens to be active.
            if (passesDistanceFilter) {
                inRange.push(marker);
            }

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
        this.updateFilterCounts(inRange);

        if (shouldAdjustMapBounds) {
            this.fitToResults(filteredLayers);
        }

        this.orderListings(filteredLayers);
    }

    /**
     * Rewrites the number on each filter chip to match what is actually on screen.
     *
     * The chips are rendered server side from the whole dataset and were never updated
     * afterwards, so every search showed the site-wide totals: a search for Canada listed
     * one result above chips reading "All 377 / Distributors 376 / Experience Centres 1".
     *
     * Each chip counts the markers IT would show, not the markers the active chip shows,
     * which is why the count is taken before the category filter is applied. A chip that
     * would return nothing is disabled rather than hidden, so the row does not reflow.
     */
    updateFilterCounts(markersInScope) {
        const buttons = this.el.querySelectorAll('.map__filter');

        if (!buttons.length) {
            return;
        }

        // matchesCategoryFilter() reads the active filter off the instance, so it is
        // swapped per chip and restored. Keeping one matcher means the counts can never
        // drift from the filtering itself.
        const active = this.activePostTypeFilter;

        buttons.forEach((button) => {
            const value = button.dataset.filterValue || '';

            this.activePostTypeFilter = value;

            const count = markersInScope.filter((marker) => this.matchesCategoryFilter(marker)).length;
            const countEl = button.querySelector('.map__filter__count');

            if (countEl) {
                countEl.textContent = String(count);
            }

            button.disabled = count === 0 && value !== active;
            button.classList.toggle('map__filter--empty', count === 0);
        });

        this.activePostTypeFilter = active;
    }

    /**
     * The ISO country code of whatever the visitor searched for.
     *
     * Google returns the country as one of the address components, whatever was typed:
     * a postcode, a town or the country name itself all resolve to the same code. That is
     * what lets one rule cover all three, which is what Sam asked for.
     */
    countryCodeFromGeocode(response) {
        const components = response?.results?.[0]?.address_components || [];
        const country = components.find((component) => (component.types || []).includes('country'));

        // The readable name comes from the same component, so the banner can say
        // "Belgium" without shipping a code-to-name list to the browser.
        this.searchedCountryName = country?.long_name || '';

        return country?.short_name ? country.short_name.toUpperCase() : '';
    }

    /**
     * Every appointed distributor covering the searched country.
     *
     * Millboard is sold through an appointed distributor in most countries, so a search
     * there should return that partner rather than a list of branches over the border.
     * Territory is held per distributor as country codes, because it does not follow the
     * address: the records covering Belgium sit in the Netherlands, and Luxembourg,
     * Andorra, Latvia and Estonia have no branch of their own at all.
     *
     * Returns an array rather than one match on purpose. Benelux is served by two
     * Wooddeck entities, both appointed for the same three countries, and picking the
     * first would hide one of them at random depending on marker order.
     *
     * @return array Markers, empty when the country has no appointed distributor.
     */
    findAppointedDistributors() {
        if (!this.searchedCountryCode) {
            return [];
        }

        return this.allMarkersGroup.getLayers().filter((marker) => {
            const listingEl = marker.options.themeData.listingElement;
            const territory = listingEl ? (listingEl.dataset.mapItemTerritory || '') : '';

            return territory
                .split(',')
                .some((code) => code.trim().toUpperCase() === this.searchedCountryCode);
        });
    }

    /**
     * Substitutes values into a translated string held on the element.
     *
     * The sentence itself is rendered by PHP into data-template so it goes through
     * translation, since these blocks appear on all six locales. Handles the numbered
     * placeholders (%1$s) that let a translator reorder the values, and the plain %s
     * form for single-value strings.
     */
    fillTemplate(el, ...values) {
        const template = el.dataset.template;

        if (!template) {
            return;
        }

        let index = 0;

        el.textContent = template
            .replace(/%(\d+)\$s/g, (match, position) => values[Number(position) - 1] ?? '')
            .replace(/%s/g, () => values[index++] ?? '');
    }

    renderTerritoryNotice(appointedMarkers) {
        // Every match covers the searched country, and in practice they share a
        // territory: the two Wooddeck entities are both appointed for the same three
        // countries. So the first one's list describes the territory for all of them.
        const listingEl = appointedMarkers[0].options.themeData.listingElement;
        const territoryNames = listingEl ? (listingEl.dataset.mapItemTerritoryNames || '') : '';
        const country = this.searchedCountryName || this.searchedCountryCode;

        const banner = this.el.querySelector('[data-map-territory-banner]');

        if (banner) {
            const countryEl = banner.querySelector('[data-map-territory-country]');

            if (countryEl) {
                countryEl.textContent = country ? `${country} \u{00B7} ` : '';
            }

            banner.hidden = false;
        }

        const subheading = this.el.querySelector('[data-map-territory-subheading]');

        if (subheading && territoryNames) {
            this.fillTemplate(subheading, territoryNames);
            subheading.hidden = false;
        }

        const note = this.el.querySelector('[data-map-territory-note]');

        if (note) {
            const textEl = note.querySelector('[data-map-territory-note-text]');

            // Deliberately describes the territory rather than naming the partner.
            // Benelux returns two Wooddeck entities, so a sentence built around one name
            // would either hide the other or need singular and plural forms in all six
            // locales for no real gain.
            if (textEl && territoryNames) {
                this.fillTemplate(textEl, territoryNames);
            }

            note.hidden = false;
        }

        this.el.classList.add('map--territory');
    }

    /**
     * Puts the finder back to normal results.
     */
    clearTerritoryNotice() {
        ['[data-map-territory-banner]', '[data-map-territory-subheading]', '[data-map-territory-note]']
            .forEach((selector) => {
                const el = this.el.querySelector(selector);

                if (el) {
                    el.hidden = true;
                }
            });

        this.el.classList.remove('map--territory');
    }

    /**
     * Shows the appointed distributor on its own for a search inside its territory.
     *
     * Confirmed behaviour: the distance filter is ignored entirely, and branches in
     * neighbouring countries are not listed even where they are closer by road. Road
     * distances are not fetched either, since there is one result and nothing to rank,
     * which also saves a billed request.
     */
    applyTerritoryMode(appointedMarkers, shouldAdjustMapBounds = true) {
        this.lmap.removeLayer(this.filteredMarkersGroup);
        this.filteredMarkersGroup = new L.FeatureGroup();

        this.allMarkersGroup.eachLayer((marker) => {
            const listingEl = marker.options.themeData.listingElement;
            const isAppointed = appointedMarkers.includes(marker);

            /**
             * No distance in territory mode.
             *
             * It used to be measured anyway "so the card can show it", but the map centre
             * after a country search is the country's centroid, not anywhere the visitor
             * is. Searching Canada put "1396.85 miles away" on the one distributor being
             * recommended, directly under a note saying the distance filter was not used.
             * A number measured from the middle of a country tells the visitor nothing,
             * and reads as though the partner is unhelpfully far away.
             */
            marker.options.themeData.distanceInMiles = null;
            marker.options.themeData.roadDistanceInMiles = null;
            marker.options.themeData.roadDurationInSeconds = null;
            marker.options.themeData.roadDistanceUnroutable = false;

            if (isAppointed) {
                this.filteredMarkersGroup.addLayer(marker);
                listingEl.removeAttribute('hidden');
            } else {
                listingEl.setAttribute('hidden', '');
            }

            this.updateMarkerDistanceMeta(marker);
        });

        this.lmap.addLayer(this.filteredMarkersGroup);
        this.updateResultsCount(appointedMarkers.length);
        this.updateFilterCounts(appointedMarkers);
        this.sortlistingEls(this.filteredMarkersGroup.getLayers());
        this.renderTerritoryNotice(appointedMarkers);

        if (shouldAdjustMapBounds) {
            // Just the pins, per Aaron: no territory outline drawn.
            if (appointedMarkers.length === 1) {
                this.lmap.setView(appointedMarkers[0].getLatLng(), this.SEARCH_FIT_MAX_ZOOM);
            } else {
                const bounds = L.latLngBounds();

                appointedMarkers.forEach((marker) => bounds.extend(marker.getLatLng()));
                this.lmap.fitBounds(bounds, {maxZoom: this.SEARCH_FIT_MAX_ZOOM});
            }
        }
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
    /**
     * Orders the listings once rather than twice.
     *
     * Sorting on straight-line distance and then re-sorting when driving times arrived
     * made the list visibly jump: a Calais search showed a Kent branch at the top, then
     * swapped it for one near Lille a second later. When times are on their way the
     * order is held back until they land, so the list settles in one go.
     *
     * There is deliberately no timer racing the fetch. An earlier version gave up after
     * 3.5 seconds and sorted on straight-line distance, but a cold search on a slow host
     * takes longer than that, so the timer fired, the list sorted, and then the times
     * landed and re-sorted it: the very reorder this is meant to remove. The request
     * itself is bounded instead (see fetchRoadDistances), and it resolves either way, so
     * the sort below runs exactly once whether the times arrived or not.
     *
     * Repeat searches are quick because the endpoint caches each origin-destination pair
     * for 30 days; it is the first search of a new area that waits.
     */
    async orderListings(filteredLayers) {
        if (!this.willFetchRoadDistances(filteredLayers)) {
            this.sortlistingEls(filteredLayers);

            return;
        }

        this.setListingsBusy(true);

        await this.updateRoadDistances(filteredLayers);

        // Sort here too, because updateRoadDistances returns without sorting when a
        // newer search has superseded it, and the list must never be left unsorted.
        this.sortlistingEls(this.filteredMarkersGroup.getLayers());
        this.setListingsBusy(false);
    }

    /**
     * Mirrors the guard at the top of updateRoadDistances, so the caller knows whether
     * driving times are actually coming before it decides to wait for them.
     */
    willFetchRoadDistances(filteredLayers) {
        return Boolean(this.roadDistancesEndpoint)
            && this.hasUserSearchLocation
            && filteredLayers.length > 0;
    }

    setListingsBusy(isBusy) {
        if (!this.listingContainer) {
            return;
        }

        this.listingContainer.classList.toggle('map__items--busy', isBusy);
        this.listingContainer.setAttribute('aria-busy', isBusy ? 'true' : 'false');
    }

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

                    // Travel time is what the ordering actually uses. Road mileage
                    // alone put Kent above Lille for a Calais search, because Google
                    // routes through the Eurotunnel: 52 road miles to Kent against 73
                    // to Lille, but roughly two and a half hours against one.
                    if (Number.isFinite(result.seconds)) {
                        layer.options.themeData.roadDurationInSeconds = result.seconds;
                    }
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
        // has to be re-applied before the ordering runs.
        this.applyRoadDistanceRadius();

        // Deliberately does NOT sort. orderListings, the only caller, sorts once after
        // awaiting this. Sorting here as well rebuilt the listing DOM twice for the same
        // final order, which is the flash this whole change exists to remove.
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

        // fetch has no timeout of its own, and the listing order waits on this, so a
        // request that never answers would leave the list dimmed indefinitely.
        const controller = new AbortController();
        const abortTimer = setTimeout(() => controller.abort(), this.ROAD_DISTANCE_REQUEST_TIMEOUT_MS);

        try {
            const response = await fetch(this.roadDistancesEndpoint, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                signal: controller.signal,
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
        } finally {
            clearTimeout(abortTimer);
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

        // The chip counts were taken from the straight-line set before the routing call,
        // so a result pushed out of the radius by road left them one ahead of the list:
        // Bournemouth at 10 miles listed three installers under a chip reading four.
        // Recounted here through getSortDistance(), the same rule the radius above uses,
        // so the active chip always matches what is on the list. Markers filtered out by
        // category never had a road distance fetched, so they fall back to straight-line,
        // which is the best figure available for them.
        this.updateFilterCounts(
            this.allMarkersGroup.getLayers().filter((marker) => this.getSortDistance(marker) <= distance)
        );
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

    /**
     * Travel time in seconds, or null when it is not known for this marker.
     *
     * This, not mileage, is what "closest" means to someone driving. Road mileage put
     * Kent above Lille for a Calais search because Google routes through the
     * Eurotunnel: 52 road miles to Kent against 73 to Lille, but about two and a half
     * hours against one. Only the priced markers have a time, so ordering falls back
     * to mileage for the rest (see sortlistingEls).
     */
    getSortDuration(marker) {
        const data = marker.options.themeData;

        // An unreachable branch deliberately returns null rather than Infinity, so it
        // drops to the mileage tier where getSortDistance gives it Infinity and it
        // lands last of all. Returning Infinity here would have kept it in the timed
        // tier, ahead of branches that ARE reachable but were not priced.
        if (data.roadDistanceUnroutable) {
            return null;
        }

        return Number.isFinite(data.roadDurationInSeconds) ? data.roadDurationInSeconds : null;
    }

    /**
     * Human travel time, e.g. "2 hr 35 min" or "45 min".
     */
    formatDuration(seconds) {
        if (!Number.isFinite(seconds)) {
            return '';
        }

        const totalMinutes = Math.max(1, Math.round(seconds / 60));
        const hours = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;

        if (!hours) {
            return `${minutes} min`;
        }

        return minutes ? `${hours} hr ${minutes} min` : `${hours} hr`;
    }

    sortlistingEls(filteredLayers) {
        // Which single prioritised result gets promoted. Recomputed per sort because it
        // depends on where the visitor searched.
        this.nearestPrioritised = this.findNearestPrioritised(filteredLayers);

        // Grade results (brief §2/§3): Experience Centres within range first,
        // then the nearest preferred stockist or Advanced installer, then everything
        // else — each ordered by distance (road distance when known, else straight-line).
        const ordered = [...filteredLayers].sort((a, b) => {
            const rankDiff = this.getListingRank(a) - this.getListingRank(b);

            if (rankDiff !== 0) {
                return rankDiff;
            }

            // Travel time first, because that is what "closest" means when driving.
            // Only the priced markers have a time, and those are the nearest by
            // straight line, so anything without one sorts after and falls back to
            // mileage among itself. Mixing the two units in one comparison would be
            // meaningless.
            const durationA = this.getSortDuration(a);
            const durationB = this.getSortDuration(b);

            if (durationA !== null && durationB !== null) {
                return durationA - durationB;
            }

            if (durationA !== null) {
                return -1;
            }

            if (durationB !== null) {
                return 1;
            }

            return this.getSortDistance(a) - this.getSortDistance(b);
        });

        this.renderOrderedListings(ordered);
    }

    /**
     * Is this marker a preferred stockist or an Advanced installer?
     *
     * data-map-item-priority, not -preferred: the priority band covers a distributor
     * flagged as a preferred stockist AND an installer flagged as Advanced. Reading the
     * distributor-only flag meant Advanced installers were never promoted.
     */
    isPrioritisedMarker(marker) {
        const listingEl = marker.options.themeData.listingElement;

        return Boolean(listingEl) && listingEl.getAttribute('data-map-item-priority') === '1';
    }

    /**
     * The closest prioritised result in the current set, or null.
     *
     * Only ONE gets promoted. Promoting all of them buried the genuinely local results:
     * a search for Guildford led with five Advanced installers at 14, 31, 48, 65 and 74
     * miles before an Approved installer 2.7 miles away, and included Advanced installers
     * in France. Dan's call, and plainly right — one nearby Advanced is a recommendation,
     * ten of them ordered by distance is just a different list.
     */
    findNearestPrioritised(markers) {
        let best = null;
        let bestDistance = Infinity;

        markers.forEach((marker) => {
            if (!this.isPrioritisedMarker(marker)) {
                return;
            }

            const distance = this.getSortDistance(marker);

            if (Number.isFinite(distance) && distance < bestDistance) {
                bestDistance = distance;
                best = marker;
            }
        });

        return best;
    }

    /**
     * Grade band for a marker's listing: 0 = Experience Centre within range,
     * 1 = the single nearest preferred stockist or Advanced installer,
     * 2 = everything else, including any other prioritised results.
     */
    getListingRank(marker) {
        const isExperienceCentre = marker.options.themeData.postType === 'experience_centre';

        if (isExperienceCentre && this.getSortDistance(marker) <= this.EC_SURFACE_RADIUS_MILES) {
            return 0;
        }

        if (this.nearestPrioritised && marker === this.nearestPrioritised) {
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
        const distMiles = marker.options.themeData.distanceInMiles;
        const driveTime = this.formatDuration(marker.options.themeData.roadDurationInSeconds);

        // Show the drive time alongside the mileage, because the list is ordered by
        // time. Without it a Calais search reads as though it is out of order: Kent
        // at 52 road miles sits below Lille at 73, which only makes sense once you
        // can see Kent is two and a half hours away and Lille is one.
        let distText;

        if (Number.isFinite(roadMiles) && driveTime) {
            distText = `${roadMiles} miles by road \u{00B7} ${driveTime}`; // TODO: translate
        } else if (Number.isFinite(roadMiles)) {
            distText = `${roadMiles} miles by road`; // TODO: translate
        } else if (Number.isFinite(distMiles)) {
            distText = `${distMiles} miles away`; // TODO: translate
        } else {
            // Territory mode clears the distance deliberately. Without this branch the
            // template literal stringified the null and printed "null miles away".
            distText = '';
        }

        let distanceEl = listingMetaEl.querySelector('.map__listing__distance');

        if (distText === '') {
            if (distanceEl) {
                distanceEl.remove();
            }

            return;
        }

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

        const driveTime = this.formatDuration(marker.options.themeData.roadDurationInSeconds);

        let distance = '';

        if (Number.isFinite(roadDistanceInMiles) && driveTime) {
            distance = `${roadDistanceInMiles} miles by road \u{00B7} ${driveTime}`;
        } else if (Number.isFinite(roadDistanceInMiles)) {
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
