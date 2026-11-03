// Loads the original viewer (a plain script that looks for an <application>
// element on the page) only when its block is close to the screen.
const start = (block) => {
    const config = JSON.parse(block.dataset.sampleInspector || '{}');
    const stage = block.querySelector('[data-sample-inspector-stage]');

    if (!config.script || !stage) {
        return;
    }

    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = config.style;
    document.head.appendChild(link);

    const viewer = document.createElement('application');
    viewer.setAttribute('data-path-asset', config.assetPath);
    viewer.setAttribute('data-render-menu', 'true');
    viewer.setAttribute('data-cta-url', config.ctaUrl);
    stage.replaceChildren(viewer);

    // When the info panel opens, narrow the board's area so the board sits in
    // the space left beside the panel, and ask the viewer to re-measure.
    let panelOpen = false;
    new MutationObserver(() => {
        const open = !!viewer.querySelector('.ui-panel-info-desktop.active');

        if (open === panelOpen) {
            return;
        }

        panelOpen = open;
        viewer.classList.toggle('si-panel-open', open);
        window.dispatchEvent(new Event('resize'));
    }).observe(viewer, { subtree: true, attributes: true, attributeFilter: ['class'] });

    const script = document.createElement('script');
    script.src = config.script;
    document.body.appendChild(script);
};

window.addEventListener('DOMContentLoaded', () => {
    const blocks = [...document.querySelectorAll('[data-sample-inspector]')];

    if (!blocks.length) {
        return;
    }

    // The viewer script runs against the first <application> on the page, so
    // only the first block on a page is started.
    const block = blocks[0];

    if (!('IntersectionObserver' in window)) {
        start(block);
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
            observer.disconnect();
            start(block);
        }
    }, { rootMargin: '300px 0px' });

    observer.observe(block);
});
