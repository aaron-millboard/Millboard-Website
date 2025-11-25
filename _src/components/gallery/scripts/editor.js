/* eslint-disable no-underscore-dangle, func-names */
(function (hooks, i18n) {
    const { __ } = i18n;

    const targetBlocks = new Set(['acf/gallery']);

    function customiseListViewLabel(settings, name) {
        if (!targetBlocks.has(name)) return settings;

        // Add (or override) a dynamic List View label
        return {
            ...settings,
            __experimentalLabel: (attributes, { context }) => {
                // Only customize for List View; fallback to default for other contexts
                if (context !== 'list-view') {
                    // If block already has a label, use it; else use block title
                    return typeof settings.__experimentalLabel === 'function'
                        ? settings.__experimentalLabel(attributes, { context })
                        : settings.title;
                }

                // Pull the ACF field value. ACF v2 stores field values in attributes.data.<fieldName>
                const pattern = attributes?.data?.pattern ?? attributes?.pattern ?? '—';

                // Optional: normalize machine values to readable labels
                const map = {
                    'pattern-1': __('Pattern 1', 'granola'),
                    'pattern-2': __('Pattern 2', 'granola'),
                    grid: __('Grid', 'granola'),
                };

                const readableLabel = map[String(pattern).toLowerCase()] || pattern;

                return `Gallery | ${readableLabel}`;
            },
        };
    }

    hooks.addFilter('blocks.registerBlockType', 'granola/gallery/listview-label', customiseListViewLabel);
})(window.wp.hooks, window.wp.i18n);
/* eslint-enable no-underscore-dangle, func-names */
