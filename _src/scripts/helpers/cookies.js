// Get a Date a number of days in the future.
const getFutureDateFromDays = (days) => new Date(Date.now() + days * 24 * 60 * 60 * 1000);

// Get a future date as a UTC string.
const getFutureUTCStringFromDays = (days) => getFutureDateFromDays(days).toUTCString();

// Create a key-value string for a cookie attribute.
const formatCookiePropVal = (key, value) => `${key}=${value};`;

// Set a cookie with a given lifetime (in days).
const setCookie = (name, value, lifetime, path = '/', domain = null, secure = true, samesite = 'strict') => {
    let cookie =
        formatCookiePropVal(name, value) +
        formatCookiePropVal('expires', getFutureUTCStringFromDays(lifetime)) +
        formatCookiePropVal('path', path) +
        formatCookiePropVal('SameSite', samesite);

    if (typeof domain === 'string') {
        cookie += formatCookiePropVal('Domain', domain);
    }

    if (secure === true) {
        cookie += 'Secure';
    }

    document.cookie = cookie;
};

// Retrieve a cookie value by name. Empty string if not found.
const getCookie = (name) => {
    const nameFormatted = `${name}=`;
    const cookies = document.cookie.split(';');
    for (let i = cookies.length - 1; i > -1; i -= 1) {
        let cookie = cookies[i];
        while (cookie.charAt(0) === ' ') {
            cookie = cookie.substring(1);
        }
        if (cookie.indexOf(nameFormatted) === 0) {
            return cookie.substring(nameFormatted.length, cookie.length);
        }
    }
    return '';
};

export { setCookie, getCookie };
