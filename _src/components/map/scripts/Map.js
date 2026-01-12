import debounce from 'lodash.debounce';
import L from 'leaflet/dist/leaflet.js';
import 'leaflet.markercluster/dist/leaflet.markercluster.js';
import LFeatureGroupSubGroup from './Leaflet.FeatureGroup.SubGroup.js';
import isElementVisible from '../../../scripts/helpers/isElementVisible.js';

// https://leafletjs.com/reference.html
// https://github.com/Leaflet/Leaflet.markercluster

class Map {
    constructor(element) {
        // Elements
        this.el = element;

        // Plants.
        this.plantRowEls = this.el.querySelectorAll('tbody tr');

        // Map container.
        this.mapContainerEl = this.el.querySelector('.map__map-container');

        // Filters.
        this.filtersButton = this.el.querySelector('[aria-controls="map-filter"');
        this.filtersExpander = null;
        this.filtersContainerEl = this.el.querySelector('.map__filters');
        this.filterFormEl = this.filtersContainerEl.querySelector('form');
        this.filtergroupEls = this.filterFormEl.querySelectorAll('fieldset[name^="map-filtergroup-"]');
        this.filterEls = this.filterFormEl.querySelectorAll('input[name^="map-filter-"]');
        this.filtersTogglerContextAppliedEl = this.filtersContainerEl.querySelector(
            '.map__map-filters-toggler-context-applied'
        );
        this.filtersClearButton = this.el.querySelector('.map__filters__buttons__clear');

        // Search.
        this.searchInput = this.el.querySelector('#map--map--search_input');

        // Country map.
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
        this.LMAP_INITIAL_ZOOM = 4.22; // Was 3.5.
        this.LMAP_INITIAL_ZOOM = 6; // Was 3.5.
        this.LMAP_MIN_ZOOM = 2.4;
        this.LMAP_MAX_ZOOM = 15; // Was 7.2
        this.LMAP_INITIAL_CENTER = [48.31067378249822, -27.66215289352289]; // Move center to europe.

        this.LMAP_MARKER_RADIUS = 34; // Previously was 26.

        this.allMarkersFlatGroup = new L.FeatureGroup();
        this.filteredMarkersFlatGroup = new L.FeatureGroup();

        if (typeof L === 'object') {
            this.init();
        }
    }

    init() {
        this.filtergroupEls.forEach((el) => {
            const id = el.getAttribute('name').split('map-filtergroup-')[1];

            this.appliedFilterSlugsByFiltergroup[id] = [];
        });

        this.initLeafletMap();
        // this.initLeafletClusterGroups();

        // Use an SVG image for the map rather than tiling functionality to save bytez.
        // const imageUrl = this.LMAP_WORLD_2D_SVG;
        // const imageBounds = L.latLngBounds(this.LMAP_WORLD_2D_SVG_BOUNDS);
        // // const wantedZoom = this.lmap.getBoundsZoom(bounds, true);
        // // const center = bounds.getCenter();
        // // this.lmap.setView(center, wantedZoom);

        // const lmapImageOverlay = L.imageOverlay(imageUrl, imageBounds, {
        //     interactive: false,
        //     crossOrigin: false,
        // });

        // lmapImageOverlay.addTo(this.lmap);

        this.initLeafletMarkers();
        this.initFilters();
        this.initSearch();
        this.initMapPopUp();

        // Some leaflet related stuff...
        L.DomEvent.disableClickPropagation(this.markerPopupEl);
        L.DomEvent.disableScrollPropagation(this.markerPopupEl);
        L.DomEvent.disableClickPropagation(this.filtersContainerEl);
        // L.DomEvent.disableScrollPropagation(this.filtersContainerEl);

        // Adding these as actual Leaflet controls was proving a right faff...
        // this.lmap.getContainer().appendChild(this.filtersContainerEl);
        this.lmap.getContainer().appendChild(this.markerPopupEl);

        // Init UI interactivity.
        this.initFilterClearApplyButtons();
        this.initCountryMapViewButtons();

        // Do a filter/search.
        this.triggerFormFilter(false);
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

            // For marker X (Rene plant)
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

    handleMarkerClick(event) {
        const marker = event.target;
        const rowData = Map.getDataFromRowElement(marker.options.themeData.tableDataRowElement);

        this.markerPopupEl.className = [
            'map__map-marker-popup',
            `map__map-marker-popup--energy-type-${rowData.energyTypeSlug}`,
        ]
            .join(' ')
            .trim();

        const popupHeadingEl = this.markerPopupEl.querySelector('.map__map-marker-popup__heading');
        const popupEnergyTypeEl = this.markerPopupEl.querySelector('.map__map-marker-popup__energy-type');
        const popupSummaryEl = this.markerPopupEl.querySelector('.map__map-marker-popup__summary');

        popupHeadingEl.textContent = rowData.name;
        // Set energy type inner HTML:
        const energyTypes = JSON.parse(rowData.energyTypeSlug);
        let energyTypeHtml = `<span class="map__map-marker-popup__energy-type-name">${
            energyTypes.length > 1 ? 'Technologies' : 'Technology'
        }</span>`;

        energyTypes.forEach((type) => {
            const className = type.replaceAll('_', '-');
            let label = type.replaceAll('_', ' ');
            label = label.charAt(0).toUpperCase() + label.slice(1);

            energyTypeHtml += `<span class="map__icon map__icon--${className}"></span>`;
            energyTypeHtml += `<span class="map__icon__name">${label}</span>`;
        });

        popupEnergyTypeEl.innerHTML = energyTypeHtml;

        // Set row data inner HTML.
        popupSummaryEl.innerHTML = rowData.summaryHTML;

        // this.setActiveMarker(marker);
        this.openMarkerPopup(marker);
    }

    openMarkerPopup(marker) {
        /* eslint-disable-next-line no-underscore-dangle -- There doesn't seem to be a LJS getter for the div. */
        marker._icon.classList.add('is-popped');

        this.activePopupMarker = marker;

        // Crude way to solve this quickly...
        // Basically, SOMETHING is happening at Leaflet's end with the default events
        // when a marker is clicked that stops this happening unless we very slightly delay it with a timeout.
        // setTimeout(() => {
        //     this.markerPopupCloseEl.focus();
        // }, 1);

        this.markerPopupEl.removeAttribute('aria-hidden');
    }

    closeMarkerPopup() {
        if (this.activePopupMarker instanceof L.Marker) {
            /* eslint-disable-next-line no-underscore-dangle -- There doesn't seem to be a LJS getter for the div. */
            this.activePopupMarker._icon.classList.remove('is-popped');
            /* eslint-disable-next-line no-underscore-dangle -- There doesn't seem to be a LJS getter for the div. */
            this.activePopupMarker._icon.focus(); // Focus leaving the popup should close the marker.
            this.activePopupMarker = null;
        }

        this.markerPopupEl.setAttribute('aria-hidden', 'true');
    }

    static getDataFromRowElement(element) {
        return {
            // lat: parseInt(element.dataset.mapItemLatValue, 10),
            // lng: parseInt(element.dataset.mapItemLngValue, 10),
            lat: element.dataset.mapItemLatValue,
            lng: element.dataset.mapItemLngValue,
            name: element.querySelector('th').textContent,
            opstatusName: element.dataset.mapItemOpstatusValueDisplay,
            opstatusSlug: element.dataset.mapItemOpstatusValue,
            summaryHTML: element.querySelector('.map__additional-info-td__main').innerHTML,
            energyTypeName: element.querySelector('[data-map-table-col="energytype"]').textContent,
            energyTypeSlug: element.dataset.mapItemEnergytypeValue,
            countrySlug: element.dataset.mapItemCountryValue.toLowerCase(),
        };
    }

    isMobileViewport() {
        return isElementVisible(this.mobileMediaQueryRefEl);
    }

    /**
     * Set up the map pop up.
     */
    initMapPopUp() {
        this.markerPopupEl = this.el.querySelector('.map__map-marker-popup');
        this.markerPopupCloseEl = this.markerPopupEl.querySelector('.map__map-marker-popup__close');
        this.markerPopupCloseEl.addEventListener('click', () => {
            this.closeMarkerPopup();
        });

        // Add marker el close event listener.
        this.markerPopupEl.addEventListener('focusout', (event) => {
            if (this.markerPopupEl.contains(event.relatedTarget)) {
                return;
            }

            if (this.markerPopupEl === event.relatedTarget) {
                return;
            }

            this.closeMarkerPopup();
        });
    }

    /**
     * Leaflet Markers setup.
     */
    initLeafletMarkers() {
        // const markerSizeMiddle = (this.LMAP_MARKER_RADIUS / 2) * -1; // Icon radius / 2 to center it.
        const markerSizeMiddle = this.LMAP_MARKER_RADIUS / 2; // Icon radius / 2 to center it.
        this.plantRowEls.forEach((el) => {
            // Get data.
            const rowData = Map.getDataFromRowElement(el);

            // Get data values.
            const rowLat = parseFloat(rowData.lat);
            const rowLng = parseFloat(rowData.lng);
            const rowTitle = rowData.name;
            const rowTypeSlug = rowData.energyTypeSlug;
            const energyTypes = JSON.parse(rowTypeSlug);
            const rowOperationalStatusSlug = rowData.opstatusSlug;
            const rowCountrySlug = rowData.countrySlug;

            let markerHtml = `<span class="leaflet-marker-icon__icon-container
            leaflet-marker-icon__icon-container--opstatus-${rowOperationalStatusSlug}--v2
            ${energyTypes.length > 1 ? 'has-multiple' : null}"
            aria-hidden="true">`;
            energyTypes.forEach((type, index) => {
                const className = type.replaceAll('_', '-');

                markerHtml += `<span class="leaflet-marker-icon__icon--v2
                    leaflet-marker-icon__icon--energytype-${className}--v2"></span>`;

                // Screen reader text.
                if (index === 0) {
                    markerHtml += `<span class="screen-reader-text">${rowTitle}</span>`;
                }
            });

            markerHtml += ' </span>';

            // https://leafletjs.com/reference.html#marker
            const marker = L.marker([rowLat, rowLng], {
                // title: rowTitle,
                // alt: rowLabel,
                // autoPanOnFocus: true,
                icon: L.divIcon({
                    html: markerHtml,
                    iconSize: [this.LMAP_MARKER_RADIUS, this.LMAP_MARKER_RADIUS],
                    iconAnchor: [0, markerSizeMiddle], // Icon radius / 2 to center it.
                    tooltipAnchor: [0, this.LMAP_MARKER_RADIUS * -1], // Icon radius / 2 to center it.
                }),
                themeData: {
                    tableDataRowElement: el,
                },
                options: {
                    energytype: rowTypeSlug,
                    plantopstatus: rowOperationalStatusSlug,
                    country: rowCountrySlug,
                },
            });

            /* eslint-disable-next-line no-underscore-dangle -- There doesn't seem to be a LJS getter for the div. */
            // marker._icon.classList.add(`leaflet-marker-icon--opstatus-${rowOperationalStatusSlug}`);

            // https://leafletjs.com/reference.html#layer-bindtooltip
            // https://leafletjs.com/reference.html#tooltip-option
            marker.bindTooltip(rowTitle, {
                direction: 'top',
                offset: [13, 13],
                permanent: false,
                sticky: false,
            });

            if (rowTypeSlug in this.markerSubGroupsByFilterableValue) {
                this.markerSubGroupsByFilterableValue[rowTypeSlug].addLayer(marker);
            }

            if (rowOperationalStatusSlug in this.markerSubGroupsByFilterableValue) {
                this.markerSubGroupsByFilterableValue[rowOperationalStatusSlug].addLayer(marker);
            }

            if (rowCountrySlug in this.markerSubGroupsByFilterableValue) {
                this.markerSubGroupsByFilterableValue[rowCountrySlug].addLayer(marker);
            }

            marker.on('click', this.handleMarkerClick.bind(this));

            // Add the marker to the leaflet layer.
            // this.allMarkersSubGroup.addLayer(marker);
            this.allMarkersFlatGroup.addLayer(marker);

            // /* eslint-disable-next-line no-underscore-dangle -- We need this. */
            // el.setAttribute('data-map-leaflet-id', this.allMarkersSubGroup.getLayerId(marker));
            el.setAttribute('data-map-leaflet-id', this.allMarkersFlatGroup.getLayerId(marker));
        });

        // Add all markers sub groups to map.
        // this.lmap.addLayer(this.allMarkersSubGroup);
        // this.lmap.addLayer(this.allMarkersFlatGroup);
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

        // Working: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'
        // Other EN lang only: https://stackoverflow.com/questions/18589621/setting-map-language-to-english-in-openstreetmap-with-leafletjs
        const mapTileProvider = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png';
        const tileLayer = L.tileLayer(mapTileProvider, {
            maxZoom: 19,
        });
        this.lmap.addLayer(tileLayer);

        // Add leaflet zoom controller.
        // https://leafletjs.com/reference.html#control-zoom
        const lmapZoomControl = L.control.zoom({
            position: 'bottomright',
        });

        lmapZoomControl.addTo(this.lmap);

        // this.lmap.on('pm:drawstart', function (e) {
        //     console.log('drawstart', e);
        // });
    }

    /**
     * Initialise leaflet cluster groups.
     */
    initLeafletClusterGroups() {
        // http://leaflet.github.io/Leaflet.markercluster/#all-options
        // const markerSizeMiddle = this.LMAP_MARKER_RADIUS / 2; // Icon radius / 2 to center it.
        this.TEMPmarkersClusterGroup = L.markerClusterGroup({
            iconCreateFunction(cluster) {
                return L.divIcon({
                    html: `
                        <span class="leaflet-marker-icon__cluster-count">${cluster.getChildCount()}</span>
                        <span class="screen-reader-text">plants in this cluster</span>
                        `,
                    // iconSize: [26, 26],
                    // iconAnchor: [13, 4], // Icon radius / 2, and I'm not 100% sure on 4 to center it.
                    // tooltipAnchor: [13, 13], // Icon radius / 2 to center it.
                    iconSize: [46, 46],
                    iconAnchor: [23, 10], // Icon radius / 2, and I'm not 100% sure on 4 to center it.
                    tooltipAnchor: [23, 23], // Icon radius / 2 to center it.
                });
            },
            showCoverageOnHover: true,
            zoomToBoundsOnClick: true,
            maxClusterRadius: 65, // This is the area with-in-which markers get clustered....
            // Was 46, bumped to cluster all of Italy.
            // This plugin can't handle decimals apparently...
            disableClusteringAtZoom: Math.ceil(this.LMAP_MAX_ZOOM - this.LMAP_ZOOM_DELTA),
            spiderfyOnMaxZoom: false,
            removeOutsideVisibleBounds: true,
        });

        this.allMarkersSubGroup = new LFeatureGroupSubGroup(this.TEMPmarkersClusterGroup);
        this.filteredMarkersSubGroup = new LFeatureGroupSubGroup(this.TEMPmarkersClusterGroup);

        this.lmap.addLayer(this.TEMPmarkersClusterGroup);
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
