<?php

/**
 * Brand assets.
 *
 * The Canto library, read through its API and rendered in the account's own
 * design. It is not embedded: Canto answers every request with
 * `X-Frame-Options: DENY` and carries no `frame-ancestors` directive, so no
 * site can frame it and there is no setting that would let one.
 *
 * Authentication is server-side and shared (see Theme\Canto\Client), so nobody
 * needs a Canto login of their own -- which is what the design promises.
 *
 * Thumbnails and downloads are Canto's own public URLs: the CDN preview needs
 * no token, and the original is a signed link. ⚠️ That signed link works for
 * anyone who has it, so it is only ever rendered behind the team gate.
 */

use Theme\Canto\Client;

$library_url = Client::is_configured() ? Client::library_url() : 'https://millboard.canto.global/';

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only navigation.
$query = isset($_GET['mbcq']) ? sanitize_text_field(wp_unslash($_GET['mbcq'])) : '';
$node_id = isset($_GET['mbca']) ? sanitize_text_field(wp_unslash($_GET['mbca'])) : '';
$scheme = isset($_GET['mbcs']) ? sanitize_key(wp_unslash($_GET['mbcs'])) : '';
$node_name = isset($_GET['mbcn']) ? sanitize_text_field(wp_unslash($_GET['mbcn'])) : '';
// phpcs:enable

$panel_url = wc_get_account_endpoint_url('brand-assets');

$items = [];
$found = 0;
$failed = false;

if (Client::is_configured()) {
    if ('' !== $query) {
        $result = Client::search($query, 60);
        $items = $result['results'];
        $found = $result['found'];
    } elseif ('' !== $node_id) {
        $result = Client::contents($scheme ?: 'album', $node_id, 60);
        $items = $result['results'];
        $found = $result['found'];
    } else {
        $items = Client::tree();
        $found = count($items);
    }

    $failed = ('' === Client::token());
}

?>

<div class="mb-account-assets">

    <div class="mb-account-panel__head">
        <h2 class="mb-account-panel__title"><?php esc_html_e('Brand assets', 'granola'); ?></h2>
        <span class="mb-account-panel__badge"><?php esc_html_e('Millboard team', 'granola'); ?></span>
    </div>

    <p class="mb-account-panel__intro">
        <?php esc_html_e('Photography, logos, brochures and product imagery, straight from Canto. You are signed in already, so there is no second login.', 'granola'); ?>
    </p>

    <?php if (!Client::is_configured() || $failed) : ?>

        <div class="mb-account-empty">
            <span class="mb-account-empty__rule" aria-hidden="true"></span>
            <h3 class="mb-account-empty__title"><?php esc_html_e('Brand library unavailable', 'granola'); ?></h3>
            <p class="mb-account-empty__body">
                <?php
                echo Client::is_configured()
                    ? esc_html__('We could not reach Canto just now. The library itself is fine, so it is worth opening it directly.', 'granola')
                    : esc_html__('The connection to Canto is not set up on this site yet.', 'granola');
                ?>
            </p>
            <a class="mb-account-btn" href="<?php echo esc_url($library_url); ?>" target="_blank" rel="noopener">
                <?php esc_html_e('Open the brand library', 'granola'); ?>
            </a>
        </div>

    <?php else : ?>

        <form class="mb-assets__search" method="get" action="<?php echo esc_url($panel_url); ?>" role="search">
            <label class="visually-hidden" for="mbcq"><?php esc_html_e('Search the brand library', 'granola'); ?></label>
            <input
                type="search"
                id="mbcq"
                name="mbcq"
                value="<?php echo esc_attr($query); ?>"
                placeholder="<?php esc_attr_e('Search photography, logos, brochures…', 'granola'); ?>"
            />
            <button type="submit" class="mb-account-btn mb-account-btn--ghost">
                <?php esc_html_e('Search', 'granola'); ?>
            </button>
        </form>

        <div class="mb-assets__bar">
            <p class="mb-assets__crumb">
                <?php if ('' !== $query || '' !== $node_id) : ?>
                    <a class="mb-account-back" href="<?php echo esc_url($panel_url); ?>">
                        <span aria-hidden="true">&lsaquo;</span>
                        <?php esc_html_e('All folders', 'granola'); ?>
                    </a>
                <?php endif; ?>
            </p>

            <p class="mb-assets__count">
                <?php
                if ('' !== $query) {
                    printf(
                        /* translators: %1$s: number of results, %2$s: the search term. */
                        esc_html__('%1$s results for &ldquo;%2$s&rdquo;', 'granola'),
                        esc_html(number_format_i18n($found)),
                        esc_html($query)
                    );
                } elseif ('' !== $node_name) {
                    echo esc_html($node_name);
                }
                ?>
            </p>
        </div>

        <?php if (!$items) : ?>

            <div class="mb-account-empty">
                <span class="mb-account-empty__rule" aria-hidden="true"></span>
                <h3 class="mb-account-empty__title"><?php esc_html_e('Nothing here', 'granola'); ?></h3>
                <p class="mb-account-empty__body">
                    <?php esc_html_e('No assets matched. Try a different word, or browse the folders.', 'granola'); ?>
                </p>
            </div>

        <?php else : ?>

            <ul class="mb-assets__grid">
                <?php foreach ($items as $item) :
                    $item_scheme = (string) ($item['scheme'] ?? '');
                    $name = (string) ($item['name'] ?? '');
                    $id = (string) ($item['id'] ?? '');
                    $is_container = in_array($item_scheme, ['folder', 'album'], true);
                    $thumb = (string) ($item['url']['previewURI240'] ?? '');
                    $original = (string) ($item['url']['directUrlOriginal'] ?? '');
                    $detail = (string) ($item['url']['detail'] ?? '');
                    ?>
                    <li class="mb-assets__item<?php echo $is_container ? ' mb-assets__item--folder' : ''; ?>">
                        <?php if ($is_container) : ?>

                            <a class="mb-assets__link" href="<?php echo esc_url(add_query_arg([
                                'mbca' => $id,
                                'mbcs' => $item_scheme,
                                'mbcn' => $name,
                            ], $panel_url)); ?>">
                                <span class="mb-assets__folder-mark" aria-hidden="true"></span>
                                <span class="mb-assets__name"><?php echo esc_html($name); ?></span>
                                <span class="mb-assets__meta">
                                    <?php
                                    echo 'folder' === $item_scheme
                                        ? esc_html__('Folder', 'granola')
                                        : esc_html__('Album', 'granola');
                                    ?>
                                </span>
                            </a>

                        <?php else : ?>

                            <figure class="mb-assets__asset">
                                <span class="mb-assets__thumb">
                                    <?php if ($thumb) : ?>
                                        <?php
                                        // data-spai-excluded (past tense: that
                                        // is the attribute ShortPixel's parser
                                        // actually tests for) keeps these off
                                        // its CDN. They are already optimised
                                        // 240px thumbnails on a signed Canto
                                        // URL, so re-proxying them adds a hop
                                        // and caches a signature Canto can
                                        // rotate, which would break them later.
                                        ?>
                                        <img
                                            src="<?php echo esc_url($thumb); ?>"
                                            alt=""
                                            loading="lazy"
                                            width="240"
                                            height="160"
                                            data-spai-excluded="true"
                                        />
                                    <?php endif; ?>
                                </span>

                                <figcaption class="mb-assets__caption">
                                    <span class="mb-assets__name"><?php echo esc_html($name); ?></span>

                                    <span class="mb-assets__actions">
                                        <?php if ($original) : ?>
                                            <a class="mb-account-link" href="<?php echo esc_url($original); ?>" download>
                                                <?php esc_html_e('Download', 'granola'); ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($detail) : ?>
                                            <a class="mb-account-link" href="<?php echo esc_url($detail); ?>" target="_blank" rel="noopener">
                                                <?php esc_html_e('In Canto', 'granola'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </span>
                                </figcaption>
                            </figure>

                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($found > count($items)) : ?>
                <p class="mb-assets__more">
                    <?php
                    printf(
                        /* translators: %1$s: shown, %2$s: total. */
                        esc_html__('Showing %1$s of %2$s. Open the library for the rest.', 'granola'),
                        esc_html(number_format_i18n(count($items))),
                        esc_html(number_format_i18n($found))
                    );
                    ?>
                </p>
            <?php endif; ?>

        <?php endif; ?>

        <p class="mb-account-embed-note">
            <?php
            printf(
                /* translators: %1$s: opening link tag, %2$s: closing link tag. */
                esc_html__('Looking for something not here? %1$sOpen the full library in Canto%2$s.', 'granola'),
                '<a href="' . esc_url($library_url) . '" target="_blank" rel="noopener">',
                '</a>'
            );
            ?>
        </p>

    <?php endif; ?>

</div>
