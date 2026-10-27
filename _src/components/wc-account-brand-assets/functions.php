<?php

/**
 * Assets -- the parts of the panel that the endpoint also has to produce.
 *
 * Live search and paging answer with MARKUP, not JSON rows. The grid is
 * rendered in exactly one place, so a result fetched in the background is the
 * same HTML as a fresh page load, and the redesign changes one file instead of
 * a PHP template and a JS template that then drift apart.
 *
 * ⚠️ Everything here can emit Canto's signed `directUrlOriginal` links, which
 * work for anyone holding them. That is why ajax_page() checks the gate before
 * it renders anything, exactly as the panel does.
 */

namespace Granola\Components\WC_Account_Brand_Assets;

use Theme\Canto\Client;

const AJAX_ACTION = 'granola_brand_assets';

/**
 * How many assets one page holds.
 *
 * Canto is asked for this many VISIBLE assets, and a run of embargoed ones can
 * cost several upstream requests to fill, so this stays modest rather than
 * keeping the old fixed 60. It is deliberately NOT taken from the request:
 * a client that could name its own limit could walk the library in one call.
 */
const PAGE_SIZE = 24;

/**
 * Find a node anywhere in the library tree, by id.
 *
 * The tree is one cached call covering the whole library, so this costs nothing
 * per lookup. It carries `children` for a folder's real contents, and
 * `idPath` / `namePath` for the ancestry a breadcrumb needs -- neither of which
 * the folder endpoint gives us.
 *
 * @return array<string,mixed>|null
 */
function tree_node(string $id): ?array
{
    if ('' === $id) {
        return null;
    }

    $find = static function (array $nodes) use (&$find, $id): ?array {
        foreach ($nodes as $node) {
            if ((string) ($node['id'] ?? '') === $id) {
                return $node;
            }

            if (!empty($node['children'])) {
                $hit = $find($node['children']);

                if (null !== $hit) {
                    return $hit;
                }
            }
        }

        return null;
    };

    return $find(Client::tree());
}

/**
 * The ancestry of a node, nearest ancestor last, for the breadcrumb.
 *
 * Canto hands us `idPath` and `namePath` as matching slash-separated strings,
 * so the two are zipped back together. The node itself is dropped: the crumb
 * shows where you can go, and you are already here.
 *
 * @return array<int,array{id: string, name: string}>
 */
function ancestors(string $id): array
{
    $node = tree_node($id);

    if (null === $node) {
        return [];
    }

    $ids = \array_filter(\explode('/', (string) ($node['idPath'] ?? '')));
    $names = \array_filter(\explode('/', (string) ($node['namePath'] ?? '')));

    // A name containing a slash would desync the two lists, and there is no
    // way to tell which slash was the separator, so bail rather than guess.
    if (\count($ids) !== \count($names)) {
        return [];
    }

    $out = [];

    foreach (\array_values($ids) as $i => $ancestor_id) {
        if ($ancestor_id === $id) {
            continue;
        }

        $out[] = ['id' => $ancestor_id, 'name' => \array_values($names)[$i]];
    }

    return $out;
}

/**
 * One grid's worth of items, as HTML.
 *
 * Returns the `<li>` elements only. The `<ul>` lives in the panel so that a
 * background fetch can append into the list that is already on screen.
 *
 * @param array<int,array<string,mixed>> $items
 */
function render_items(array $items, string $panel_url): string
{
    /*
     * "In Canto" opens the asset in Canto's own interface, which needs a Canto
     * login. Staff have one through the shared account; a distributor or
     * installer does not and never will, so for them the link is a door they
     * cannot open. Download is the signed CDN URL and needs no login, so it
     * stays for everyone.
     *
     * Read once rather than per item: this runs for every tile on the page.
     */
    $staff = \Granola\Components\WC_Account\is_staff();

    \ob_start();

    foreach ($items as $item) {
        $item_scheme = (string) ($item['scheme'] ?? '');
        $name = (string) ($item['name'] ?? '');
        $id = (string) ($item['id'] ?? '');
        $is_container = \in_array($item_scheme, ['folder', 'album'], true);
        /*
         * The 800px render for photographs, falling back to the 240px CDN
         * thumbnail for anything without one (documents, video posters). The
         * tiles stretch to fill the row now, and a 240px source upscales and
         * blurs once a column is wider than that. Both are lazy-loaded.
         */
        $thumb = (string) ($item['url']['directUrlPreview'] ?? '')
            ?: (string) ($item['url']['previewURI240'] ?? '');
        $original = (string) ($item['url']['directUrlOriginal'] ?? '');
        $detail = (string) ($item['url']['detail'] ?? '');
        /*
         * View opens this rather than the original: directUrlPreview is an
         * 800px render that needs no token, where the original can be a 30MB
         * TIFF nobody wants loaded into a lightbox. Only images get it --
         * there is nothing to show for a PDF or a video.
         */
        $preview = 'image' === $item_scheme ? (string) ($item['url']['directUrlPreview'] ?? '') : '';
        ?>
        <li class="mb-assets__item<?php echo $is_container ? ' mb-assets__item--folder' : ''; ?>">
            <?php if ($is_container) : ?>

                <a class="mb-assets__link" href="<?php echo \esc_url(\add_query_arg([
                    'mbca' => $id,
                    'mbcs' => $item_scheme,
                    'mbcn' => $name,
                ], $panel_url)); ?>">
                    <span class="mb-assets__folder-mark" aria-hidden="true"></span>
                    <span class="mb-assets__name"><?php echo \esc_html($name); ?></span>
                    <span class="mb-assets__meta">
                        <?php
                        echo 'folder' === $item_scheme
                            ? \esc_html__('Folder', 'granola')
                            : \esc_html__('Album', 'granola');
                        ?>
                    </span>
                </a>

            <?php else : ?>

                <figure class="mb-assets__asset">
                    <span class="mb-assets__thumb">
                        <?php if ($thumb) : ?>
                            <?php
                            // data-spai-excluded (past tense: that is the
                            // attribute ShortPixel's parser actually tests for)
                            // keeps these off its CDN. They are already
                            // optimised 240px thumbnails on a signed Canto URL,
                            // so re-proxying them adds a hop and caches a
                            // signature Canto can rotate, which would break
                            // them later.
                            ?>
                            <img
                                src="<?php echo \esc_url($thumb); ?>"
                                alt=""
                                loading="lazy"
                                width="240"
                                height="160"
                                data-spai-excluded="true"
                            />
                        <?php endif; ?>
                    </span>

                    <figcaption class="mb-assets__caption">
                        <span class="mb-assets__name"><?php echo \esc_html($name); ?></span>

                        <span class="mb-assets__actions">
                            <?php if ($preview) : ?>
                                <button
                                    type="button"
                                    class="mb-account-link mb-assets__view"
                                    data-mb-view="<?php echo \esc_url($preview); ?>"
                                    data-mb-view-name="<?php echo \esc_attr($name); ?>"
                                >
                                    <?php \esc_html_e('View', 'granola'); ?>
                                </button>
                            <?php endif; ?>

                            <?php if ($original) : ?>
                                <a class="mb-account-link" href="<?php echo \esc_url($original); ?>" download>
                                    <?php \esc_html_e('Download', 'granola'); ?>
                                </a>
                            <?php endif; ?>

                            <?php if ($detail && $staff) : ?>
                                <a class="mb-account-link" href="<?php echo \esc_url($detail); ?>" target="_blank" rel="noopener">
                                    <?php \esc_html_e('In Canto', 'granola'); ?>
                                </a>
                            <?php endif; ?>
                        </span>
                    </figcaption>
                </figure>

            <?php endif; ?>
        </li>
        <?php
    }

    return (string) \ob_get_clean();
}

/**
 * Fetch one page of the library: a search, a folder, or the top level.
 *
 * The panel and the endpoint share this so the two can never disagree about
 * what a page is. `next_start` is Canto's opaque cursor (see Client::contents)
 * and is only ever handed back to this same function.
 *
 * @return array{results: array<int,array<string,mixed>>, found: int, more: bool, next_start: int, failed: bool}
 */
function fetch_page(string $query, string $node_id, string $scheme, int $start): array
{
    if (!Client::is_configured()) {
        return ['results' => [], 'found' => 0, 'more' => false, 'next_start' => 0, 'failed' => true];
    }

    // Both calls already drop every asset this user may not see (see
    // Theme\Canto\Access), so `found` counts visible assets only.
    if ('' !== $query) {
        $result = Client::search($query, PAGE_SIZE, $start);
    } elseif ('' !== $node_id && 'folder' === $scheme) {
        /*
         * ⚠️ Canto's folder endpoint answers with every DESCENDANT asset, not
         * the folder's direct children. Asking it for "Accessories" returns the
         * 7 albums AND all 101 assets sitting inside those albums, so the panel
         * listed every touch-up tin beside the album that already contained it.
         * Canto's own interface shows 7 items for the same folder.
         *
         * The tree knows the real shape and is one cached call, so a folder is
         * answered from there: its children, and nothing from further down. An
         * album still goes to the API, because that is where its assets live.
         */
        $node = tree_node($node_id);
        $children = $node['children'] ?? [];

        $result = [
            'results' => \array_values($children),
            'found' => \count($children),
            'more' => false,
            'next_start' => 0,
        ];
    } elseif ('' !== $node_id) {
        $result = Client::contents($scheme ?: 'album', $node_id, PAGE_SIZE, $start);
    } else {
        // The top level is one cached call covering the whole tree, so it has
        // nothing to page through.
        $items = Client::tree();
        $result = ['results' => $items, 'found' => \count($items), 'more' => false, 'next_start' => 0];
    }

    // Checked AFTER the call: an empty token here means the request itself
    // could not be authorised, which is a reachability problem rather than an
    // empty library.
    $result['failed'] = ('' === Client::token());

    return $result;
}

/**
 * Answer a live search or a "load more" with one page of rendered items.
 *
 * Gated twice on purpose. The nonce proves the request came from the panel;
 * can_view_brand_assets() proves this person is allowed the panel at all, and
 * fails closed. Neither is enough alone: a nonce is readable by anyone who can
 * load the page, and a capability check without a nonce invites a third-party
 * page to spend someone's session walking the library.
 */
function ajax_page(): void
{
    if (!\check_ajax_referer(AJAX_ACTION, 'nonce', false)) {
        \wp_send_json_error(
            ['message' => \__('That page has been open a while. Reload it and try again.', 'granola')],
            403
        );
    }

    if (!\Granola\Components\WC_Account\can_view_brand_assets()) {
        \wp_send_json_error(
            ['message' => \__('You do not have access to the resource library.', 'granola')],
            403
        );
    }

    // phpcs:disable WordPress.Security.NonceVerification.Missing -- checked above.
    $query = isset($_POST['q']) ? \sanitize_text_field(\wp_unslash($_POST['q'])) : '';
    $node_id = isset($_POST['node']) ? \sanitize_text_field(\wp_unslash($_POST['node'])) : '';
    $scheme = isset($_POST['scheme']) ? \sanitize_key(\wp_unslash($_POST['scheme'])) : '';
    $start = isset($_POST['start']) ? \absint($_POST['start']) : 0;
    // phpcs:enable

    $page = fetch_page($query, $node_id, $scheme, $start);

    if ($page['failed']) {
        \wp_send_json_error(
            ['message' => \__('We could not reach Canto just now. The library itself is fine, so it is worth opening it directly.', 'granola')],
            502
        );
    }

    \wp_send_json_success([
        'html' => render_items($page['results'], \wc_get_account_endpoint_url('brand-assets')),
        'count' => \count($page['results']),
        'found' => (int) $page['found'],
        'more' => (bool) $page['more'],
        'next_start' => (int) $page['next_start'],
    ]);
}
