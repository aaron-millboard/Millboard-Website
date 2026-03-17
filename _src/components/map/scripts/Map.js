import throttle from 'lodash.throttle';
import L from 'leaflet/dist/leaflet.js';
import { FullScreen } from 'leaflet.fullscreen';
import isElementVisible from '../../../scripts/helpers/isElementVisible.js';

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
        this.LMAP_INITIAL_ZOOM = 6; // Was 3.5.
        this.LMAP_MIN_ZOOM = 2.4;
        this.LMAP_MAX_ZOOM = 15; // Was 7.2
        this.LMAP_INITIAL_CENTER = [55, -5]; // Move center to UK.
        this.LMAP_DISTANCE_CENTER = L.latLng(52.3, -1.4);

        this.localeCountryCode = this.getCountryCodeFromUrl() || 'gb';
        this.urlLocaleLatLng = this.getLatLngFromLocaleCode();
        this.urlLocationQuery = this.getLocationQueryFromUrl();

        if (this.urlLocaleLatLng) {
            this.LMAP_INITIAL_CENTER = [this.urlLocaleLatLng.lat, this.urlLocaleLatLng.lng];
            this.LMAP_DISTANCE_CENTER = this.urlLocaleLatLng;
        }

        this.LMAP_MARKER_WIDTH = 31;
        this.LMAP_MARKER_HEIGHT = 40;

        this.METERS_TO_MILES_RATIO = 0.000621371;

        this.allMarkersGroup = new L.FeatureGroup();
        this.filteredMarkersGroup = new L.FeatureGroup();

        this.googleApiKey = window.params.google_api_key;

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
        this.initTablist();

        this.applyLocationFromUrl();

        if (this.tablist) {
            window.addEventListener('resize', throttle(() => {
                [...this.tabPanels].forEach((panel, index) => {
                    if (this.isMobileViewport() && index > 0) {
                        panel.setAttribute('hidden', '');
                    } else {
                        panel.removeAttribute('hidden');
                    }
                });

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
                this.filterListingsByDistance();
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
            gb: [55, -5],
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

        if (countryBounds) {
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

        const mapTileProvider = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
        const tileLayer = L.tileLayer(mapTileProvider, {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
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

            let markerHtml = `<span class="leaflet-marker-icon__icon-container" aria-hidden="true">`;
            markerHtml += `<span class="leaflet-marker-icon__icon"></span>`;
            markerHtml += `<span class="screen-reader-text">${listingTitle}</span>`;
            markerHtml += ' </span>';

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
                },
            });

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
            this.filterListingsByDistance();
        });
    }

    initDistanceFilter() {
        if (!this.distanceSelect) {
            return;
        }

        this.distanceSelect.addEventListener('change', () => {
            this.filterListingsByDistance();
        });
    }

    initTablist() {
        if (!this.tablist) {
            return;
        }

        if (this.isMobileViewport()) {
            [...this.tabPanels].forEach((panel, index) => {
                if (index > 0) {
                    panel.setAttribute('hidden', '');
                }
            });
        }

        [...this.tabs].forEach((tab) => {
            tab.addEventListener('click', ({currentTarget}) => {
                const clickedTab = currentTarget;

                if (!clickedTab) {
                    return;
                }

                [...this.tabs].forEach((tab) => {
                    tab.classList.remove('map__tab--active')
                });

                clickedTab.classList.add('map__tab--active');
                const panelId = clickedTab.getAttribute('aria-controls');

                if (!panelId) {
                    return;
                }

                if (this.isMobileViewport()) {
                    [...this.tabPanels].forEach((panel) => {
                        panel.setAttribute('hidden', '');
                    });
                }

                const panel = this.el.querySelector(`#${panelId}`);

                if (panel) {
                    panel.removeAttribute('hidden');

                    if (panel.contains(this.mapContainerEl)) {
                        this.syncMapViewport();
                    }
                }
            });
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

        // Start a bounded area.
        const distance = parseFloat(this.distanceSelect.value) || 0;
        const bounds = L.latLngBounds();
        bounds.extend(this.LMAP_DISTANCE_CENTER);

        // Process all markers.
        this.allMarkersGroup.eachLayer((marker) => {
            // Updating marker distance data.
            const distanceInMiles = this.calcLatLngDistanceMilesFromMapCenter(marker.getLatLng());
            marker.options.themeData.distanceInMiles = distanceInMiles;

            if (distance === 0 || distanceInMiles <= distance) {
                this.filteredMarkersGroup.addLayer(marker);
                marker.options.themeData.listingElement.removeAttribute('hidden', '');
                marker.options.themeData.distanceInMiles = distanceInMiles;
                bounds.extend(marker.getLatLng());
            } else {
                marker.options.themeData.listingElement.setAttribute('hidden', '');
            }

            this.updateMarkerDistanceMeta(marker);
        });

        this.lmap.addLayer(this.filteredMarkersGroup);

        const filteredLayers = this.filteredMarkersGroup.getLayers();
        const markerCount = filteredLayers.length;

        // Update listings heading content.
        if (markerCount === 1) {
            this.listingsHeading.textContent = `Displaying: ${markerCount} result`
        } else {
            this.listingsHeading.textContent = `Displaying: ${markerCount} results`
        }

        // Update no content element classes.
        if (markerCount > 0) {
            this.listingContainer.classList.remove('no-results');
        } else {
            this.listingContainer.classList.add('no-results');
        }

        if (shouldAdjustMapBounds) {
            this.lmap.fitBounds(bounds);
        }

        this.sortlistingEls(filteredLayers);
    }

    calcLatLngDistanceMilesFromMapCenter(latLng) {
        const distance = latLng.distanceTo(this.LMAP_DISTANCE_CENTER);
        const distanceInMiles = Math.round(this.METERS_TO_MILES_RATIO * distance * 100) / 100; // 2 decimal points.

        return distanceInMiles;
    }

    sortlistingEls(filteredLayers) {
        // Sort map markers by distance from central point.
        filteredLayers.sort((a, b) => a.options.themeData.distanceInMiles - b.options.themeData.distanceInMiles);

        // Order listings from closest > furthest away.
        filteredLayers.forEach((layer) => {
            if (layer.options.themeData.listingElement) {
                this.listingContainer.appendChild(
                    layer.options.themeData.listingElement
                );
            }
        });
    }

    updateMarkerDistanceMeta(marker) {
        const listingMetaEl = marker.options.themeData.listingElement.querySelector('.map__listing__meta');
        if (!listingMetaEl) {
            return;
        }

        const distMiles = marker.options.themeData.distanceInMiles
        const distText = distMiles + ' miles'; // TODO: translate

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

        if (shouldScrollIntoView) {
            selectedListingEl.scrollIntoView({behavior: 'smooth', block: 'nearest'});
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
        });

        if (!activeMarker) {
            return;
        }

        const activeMarkerEl = activeMarker.getElement();

        if (activeMarkerEl) {
            activeMarkerEl.classList.add('leaflet-marker-icon--selected');
        }
    }

    // Helpers
    static getDataFromRowElement(element) {
        return {
            lat: element.dataset.mapItemLat,
            lng: element.dataset.mapItemLng,
            name: element.querySelector('.map__listing__title').textContent,
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
        const countryCode = (this.localeCountryCode || 'gb').toUpperCase();
        const response = fetch(`https://maps.googleapis.com/maps/api/geocode/json?components=country:${countryCode}&address=${encodeURI(normalizedData)}&key=${this.googleApiKey}`)
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
}

export default Map;
