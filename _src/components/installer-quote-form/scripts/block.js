// The HubSpot embed injects internal iframes (submission handler, reCAPTCHA)
// and occasionally an untitled one, which fails WCAG "frames must have a title".
// Give any untitled iframe in the form a title so the form passes an a11y audit.

function titleFrames(root) {
    root.querySelectorAll('iframe:not([title]), iframe[title=""]').forEach(function (frame) {
        frame.setAttribute('title', frame.getAttribute('name') || 'HubSpot form frame');
    });
}

document.querySelectorAll('.installer-quote-form__hs').forEach(function (wrapper) {
    titleFrames(wrapper);
    new MutationObserver(function () {
        titleFrames(wrapper);
    }).observe(wrapper, { childList: true, subtree: true });
});
