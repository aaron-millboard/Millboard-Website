/**
 * Unregister default styles for core/button block.
 */
wp.domReady(() => {
    // wp.blocks.unregisterBlockStyle('core/button', 'default'); // Keep default style
    wp.blocks.unregisterBlockStyle('core/button', 'fill');
    // wp.blocks.unregisterBlockStyle('core/button', 'outline'); // Keep outline style
    wp.blocks.unregisterBlockStyle('core/button', 'squared');
});
