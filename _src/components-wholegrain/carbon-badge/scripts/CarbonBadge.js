/* global carbonBadgeL10nObject */

export default class CarbonBadge {
    constructor(element) {
        // If the fetch API is not available, don't do anything.
        if (!('fetch' in window)) {
            return;
        }

        // Bail early - can't access localized object.
        if (!carbonBadgeL10nObject) {
            return;
        }

        this.el = element;
        this.innerEl = this.el.querySelector('.carbon-badge__inner');
        this.resultEl = this.el.querySelector('.carbon-badge__result');

        if (process.env.NODE_ENV !== 'development') {
            this.url = encodeURIComponent(window.location.href);
        } else {
            // Fallback to WD website to display a value for development sites.
            this.url = encodeURIComponent('https://www.wholegraindigital.com/');
        }

        this.init();
    }

    init() {
        // Get result if it's saved.
        this.cachedResponse = localStorage.getItem(`wcb_${this.url}`);

        // If there is a cached response, use it.
        if (this.cachedResponse) {
            const r = JSON.parse(this.cachedResponse);
            const t = new Date().getTime();
            this.renderResult(r);

            // Time since response was cached is over a day;
            // make a new request and update the cached result in the background.
            if (t - r.t > 86400000) {
                this.newRequest(false);
            }
        } else {
            this.newRequest(); // No cached response - fetch from API.
        }
    }

    newRequest(render = true) {
        // Run the API request because there is no cached result available.
        fetch(`https://api.websitecarbon.com/b?url=${this.url}`)
            .then((r) => {
                if (!r.ok) {
                    throw Error(r);
                }
                return r.json();
            })
            .then((r) => {
                if (render) {
                    this.renderResult(r);
                }

                // Save the result into localStorage with a timestamp.
                r.t = new Date().getTime();
                localStorage.setItem(`wcb_${this.url}`, JSON.stringify(r));
            })
            .catch((e) => {
                // Handle error responses.
                this.resultEl.innerHTML = '';
                console.log(e);
                localStorage.removeItem(`wcb_${this.url}`);
            });
    }

    renderResult(result) {
        if (!result.c) {
            return;
        }

        // Create result text, e.g. "0.5g of CO2".
        const resultText = carbonBadgeL10nObject.result.replace(
            '{grams}',
            new Intl.NumberFormat(document.documentElement.lang).format(result.c)
        );
        this.resultEl.innerHTML = resultText;

        // Conditionally create and add context, e.g. "Cleaner than 40% of pages tested".
        if (result.p) {
            const cleanerText = carbonBadgeL10nObject.cleaner.replace(
                '{percent}',
                new Intl.NumberFormat(document.documentElement.lang).format(result.p)
            );
            const cleanerEl = document.createElement('span');
            cleanerEl.classList.add('carbon-badge__clean');
            cleanerEl.textContent = cleanerText;
            this.el.appendChild(cleanerEl);
        }
    }
}
