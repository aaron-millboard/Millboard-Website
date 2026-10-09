// HubSpot renders its checkbox-group lists with role="checkbox" on the <ul> and on
// each <li>, even though the real <input type="checkbox"> inside already carries that
// role. That trips two WCAG 4.1.2 failures: aria-required-attr (a checkbox role needs
// aria-checked) and nested-interactive (checkbox inside checkbox). HubSpot will not
// change their embed for us, so strip the bogus roles our side once the form renders.
// The native <ul>/<li> + real checkbox markup underneath is already accessible.
function stripBogusCheckboxRoles() {
    document
        .querySelectorAll('ul.inputs-list[role="checkbox"], li.hs-form-checkbox[role="checkbox"]')
        .forEach((el) => el.removeAttribute('role'));
}

export default function initHubspotFormA11y() {
    // HubSpot forms render asynchronously and can re-render, so fix what is already
    // here and keep watching for late/replaced forms.
    stripBogusCheckboxRoles();

    // ponytail: whole-body observer, cheap selector. These pages are low-churn; scope
    // it to the form wrapper only if a busy page ever makes it show up.
    new MutationObserver(stripBogusCheckboxRoles).observe(document.body, {
        childList: true,
        subtree: true,
    });
}
