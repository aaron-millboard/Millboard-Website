import L from 'leaflet/dist/leaflet.js';
/* eslint-disable no-underscore-dangle */
/* eslint-disable no-nested-ternary */

/**
 * Didn't fancy including an outdated node module for the sake of one JS file.
 * So rejigged the functionality into something class based.
 * The repo for what we want: https://github.com/ghybs/Leaflet.FeatureGroup.SubGroup
 * Specific functionality we want: https://github.com/ghybs/Leaflet.FeatureGroup.SubGroup/blob/master/src/subgroup.js
 * Misc info from LeafletJS on extending: https://leafletjs.com/examples/extending/extending-1-classes.html
 */
export default class LFeatureGroupSubGroup extends L.FeatureGroup {
    // constructor(parentGroup, options) {
    //     super(parentGroup, options);
    // }

    /**
     * Instantiates a SubGroup.
     * @param parentGroup (L.LayerGroup) (optional)
     * @param layersArray (L.Layer[]) (optional)
     */
    initialize(parentGroup, layersArray) {
        L.FeatureGroup.prototype.initialize.call(this, layersArray);

        this.setParentGroup(parentGroup);
    }

    /**
     * Changes the parent group into which child markers are added to /
     * removed from.
     * @param parentGroup (L.LayerGroup)
     * @returns {SubGroup} this
     */
    setParentGroup(parentGroup) {
        const pgInstanceOfLG = parentGroup instanceof L.LayerGroup;

        this._parentGroup = parentGroup;

        // onAdd
        this.onAdd = pgInstanceOfLG
            ? typeof parentGroup.addLayers === 'function'
                ? this._onAddToGroupBatch
                : this._onAddToGroup
            : this._onAddToMap;

        // onRemove
        this.onRemove = pgInstanceOfLG
            ? typeof parentGroup.removeLayers === 'function'
                ? this._onRemoveFromGroupBatch
                : this._onRemoveFromGroup
            : this._onRemoveFromMap;

        // addLayer
        this.addLayer = pgInstanceOfLG ? this._addLayerToGroup : this._addLayerToMap;

        // removeLayer
        this.removeLayer = pgInstanceOfLG ? this._removeLayerFromGroup : this._removeLayerFromMap;

        return this;
    }

    /**
     * Removes the current sub-group from map before changing the parent
     * group. Re-adds the sub-group to map if it was before changing.
     * @param parentGroup (L.LayerGroup)
     * @returns {SubGroup} this
     */
    setParentGroupSafe(parentGroup) {
        const map = this._map;

        if (map) {
            map.removeLayer(this);
        }

        this.setParentGroup(parentGroup);

        if (map) {
            map.addLayer(this);
        }

        return this;
    }

    /**
     * Returns the current parent group.
     * @returns {*}
     */
    getParentGroup() {
        return this._parentGroup;
    }

    // For parent groups with batch methods (addLayers and removeLayers)
    // like MarkerCluster.
    _onAddToGroupBatch(map) {
        const layersArray = this.getLayers();

        this._map = map;
        this._parentGroup.addLayers(layersArray);
    }

    _onRemoveFromGroupBatch() {
        const layersArray = this.getLayers();

        this._parentGroup.removeLayers(layersArray);
        this._map = null;
    }

    // For other parent layer groups.
    _onAddToGroup(map) {
        const parentGroup = this._parentGroup;

        this._map = map;
        this.eachLayer(parentGroup.addLayer, parentGroup);
    }

    _onRemoveFromGroup() {
        const parentGroup = this._parentGroup;

        this.eachLayer(parentGroup.removeLayer, parentGroup);
        this._map = null;
    }

    // Defaults to standard FeatureGroup behaviour when parent group is not
    // specified or is not a type of LayerGroup.
    _onAddToMap() {
        return L.FeatureGroup.SubGroup.prototype.onAdd.call(this);
    }

    _onRemoveFromMap() {
        return L.FeatureGroup.SubGroup.prototype.onRemove.call(this);
    }

    _addLayerToGroup(layer) {
        if (this.hasLayer(layer)) {
            return this;
        }

        layer.addEventParent(this);

        const id = this.getLayerId(layer);

        this._layers[id] = layer;

        if (this._map) {
            // Add to parent group instead of directly to map.
            this._parentGroup.addLayer(layer);
        }

        return this.fire('layeradd', { layer });
    }

    _removeLayerFromGroup(origLayer) {
        let layer = origLayer;

        // If unknown layer, skip.
        if (!this.hasLayer(layer)) {
            return this;
        }

        // Retrieve the layer id.
        const id = layer in this._layers ? layer : this.getLayerId(layer);

        // Retrieve the layer from this._layer.
        layer = this._layers[id];

        // Unregister from events parent.
        layer.removeEventParent(this);

        if (this._map && layer) {
            // Remove from parent group instead of directly from map.
            this._parentGroup.removeLayer(layer);
        }

        delete this._layers[id];

        return this.fire('layerremove', { layer });
    }

    // Defaults to standard FeatureGroup behaviour when parent group is not
    // specified or is not a type of LayerGroup.
    _addLayerToMap(layer) {
        return L.FeatureGroup.SubGroup.prototype.addLayer.call(this, layer);
    }

    _removeLayerFromMap(layer) {
        return L.FeatureGroup.SubGroup.prototype.removeLayer.call(this, layer);
    }
}
