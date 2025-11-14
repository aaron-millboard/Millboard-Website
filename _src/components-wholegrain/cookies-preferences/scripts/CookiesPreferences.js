import { setCookie, getCookie } from '../../../scripts/helpers/cookies.js';
import tryParseJSONObject from '../../../scripts/helpers/tryParseJSONObject.js';
import ExpandableElement from '../../../scripts/helpers/ExpandableElement.js';

/**
 * Cookies Preferences
 * TODO: Consider a decent way of avoiding loading JS (and styles?) for Cookies functionality unless needed.
 * TODO(cont): Typically we'd just check for the existence of the flag cookie (cookies-preferences-saved)...
 * TODO(cont): ... in PHP and not load the component, but this was causing some funky issues around resetting...
 * TODO(cont): ... preferences on Kinsta sites where cached HTML was being served without the component regardless of...
 * TODO(cont): ... the saved cookies-preferences-saved cookie value.
 *
 */
class CookiesPreferences {
    constructor(element) {
        this.el = element;

        // Cookie info
        this.cookieLifetime = 365; // Days
        this.cookieName = 'cookies-consent';

        // Key els, i.e. the form, inputs, and trigger buttons.
        this.formEl = this.el;
        this.groupsExpandableElTarget = this.el.querySelector('.js-expandable-element');
        this.groupsExpandableElTargetId = this.groupsExpandableElTarget.getAttribute('id');

        this.consentTypeInputs = this.el.querySelectorAll('input[type="checkbox"]');

        this.consentAllButton = this.el.querySelector('.js-cookies-consent-all');
        this.consentSelectedButton = this.el.querySelector('.js-cookies-consent-selected');
        this.rejectAllButton = this.el.querySelector('.js-cookies-reject-all');
        this.managePreferencesButton = this.el.querySelector(`[aria-controls="${this.groupsExpandableElTargetId}"]`);

        this.alertElShell = document.createElement('p');
        this.alertElShell.setAttribute('role', 'alert');
        this.alertElShell.classList.add('cookies-preferences__alert');
        this.alertElShell.setAttribute('hidden', 'hidden');

        this.preferencesSavedAlertEl = null;

        // Feedback messages
        this.feedbackText = tryParseJSONObject(this.el.dataset.feedbackText);

        this.init();
    }

    init() {
        this.consentAllButton.addEventListener('click', () => {
            this.consentTypeInputs.forEach((element) => {
                element.checked = true;

                this.updateActionsVisibility();
            });
        });

        this.rejectAllButton.addEventListener('click', () => {
            this.consentTypeInputs.forEach((element) => {
                element.checked = false;

                this.updateActionsVisibility();
            });
        });

        this.consentTypeInputs.forEach((element) => {
            element.addEventListener('change', () => {
                this.updateActionsVisibility();
            });
        });

        // Stop click events from submitting the form on manage preferences.
        this.managePreferencesButton.addEventListener('click', (e) => {
            e.preventDefault();
        });

        this.formEl.addEventListener('submit', (e) => {
            e.preventDefault();

            const formdata = new FormData(this.formEl);
            const consentPreferences = {};

            [...formdata.entries()].forEach((pair) => {
                if (pair[0].indexOf('gtm_consent_type_') === 0) {
                    // TODO: Consider making cookie consent type objects classes (e.g. CookiesPreference).
                    consentPreferences[pair[0]] = {
                        type: 'gtm_consent_type',
                        name: pair[0].replace('gtm_consent_type_', ''),
                        value: pair[1] === 'granted' ? 'granted' : 'denied',
                    };
                } else {
                    consentPreferences[pair[0]] = {
                        type: 'custom',
                        name: pair[0],
                        value: pair[1],
                    };
                }
            });

            this.savePreferences(consentPreferences);
        });

        this.updateFormInputsFromPreferences(this.getSavedPreferences());

        document.addEventListener('cookiespreferencessaved', () => {
            this.updateFormInputsFromPreferences(this.getSavedPreferences());
        });

        this.el.addEventListener('cookiespreferencessaved', () => {
            const preferencesSavedText = document.createTextNode(this.feedbackText.preferencesSaved);

            if (this.preferencesSavedAlertEl != null) {
                this.preferencesSavedAlertEl.remove();
            }

            this.preferencesSavedAlertEl = this.alertElShell.cloneNode();

            this.preferencesSavedAlertEl.appendChild(preferencesSavedText);

            this.el.appendChild(this.preferencesSavedAlertEl);

            setTimeout(() => {
                this.preferencesSavedAlertEl.removeAttribute('hidden');
            }, 1);

            // Push an event we can pick up in GTM.
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                event: 'consentcookiechange',
            });
        });

        if (this.groupsExpandableElTarget instanceof Element) {
            this.groupsExpandableEl = new ExpandableElement(this.groupsExpandableElTarget);

            this.groupsExpandableEl.updateConfig({
                focusWithinOnExpand: true,
            });
        }
    }

    updateActionsVisibility() {
        let checkedConsentTypes = 0;

        this.consentTypeInputs.forEach((element, index) => {
            if (element.checked) {
                checkedConsentTypes += 1;
            }

            if (index === this.consentTypeInputs.length - 1) {
                if (checkedConsentTypes === 0 || checkedConsentTypes === this.consentTypeInputs.length) {
                    this.consentAllButton.parentNode.setAttribute('aria-hidden', 'false');
                    this.consentSelectedButton.parentNode.setAttribute('aria-hidden', 'true');
                } else {
                    this.consentAllButton.parentNode.setAttribute('aria-hidden', 'true');
                    this.consentSelectedButton.parentNode.setAttribute('aria-hidden', 'false');
                }
            }
        });
    }

    // Update the form inputs based on some preferences
    updateFormInputsFromPreferences(preferences, callback) {
        this.consentTypeInputs.forEach((element, index) => {
            if (Object.prototype.hasOwnProperty.call(preferences, element.name)) {
                if (preferences[element.name].value === element.value) {
                    element.checked = true;
                } else {
                    element.checked = false;
                }
            }

            if (index === this.consentTypeInputs.length - 1) {
                this.updateActionsVisibility();
            }
        });

        if (typeof callback === 'function') {
            callback();
        }
    }

    getSavedPreferences() {
        const cookieData = getCookie(this.cookieName);
        let response = {};

        response = tryParseJSONObject(cookieData);

        // In case the cookie value has been saved as anything other than a JSON object.
        if (response === false && cookieData !== '') {
            setCookie(this.cookieName, '', 0);
        }

        return response;
    }

    // Save our preferences in a cookie.
    savePreferences(preferences) {
        setCookie(this.cookieName, JSON.stringify(preferences), this.cookieLifetime);
        setCookie(`${this.cookieName}-preferences-saved`, 1, this.cookieLifetime);

        this.el.dispatchEvent(
            new Event('cookiespreferencessaved', {
                bubbles: true,
                cancelable: false,
            })
        );
    }
}

export default CookiesPreferences;
