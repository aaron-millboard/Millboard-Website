/**
 * Register block styles for core/heading block.
 */
wp.domReady(() => {
    wp.blocks.registerBlockStyle('core/heading', {
        name: 'typestyle-h2',
        label: 'H2 Appearance',
    });

    wp.blocks.registerBlockStyle('core/heading', {
        name: 'typestyle-h3',
        label: 'H3 Appearance',
    });

    wp.blocks.registerBlockStyle('core/heading', {
        name: 'typestyle-h4',
        label: 'H4 Appearance',
    });

    wp.blocks.registerBlockStyle('core/heading', {
        name: 'typestyle-h5',
        label: 'H5 Appearance',
    });
});
