/**
 * Register block styles for core/paragraph block.
 */
wp.domReady(() => {
    wp.blocks.registerBlockStyle('core/paragraph', {
        name: 'typestyle-large',
        label: 'Large',
    });
});
