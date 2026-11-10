<?php

/**
 * Self-check for Client::container_listed(). No WordPress needed:
 *
 *   php docs/canto-containers-selfcheck.php
 *
 * Exits non-zero and says which case failed. docs/ is in .deployignore.
 *
 * This is the rule that decides whether a folder or album is listed in
 * My Account > Resources and, through the same call, on the Covers admin
 * screen. Getting it wrong in the safe direction shows a tile that opens onto
 * nothing; getting it wrong in the unsafe direction EMPTIES the library for
 * every partner, which is why the fail-open cases below are not optional.
 *
 * Added 10 Nov 2026 with the "nothing inside anyone may see" test, after
 * three Paid Media albums listed 70, 25 and 63 assets and showed nothing.
 */

/* ---- the little bit of WordPress these two classes touch ---------------- */

define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 3600);
define('MB_CANTO_TENANT', 'example');
define('MB_CANTO_DOMAIN', 'canto.global');

$GLOBALS['mb_options'] = [];

function get_option($name, $default = false)
{
    return $GLOBALS['mb_options'][$name] ?? $default;
}

function update_option($name, $value, $autoload = null)
{
    $GLOBALS['mb_options'][$name] = $value;

    return true;
}

/*
 * The hide list is a NETWORK option: one Canto library feeds all six locales.
 * Kept in a separate store here so a case that wrote the per-site option by
 * mistake would fail rather than quietly pass.
 */
$GLOBALS['mb_site_options'] = [];

function get_site_option($name, $default = false)
{
    return $GLOBALS['mb_site_options'][$name] ?? $default;
}

function update_site_option($name, $value)
{
    $GLOBALS['mb_site_options'][$name] = $value;

    return true;
}

function get_transient($key)
{
    return false;
}

function set_transient($key, $value, $ttl = 0)
{
    return true;
}

function delete_transient($key)
{
    return true;
}

/*
 * restricted() is reached only by a container that has survived every other
 * test, and it is the one step that would otherwise make a network call. A
 * non-200 makes it fail open and return false, which is its real behaviour
 * when Canto cannot be reached, so the last line of container_listed() is
 * exercised rather than skipped.
 */
function wp_remote_get($url, $args = [])
{
    return ['response' => ['code' => 503], 'body' => ''];
}

function is_wp_error($thing)
{
    return false;
}

function wp_remote_retrieve_response_code($response)
{
    return $response['response']['code'] ?? 0;
}

function wp_remote_retrieve_body($response)
{
    return $response['body'] ?? '';
}

function apply_filters($hook, $value)
{
    return $value;
}

/*
 * A pretend multisite, so a cover stored as wp:<blog>:<id> can be shown to
 * resolve against the RIGHT blog. Attachment 500 is an image on blog 3 only;
 * blog 7 has a different picture at the same id, which is the whole hazard.
 */
$GLOBALS['mb_blog'] = 7;
$GLOBALS['mb_switch_depth'] = 0;

$GLOBALS['mb_attachments'] = [
    3 => [500 => 'https://example.test/uk/summit-header.jpg'],
    7 => [500 => 'https://example.test/de/something-else.jpg', 600 => null],
];

function get_current_blog_id()
{
    return $GLOBALS['mb_blog'];
}

function switch_to_blog($blog_id)
{
    $GLOBALS['mb_blog'] = (int) $blog_id;
    ++$GLOBALS['mb_switch_depth'];

    return true;
}

function restore_current_blog()
{
    $GLOBALS['mb_blog'] = 7;
    --$GLOBALS['mb_switch_depth'];

    return true;
}

function wp_attachment_is_image($id)
{
    $blog = $GLOBALS['mb_blog'];

    return !empty($GLOBALS['mb_attachments'][$blog][$id]);
}

function wp_get_attachment_image_url($id, $size = 'large')
{
    $blog = $GLOBALS['mb_blog'];

    return $GLOBALS['mb_attachments'][$blog][$id] ?? '';
}

function add_query_arg()
{
    return '';
}

function esc_html__($text, $domain = null)
{
    return $text;
}

function __($text, $domain = null)
{
    return $text;
}

require __DIR__ . '/../Theme/Canto/Access.php';
require __DIR__ . '/../Theme/Canto/Covers.php';
require __DIR__ . '/../Theme/Canto/Client.php';

use Theme\Canto\Client;
use Theme\Canto\Covers;

/* ---- cases -------------------------------------------------------------- */

$album = static fn (string $id, ?int $size = 12) => array_filter(
    ['scheme' => 'album', 'id' => $id, 'size' => $size],
    static fn ($v) => null !== $v
);

$live = ['LIVE1' => true, 'LIVE2' => true];

$cases = [
    // [label, node, live set, expected]

    // Assets are not containers and are never touched by this rule.
    ['an image passes straight through', ['scheme' => 'image', 'id' => 'X'], $live, true],
    ['a document passes straight through', ['scheme' => 'document', 'id' => 'X'], $live, true],

    // Canto's own emptiness.
    ['explicit size 0 is dropped', $album('LIVE1', 0), $live, false],
    ['size absent is NOT treated as empty', ['scheme' => 'album', 'id' => 'LIVE1'], $live, true],

    // The new test.
    ['album with nothing visible is dropped', $album('DEAD'), $live, false],
    ['album with something visible is kept', $album('LIVE1'), $live, true],
    ['folder with something visible is kept', ['scheme' => 'folder', 'id' => 'LIVE2', 'size' => 3], $live, true],

    /*
     * ⚠️ FAIL OPEN. live_containers() returns null when its answer is not
     * trustworthy. Pruning on null would hide the entire library from every
     * partner at once, so null must mean "do not apply this test".
     */
    ['null live set does NOT prune a container', $album('DEAD'), null, true],
    ['null live set still honours size 0', $album('DEAD', 0), null, false],
];

/* The hide list is stateful, so those cases run separately below. */

$failed = 0;
$ran = 0;

foreach ($cases as [$label, $node, $set, $expected]) {
    ++$ran;
    $got = Client::container_listed($node, $set);

    if ($got !== $expected) {
        ++$failed;
        printf("FAIL  %s\n        expected %s, got %s\n", $label, var_export($expected, true), var_export($got, true));
    }
}

/* ---- the hide list ------------------------------------------------------ */

Covers::set_hidden('LIVE1', true);

$hide_cases = [
    ['hidden container is dropped from My Account', Client::container_listed($album('LIVE1'), $live), false],
    /*
     * ⚠️ The admin screen passes false so a hidden container KEEPS its row.
     * Without this the checkbox that hid it would disappear with it and it
     * could never be un-hidden.
     */
    ['hidden container is still listed on the admin screen', Client::container_listed($album('LIVE1'), $live, false), true],
    ['an unhidden sibling is unaffected', Client::container_listed($album('LIVE2'), $live), true],
    ['is_hidden agrees', Covers::is_hidden('LIVE1'), true],
    ['is_hidden is false for others', Covers::is_hidden('LIVE2'), false],
    ['is_hidden is false for an empty id', Covers::is_hidden(''), false],
];

foreach ($hide_cases as [$label, $got, $expected]) {
    ++$ran;

    if ($got !== $expected) {
        ++$failed;
        printf("FAIL  %s\n        expected %s, got %s\n", $label, var_export($expected, true), var_export($got, true));
    }
}

/* Un-hiding has to actually work, or the toggle is a one-way door. */
Covers::set_hidden('LIVE1', false);

++$ran;

if (Client::container_listed($album('LIVE1'), $live) !== true) {
    ++$failed;
    echo "FAIL  un-hiding puts the container back\n";
}

++$ran;

if ([] !== get_site_option(Covers::HIDDEN_OPTION, 'missing')) {
    ++$failed;
    echo "FAIL  un-hiding the last one leaves an empty list, not a stale entry\n";
}

/*
 * ⚠️ It must be the NETWORK store, not the per-site one. Hiding an album on
 * en-gb while it still shows on the other five locales is the bug this
 * prevents, and nothing else in the suite would notice the difference.
 */
Covers::set_hidden('NETWORK', true);

++$ran;

if (!isset($GLOBALS['mb_site_options'][Covers::HIDDEN_OPTION])) {
    ++$failed;
    echo "FAIL  the hide list is stored network-wide\n";
}

++$ran;

if (isset($GLOBALS['mb_options'][Covers::HIDDEN_OPTION])) {
    ++$failed;
    echo "FAIL  the hide list must NOT be written to the per-site option\n";
}

Covers::set_hidden('NETWORK', false);

/* ---- cover references across sites -------------------------------------- */

/*
 * ⚠️ The reason this exists. Attachment ids are PER SITE, so the UK's folder
 * thumbnails could not be copied to the other twelve by number: on blog 7 the
 * same id is a different picture. A stored blog id makes the reference mean
 * the same thing everywhere.
 */
$resolve_cases = [
    ['cross-site ref resolves against the NAMED blog, not the current one',
        Covers::resolve_url('wp:3:500'), 'https://example.test/uk/summit-header.jpg'],
    ['a bare wp:<id> still means THIS site',
        Covers::resolve_url('wp:500'), 'https://example.test/de/something-else.jpg'],
    ['naming the current blog is the same as not naming it',
        Covers::resolve_url('wp:7:500'), 'https://example.test/de/something-else.jpg'],
    ['an attachment that is not an image resolves to nothing',
        Covers::resolve_url('wp:7:600'), ''],
    ['an id that does not exist on the named blog resolves to nothing',
        Covers::resolve_url('wp:3:600'), ''],
    ['a malformed reference resolves to nothing', Covers::resolve_url('wp:'), ''],
    ['an empty value resolves to nothing', Covers::resolve_url(''), ''],
];

foreach ($resolve_cases as [$label, $got, $expected]) {
    ++$ran;

    if ($got !== $expected) {
        ++$failed;
        printf("FAIL  %s\n        expected %s, got %s\n", $label, var_export($expected, true), var_export($got, true));
    }
}

/*
 * ⚠️ Every switch_to_blog must be paired. A leaked switch leaves the rest of
 * the request reading another site's options, which is the kind of fault that
 * shows up somewhere else entirely.
 */
++$ran;

if (0 !== $GLOBALS['mb_switch_depth'] || 7 !== get_current_blog_id()) {
    ++$failed;
    printf(
        "FAIL  resolve_url leaves the blog switched (depth %d, blog %d)\n",
        $GLOBALS['mb_switch_depth'],
        get_current_blog_id()
    );
}

printf("\n%d checks, %d failed\n", $ran, $failed);

exit($failed > 0 ? 1 : 0);
