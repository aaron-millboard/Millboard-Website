import debounce from 'lodash.debounce';
import L from 'leaflet/dist/leaflet.js';
import LFeatureGroupSubGroup from './Leaflet.FeatureGroup.SubGroup.js';
import isElementVisible from '../../../scripts/helpers/isElementVisible.js';

// https://leafletjs.com/reference.html
class Map {
    constructor(element) {
        // Elements
        this.el = element;

        // Listings.
        this.listingEls = this.el.querySelectorAll('.map__listing');

        // Map container.
        this.mapContainerEl = this.el.querySelector('.map__map-container');

        // Search.
        this.searchInput = this.el.querySelector('#map-search-input');
        this.searchSubmit = this.el.querySelector('.map__search__submit');

        // Distance.
        this.distanceSelect = this.el.querySelector('.map__distance__input');

        this.mobileMediaQueryRefEl = document.querySelector('.site-header__burger');

        this.appliedFilterSlugsByFiltergroup = {};
        this.markerSubGroupsByFilterableValue = {};

        // Constants for the Leaflet map.
        this.LMAP_WORLD_2D_SVG = this.mapContainerEl.dataset.mapWorldSvgUrl;
        this.LMAP_WORLD_2D_SVG_BOUNDS = [
            [85, -169.4],
            [-85, 190.6],
        ];

        // Variables for the Leaflet Map.
        this.LMAP_ZOOM_DELTA = 1.4; // Ws 0.8
        this.LMAP_ZOOM_SNAP = this.LMAP_ZOOM_DELTA;
        this.LMAP_INITIAL_ZOOM = 6; // Was 3.5.
        this.LMAP_MIN_ZOOM = 2.4;
        this.LMAP_MAX_ZOOM = 15; // Was 7.2
        this.LMAP_INITIAL_CENTER = [55, -5]; // Move center to UK.
        this.LMAP_DISTANCE_CENTER = L.latLng(52.3, -1.4);

        this.LMAP_MARKER_WIDTH = 31;
        this.LMAP_MARKER_HEIGHT = 40;

        this.METERS_TO_MILES_RATIO = 0.000621371;

        this.allMarkersFlatGroup = new L.FeatureGroup();
        this.filteredMarkersFlatGroup = new L.FeatureGroup();

        this.googleApiKey = window.params.google_api_key;

        if (typeof L === 'object') {
            this.init();
        }
    }

    init() {
        this.initLeafletMap();
        this.initLeafletMarkers();
        this.initSearch();
    }

    /**
     * Leaflet Map Init.
     */
    initLeafletMap() {
        // https://leafletjs.com/reference.html#map-option
        this.lmap = L.map('leaflet-map-container', {
            center: this.LMAP_INITIAL_CENTER,
            attributionControl: false,
            intertia: false,
            maxBounds: [
                [78, -169.4],
                [-58, 120.6],
            ],
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

        // L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        //     maxZoom: 19,
        // }).addTo(this.lmap);


        // Add leaflet zoom controller.
        // https://leafletjs.com/reference.html#control-zoom
        const lmapZoomControl = L.control.zoom({
            position: 'topright',
        });

        lmapZoomControl.addTo(this.lmap);

        // this.lmap.on('pm:drawstart', function (e) {
        //     console.log('drawstart', e);
        // });
    }

    /**
     * Leaflet Markers setup.
     */
    initLeafletMarkers() {
        const markerHoizontalMiddle = this.LMAP_MARKER_WIDTH / 2; // Icon width / 2 to center it.

        this.listingEls.forEach((el) => {
            // Get data.
            const listingData = Map.getDataFromRowElement(el);

            // Get data values.
            const rowLatLng = L.latLng(listingData.lat, listingData.lng);
            const rowLat = parseFloat(listingData.lat);
            const rowLng = parseFloat(listingData.lng);
            const rowTitle = listingData.name;

            let markerHtml = `<span class="leaflet-marker-icon__icon-container" aria-hidden="true">`;
            markerHtml += `<span class="leaflet-marker-icon__icon"></span>`;
            markerHtml += `<span class="screen-reader-text">${rowTitle}</span>`;
            markerHtml += ' </span>';

            // https://leafletjs.com/reference.html#marker
            const marker = L.marker([rowLat, rowLng], {
                autoPanOnFocus: true,
                icon: L.divIcon({
                    html: markerHtml,
                    iconSize: [this.LMAP_MARKER_WIDTH, this.LMAP_MARKER_HEIGHT],
                    iconAnchor: [markerHoizontalMiddle, this.LMAP_MARKER_HEIGHT], // Icon radius / 2 to center it.
                }),

                // Custom object data.
                themeData: {
                    listingElement: el,
                },
            });

            this.updateMarkerDistanceMeta(marker);

            // Add the marker to the leaflet layer.
            // this.allMarkersSubGroup.addLayer(marker);
            this.allMarkersFlatGroup.addLayer(marker);

            // /* eslint-disable-next-line no-underscore-dangle -- We need this. */
            // el.setAttribute('data-map-leaflet-id', this.allMarkersSubGroup.getLayerId(marker));
            el.setAttribute('data-map-leaflet-id', this.allMarkersFlatGroup.getLayerId(marker));
        });

        // Add all markers sub groups to map.
        // this.lmap.addLayer(this.allMarkersSubGroup);
        this.lmap.addLayer(this.allMarkersFlatGroup);
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
            this.lmap.setView(new L.LatLng(lat, lng), 8);
            this.filterListingsByDistance();
        });
    }

    filterListingsByDistance() {
        if (!this.distanceSelect) {
            return;
        }

        // "Any" distance selected - show all.
        if (!this.distanceSelect.value) {
            [...this.listingEls].forEach((el) => {
                el.removeAttribute('hidden');
            });
        }

        const distance = 0;
        if (this.distanceSelect && this.distanceSelect.value) {
            console.log(this.distanceSelect);
        }
    }

    updateMarkerDistanceMeta(marker) {
        const listingMetaEl = marker.options.themeData.listingElement.querySelector('.map__listing__meta');
        if (!listingMetaEl) {
            return;
        }

        const distMeters = marker.getLatLng().distanceTo(this.LMAP_DISTANCE_CENTER);
        const distMiles = Math.round(this.METERS_TO_MILES_RATIO * distMeters * 100) / 100; // 2 decimal points.
        const distText = distMiles + ' miles'; // TODO: translate

        const distanceEl = listingMetaEl.querySelector('.map__listing__distance');
        if (distanceEl) {
            distanceEl.textContent = distText
            return;
        }

        const newEl = document.createElement('span')
        newEl.classList.add('map__listing__distance');
        newEl.textContent = distText;
        listingMetaEl.appendChild(newEl);
    }

    /**
     * Handles any searches
     *
     * @param searchTerm - The searched for term from the search input.value.
     */
    handleSearch(searchTerm) {
        // Clear existing layers.
        // this.filteredMarkersSubGroup.clearLayers();
        this.filteredMarkersFlatGroup.clearLayers();
        const tableResults = [];

        // Loop over markers and check title for name.
        this.allMarkersFlatGroup.getLayers().forEach((markerLayer) => {
            // this.allMarkersSubGroup.getLayers().forEach((markerLayer) => {
            // const titleOfPlant = markerLayer.options.title.trim().toLowerCase();
            const textContentOfTableRow = markerLayer.options.themeData.listingElement.textContent
                .trim()
                .toLowerCase();

            if (textContentOfTableRow.indexOf(searchTerm) !== -1) {
                // this.filteredMarkersSubGroup.addLayer(markerLayer);
                this.filteredMarkersFlatGroup.addLayer(markerLayer);

                tableResults.push(markerLayer.options.themeData.listingElement);
            }
        });

        // Update map.
        this.lmap.addLayer(this.filteredMarkersFlatGroup);

        if (this.filteredMarkersFlatGroup.getLayers().length > 0) {
            const filteredMarkersBounds = this.filteredMarkersFlatGroup.getBounds();
            this.lmap.fitBounds(filteredMarkersBounds);
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

        // Run the API request because there is no cached result available.
        const response = fetch(`https://maps.googleapis.com/maps/api/geocode/json?address=${encodeURI(data)}&key=${this.googleApiKey}`)
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
