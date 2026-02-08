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
        this.searchInput = this.el.querySelector('#map--map--search_input');

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

        this.LMAP_MARKER_WIDTH = 31;
        this.LMAP_MARKER_HEIGHT = 40;

        this.allMarkersFlatGroup = new L.FeatureGroup();
        this.filteredMarkersFlatGroup = new L.FeatureGroup();

        if (typeof L === 'object') {
            this.init();
        }
    }

    init() {
        this.LMAP_DISTANCE_CENTER = L.latLng(52.3, -1.4);

        this.initLeafletMap();
        this.initLeafletMarkers();
        // this.initFilters();
        // this.initSearch();

        // Some leaflet related stuff...
        // L.DomEvent.disableClickPropagation(this.filtersContainerEl);
        // // L.DomEvent.disableScrollPropagation(this.filtersContainerEl);

        // // Init UI interactivity.
        // this.initFilterClearApplyButtons();

        // // Do a filter/search.
        // this.triggerFormFilter(false);
    }

    /**
     * Handles filtering the data.
     */
    triggerFormFilter(updateBounds = true) {
        // Get form data.
        const formData = new FormData(this.filterFormEl);
        const selectedFilterSlugsByFiltergroup = {};
        // let totalFiltersApplied = 0; // Increments with each filter added.
        let totalFilterGroupsApplied = 0;

        // Handle search.
        if (formData.has('map--map--search')) {
            const searchTerm = formData.get('map--map--search');
            if (searchTerm) {
                this.handleSearch(searchTerm.toLowerCase());

                return;
            }
        }

        // Find the value that has been filtered by.
        // Loop over the filter slugs by filter groups.
        // Check the form data submited contains one of our filter groups.
        [...Object.keys(this.appliedFilterSlugsByFiltergroup)].forEach((filtergroupIDPart) => {
            const filtergroupID = `map-filter-${filtergroupIDPart}`;

            if (formData.has(filtergroupID)) {
                selectedFilterSlugsByFiltergroup[filtergroupID] = formData.getAll(filtergroupID);

                // If the formData value is not empty, incremenet our groups count by 1.
                if (formData.get(filtergroupID).length > 0) {
                    totalFilterGroupsApplied += 1;
                }
            } else {
                selectedFilterSlugsByFiltergroup[filtergroupID] = [];
            }

            // An incrementor for each filter added.
            // totalFiltersApplied += selectedFilterSlugsByFiltergroup[filtergroupID].length;
        });

        // We now have a bunch of things we want to filter by.
        // Clear existing layers.
        // this.filteredMarkersSubGroup.clearLayers();
        this.filteredMarkersFlatGroup.clearLayers();
        const tableResults = [];

        // Loop over all our markers.
        // this.allMarkersSubGroup.getLayers().forEach((markerLayer) => {
        this.allMarkersFlatGroup.getLayers().forEach((markerLayer) => {
            // console.log('markerLayer options', markerLayer.options);

            let countOfshowForThisFilterNameForAllFilters = 0;

            // For marker X
            // Check each of the selected filters.
            // Loop over the filters selected by the user.
            // Gather data on what has been added.
            Object.keys(selectedFilterSlugsByFiltergroup).forEach((filterName) => {
                // Loop over one filter name.
                // This is an OR relationship - we must find one match.
                let showForThisFilterName = false;
                const chosenFilterValues = selectedFilterSlugsByFiltergroup[filterName];
                const filterNameShort = filterName.replace('map-filter-', '');
                const markerMetaData = markerLayer.options.options;

                // Loop over the array of chosen values for this filter - e.g. "country".
                if (chosenFilterValues.length > 0) {
                    chosenFilterValues.forEach((filterValue) => {
                        if (markerMetaData[filterNameShort] === filterValue) {
                            showForThisFilterName = true;
                        } else if (filterNameShort === 'energytype') {
                            // Handle energy type - now an array of energy types (wind, solar).
                            const jsonString = JSON.parse(markerMetaData[filterNameShort]);

                            // Handle differences between slug and value.
                            let filterValueToCheck = filterValue;
                            if (filterValue === 'waste-to-energy') {
                                filterValueToCheck = 'waste_to_energy';
                            }
                            if (jsonString.includes(filterValueToCheck)) {
                                showForThisFilterName = true;
                            }
                        }
                    });
                }

                if (showForThisFilterName) {
                    countOfshowForThisFilterNameForAllFilters += 1;
                }
            });

            if (countOfshowForThisFilterNameForAllFilters === totalFilterGroupsApplied) {
                // this.filteredMarkersSubGroup.addLayer(markerLayer);
                this.filteredMarkersFlatGroup.addLayer(markerLayer);
                tableResults.push(markerLayer.options.themeData.tableDataRowElement);
            }
        });

        // this.lmap.addLayer(this.filteredMarkersSubGroup);
        this.lmap.addLayer(this.filteredMarkersFlatGroup);

        // if (this.filteredMarkersSubGroup.getLayers().length > 0) {
        if (this.filteredMarkersFlatGroup.getLayers().length > 0 && updateBounds) {
            const filteredMarkersBounds = this.filteredMarkersFlatGroup.getBounds();
            this.lmap.setMaxZoom(6.5); // Limit inital zoom
            this.lmap.fitBounds(filteredMarkersBounds);

            // Return max zoom to its initial value.
            setTimeout(() => {
                this.lmap.setMaxZoom(this.LMAP_MAX_ZOOM);
            }, 100);
        } else if (!updateBounds) {
            // Force initial map position.
            setTimeout(() => {
                // this.lmap.setZoom(4.199999999999999);
                this.lmap.setZoom(4.55);
            }, 100);
        }
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
            const textContentOfTableRow = markerLayer.options.themeData.tableDataRowElement.textContent
                .trim()
                .toLowerCase();

            if (textContentOfTableRow.indexOf(searchTerm) !== -1) {
                // this.filteredMarkersSubGroup.addLayer(markerLayer);
                this.filteredMarkersFlatGroup.addLayer(markerLayer);

                tableResults.push(markerLayer.options.themeData.tableDataRowElement);
            }
        });

        // Update map.
        this.lmap.addLayer(this.filteredMarkersFlatGroup);

        if (this.filteredMarkersFlatGroup.getLayers().length > 0) {
            const filteredMarkersBounds = this.filteredMarkersFlatGroup.getBounds();
            this.lmap.fitBounds(filteredMarkersBounds);
        }
    }

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

    /**
     * Leaflet Markers setup.
     */
    initLeafletMarkers() {
        const markerHoizontalMiddle = this.LMAP_MARKER_WIDTH / 2; // Icon width / 2 to center it.

        this.listingEls.forEach((el) => {
            // Get data.
            const rowData = Map.getDataFromRowElement(el);

            // Get data values.
            const rowLatLng = L.latLng(rowData.lat, rowData.lng);
            const rowLat = parseFloat(rowData.lat);
            const rowLng = parseFloat(rowData.lng);
            const rowTitle = rowData.name;

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
                    tableDataRowElement: el,
                },
            });

            const METERS_TO_MILES_RATIO = 0.000621371;
            const distMeters = rowLatLng.distanceTo(this.LMAP_DISTANCE_CENTER);
            const distMiles = Math.round(METERS_TO_MILES_RATIO * distMeters * 100) / 100; // 2 decimal points.

            const rowMeta = el.querySelector('.map__listing__meta');

            if (rowMeta) {
                const distEl = document.createElement('span');
                distEl.classList.add('map__listing__distance');
                distEl.textContent = distMiles + ' miles'; // TODO: translate
                rowMeta.appendChild(distEl);
            }

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
    initFilters() {
        this.filterEls.forEach((el) => {
            this.markerSubGroupsByFilterableValue[el.value] = new LFeatureGroupSubGroup(this.TEMPmarkersClusterGroup);

            el.addEventListener('change', (event) => {
                event.preventDefault();
                this.triggerFormFilter();
            });
        });
    }

    /**
     * Inits the filter event listeners.
     */
    initSearch() {
        this.searchInput.addEventListener(
            'input',
            debounce(() => {
                this.triggerFormFilter();
                this.filtersExpander.collapse();
            }, 300)
        );
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

    initFilterClearApplyButtons() {
        this.filtersClearButton.addEventListener('click', (e) => {
            e.preventDefault();

            // Reset.
            this.filterFormEl.reset();

            // Do a filter/search.
            this.triggerFormFilter();
        });
    }
}

export default Map;
