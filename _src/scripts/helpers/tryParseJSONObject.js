/**
 * Using JSON.parse() when you don't know if it's a JSON string throws errors...
 * Solution below from https://stackoverflow.com/a/20392392/4479336
 * ---
 * If you don't care about primitives and only objects then this function
 * is for you, otherwise look elsewhere.
 * This function will return `false` for any valid JSON primitive.
 * EG, 'true' -> false
 *     '123' -> false
 *     'null' -> false
 *     '"I'm a string"' -> false
 */
export default function tryParseJSONObject(JSONString) {
    try {
        const o = JSON.parse(JSONString);

        // Handle non-exception-throwing cases:
        // Neither JSON.parse(false) or JSON.parse(1234) throw errors, hence the type-checking,
        // but... JSON.parse(null) returns null, and typeof null === "object",
        // so we must check for that, too. Thankfully, null is falsey, so this suffices:
        if (o && typeof o === 'object') {
            return o;
        }
    } catch (e) {
        // console.log('Could not parse JSON from string.');
    }

    return false;
}
