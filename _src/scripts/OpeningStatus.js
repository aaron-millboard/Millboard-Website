/**
 * Works out each partner's open-or-closed line in the browser rather than in PHP.
 *
 * It used to be worked out in PHP, and that is wrong on a cached site: the finder page
 * is served from Kinsta's full page cache, so whatever the clock said when the cache
 * entry was written is frozen into the HTML and served to everyone afterwards. An entry
 * written on a Sunday, when every builders merchant has its "closed" box ticked, made
 * all 187 cards read "Closed today" right through the following Monday while the partner
 * profile pages, which are too quiet to stay cached, correctly said "Open now". Purging
 * only reset the lie to a different hour.
 *
 * So the server now ships the week's hours and the browser reads the clock. There is
 * nothing time-sensitive left in the HTML, which means the page can be cached for as
 * long as anyone likes.
 *
 * The clock that matters is the partner's, not the visitor's, so the time is read in the
 * site's own timezone. That is what the PHP did (`current_time()`), and keeping it means
 * a visitor abroad sees the same answer as one at home. It is still approximate for the
 * partners in other countries, exactly as it was before, since one site timezone cannot
 * describe a directory spanning 23 of them.
 *
 * Three surfaces share this: the finder cards, the status line on a partner profile, and
 * the "Today" highlight on the profile's opening hours table. All three were frozen by
 * the same cache and are fixed by the same pass.
 *
 * Wording stays in PHP so it stays translatable in Loco, and arrives on `params`.
 */

const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

/**
 * Minutes past midnight for an "HH:MM" string, or null if it is not one.
 *
 * Mirrors the PHP this replaces: a single-digit hour is fine, stray whitespace is fine,
 * and an impossible time is rejected rather than quietly wrapped around.
 */
function toMinutes(time) {
    const match = /^\s*(\d{1,2}):(\d{2})\s*$/.exec(String(time));

    if (!match) {
        return null;
    }

    const hours = parseInt(match[1], 10);
    const minutes = parseInt(match[2], 10);

    if (hours > 23 || minutes > 59) {
        return null;
    }

    return (hours * 60) + minutes;
}

/**
 * The day and time right now, read in the site's timezone.
 *
 * An unrecognised timezone would otherwise throw and take every line on the page down
 * with it, so it falls back to the visitor's own clock. That is the wrong clock for a
 * partner in another country, but it is the right one for the overwhelming majority who
 * are in the same country as the site they are reading.
 */
function nowAt(timezone) {
    const options = {
        weekday: 'long',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
    };

    let parts;

    try {
        parts = new Intl.DateTimeFormat('en-GB', {...options, timeZone: timezone}).formatToParts(new Date());
    } catch (error) {
        parts = new Intl.DateTimeFormat('en-GB', options).formatToParts(new Date());
    }

    const value = (type) => {
        const part = parts.find((candidate) => candidate.type === type);

        return part ? part.value : '';
    };

    return {
        day: value('weekday'),
        minutes: (parseInt(value('hour'), 10) * 60) + parseInt(value('minute'), 10),
    };
}

/**
 * Today's line for one record.
 *
 * `entry` is that day's slot from the week payload: null where the record has no row for
 * the day, false where it says closed, and "HH:MM-HH:MM" where it is open. Returns null
 * when there is nothing to say, which is not the same as being closed and must not be
 * shown as though it were.
 */
function lineFor(entry, labels, minutes) {
    if (entry === false) {
        return {state: 'closed', text: labels.closed_today};
    }

    if (typeof entry !== 'string') {
        return null;
    }

    const [opens, closes] = entry.split('-');
    const from = toMinutes(opens);
    const until = toMinutes(closes);

    if (from === null || until === null) {
        return null;
    }

    if (minutes < from) {
        return {state: 'closed', text: labels.opens.replace('%s', opens)};
    }

    if (minutes >= until) {
        return {state: 'closed', text: labels.closed_now};
    }

    return {state: 'open', text: labels.open.replace('%s', closes)};
}

/**
 * Fill in one status element, or hide it when today has no hours to report.
 */
function paint(element, config, now) {
    const labels = config[element.dataset.openingStatus];

    if (!labels) {
        return;
    }

    let week;

    try {
        week = JSON.parse(element.dataset.openingWeek || 'null');
    } catch (error) {
        week = null;
    }

    const line = Array.isArray(week) ? lineFor(week[DAYS.indexOf(now.day)], labels, now.minutes) : null;

    if (!line) {
        element.hidden = true;

        return;
    }

    // The profile line wraps its text in a span so the status dot beside it survives.
    const target = element.querySelector('[data-opening-status-text]') || element;

    target.textContent = line.text;
    element.classList.remove(`${labels.class}--open`, `${labels.class}--closed`);
    element.classList.add(`${labels.class}--${line.state}`);
    element.hidden = false;
}

/**
 * Mark today's row on a profile's opening hours table.
 *
 * Every row is rendered carrying a hidden "Today" pill, so the pill's wording stays in
 * PHP where Loco can translate it and nothing has to be built in JavaScript.
 */
function markToday(config, now) {
    document.querySelectorAll('[data-opening-day]').forEach((row) => {
        const isToday = row.dataset.openingDay === now.day;
        const pill = row.querySelector('[data-opening-today-pill]');

        row.classList.toggle(config.today_class, isToday);

        if (pill) {
            pill.hidden = !isToday;
        }
    });
}

export default function initOpeningStatus() {
    const config = window.params && window.params.opening_status;

    if (!config) {
        return;
    }

    const now = nowAt(config.timezone);

    if (!DAYS.includes(now.day) || Number.isNaN(now.minutes)) {
        return;
    }

    document.querySelectorAll('[data-opening-status]').forEach((element) => paint(element, config, now));
    markToday(config, now);
}
