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

use function Granola\Components\WC_Account_Brand_Assets\fetch_page;
use function Granola\Components\WC_Account_Brand_Assets\render_items;

use const Granola\Components\WC_Account_Brand_Assets\AJAX_ACTION;

$library_url = Client::is_configured() ? Client::library_url() : 'https://millboard.canto.global/';

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only navigation.
$query = isset($_GET['mbcq']) ? sanitize_text_field(wp_unslash($_GET['mbcq'])) : '';
$node_id = isset($_GET['mbca']) ? sanitize_text_field(wp_unslash($_GET['mbca'])) : '';
$scheme = isset($_GET['mbcs']) ? sanitize_key(wp_unslash($_GET['mbcs'])) : '';
$node_name = isset($_GET['mbcn']) ? sanitize_text_field(wp_unslash($_GET['mbcn'])) : '';
// mbcx carries Canto's own opaque cursor, so paging works as plain links
// before any JavaScript does. The script then takes the same link over.
$start = isset($_GET['mbcx']) ? absint($_GET['mbcx']) : 0;
// phpcs:enable

$panel_url = wc_get_account_endpoint_url('brand-assets');

$page = fetch_page($query, $node_id, $scheme, $start);

$items = $page['results'];
$found = $page['found'];
$more = $page['more'];
$next_start = $page['next_start'];
$failed = $page['failed'];

// What a "show more" has to carry to land on the same listing, one page on.
$more_args = ['mbcx' => $next_start];

foreach (['mbcq' => $query, 'mbca' => $node_id, 'mbcs' => $scheme, 'mbcn' => $node_name] as $key => $value) {
    if ('' !== $value) {
        $more_args[$key] = $value;
    }
}

?>

<div
    class="mb-account-assets"
    data-mb-assets
    data-endpoint="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
    data-action="<?php echo esc_attr(AJAX_ACTION); ?>"
    data-nonce="<?php echo esc_attr(wp_create_nonce(AJAX_ACTION)); ?>"
    data-query="<?php echo esc_attr($query); ?>"
    data-node="<?php echo esc_attr($node_id); ?>"
    data-scheme="<?php echo esc_attr($scheme); ?>"
    data-next="<?php echo esc_attr((string) $next_start); ?>"
    data-more="<?php echo $more ? '1' : '0'; ?>"
    <?php
    // Every string the script shows comes from here, so it reaches Loco. A
    // string built in the JS bundle never does, and this panel ships in six
    // locales.
    ?>
    data-searching="<?php esc_attr_e('Searching…', 'granola'); ?>"
    data-next-label="<?php esc_attr_e('Next', 'granola'); ?>"
    data-failed="<?php esc_attr_e('We could not load those assets. Try again in a moment.', 'granola'); ?>"
    <?php
    /* translators: %1$d: page number. %2$d: number of assets on this page. %3$s: the search term. */
    $count_template = __('Page %1$d &middot; %2$d shown for &ldquo;%3$s&rdquo;', 'granola');
    ?>
    data-count-template="<?php echo esc_attr($count_template); ?>"
>

    <div class="mb-account-panel__head">
        <h2 class="mb-account-panel__title"><?php esc_html_e('Resources', 'granola'); ?></h2>
        <?php
        // The badge says who the panel is for. On a distributor's or
        // installer's screen "Millboard team" is simply wrong, so staff see
        // that and partners see the neutral wording.
        ?>
        <span class="mb-account-panel__badge">
            <?php
            echo \Granola\Components\WC_Account\is_staff()
                ? esc_html__('Millboard team', 'granola')
                : esc_html__('Trade account', 'granola');
            ?>
        </span>
    </div>

    <p class="mb-account-panel__intro">
        <?php esc_html_e('Photography, logos, brochures and product imagery, straight from Canto. You are signed in already, so there is no second login.', 'granola'); ?>
    </p>

    <?php if (!Client::is_configured() || $failed) : ?>

        <?php
        // "Open it directly" is only advice a member of staff can act on, so
        // the wording and the button both follow who is looking. Telling a
        // distributor to go to Canto when we cannot reach it sends them to a
        // sign-in wall they have no account for, which reads as a second
        // fault rather than a way round the first.
        $staff = \Granola\Components\WC_Account\is_staff();
        ?>
        <div class="mb-account-empty">
            <span class="mb-account-empty__rule" aria-hidden="true"></span>
            <h3 class="mb-account-empty__title"><?php esc_html_e('Resources unavailable', 'granola'); ?></h3>
            <p class="mb-account-empty__body">
                <?php
                if (!Client::is_configured()) {
                    esc_html_e('The connection to Canto is not set up on this site yet.', 'granola');
                } elseif ($staff) {
                    esc_html_e('We could not reach Canto just now. The library itself is fine, so it is worth opening it directly.', 'granola');
                } else {
                    esc_html_e('We could not load the assets just now. Please try again shortly.', 'granola');
                }
                ?>
            </p>
            <?php if ($staff) : ?>
                <a class="mb-account-btn" href="<?php echo esc_url($library_url); ?>" target="_blank" rel="noopener">
                    <?php esc_html_e('Open the brand library', 'granola'); ?>
                </a>
            <?php endif; ?>
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

            <p class="mb-assets__count" data-mb-assets-count>
                <?php
                if ('' !== $query) {
                    // A page is a page, not a total: saying "24 results" when
                    // Canto holds hundreds more would be a plain lie, and the
                    // real total cannot be shown anyway (it counts assets this
                    // person may not see, so it leaks how many there are).
                    if ($more) {
                        /* translators: %1$s: number of results shown so far, %2$s: the search term. */
                        $count_text = esc_html__('Showing the first %1$s for &ldquo;%2$s&rdquo;', 'granola');
                    } else {
                        /* translators: %1$s: number of results, %2$s: the search term. */
                        $count_text = esc_html__('%1$s results for &ldquo;%2$s&rdquo;', 'granola');
                    }

                    printf(
                        $count_text, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- both branches are esc_html__().
                        esc_html(number_format_i18n($found)),
                        esc_html($query)
                    );
                } elseif ('' !== $node_name) {
                    echo esc_html($node_name);
                }
                ?>
            </p>
        </div>

        <?php
        // The empty state and the grid are BOTH always rendered, the empty one
        // hidden when there is something to show. They used to be either/or,
        // which meant a page that first loaded with no results carried no grid
        // for the script to attach to, so live search was dead on exactly the
        // screen you most want to search again from -- a shared or bookmarked
        // URL for a term that no longer matches.
        ?>
        <div class="mb-account-empty" data-mb-assets-empty<?php echo $items ? ' hidden' : ''; ?>>
            <span class="mb-account-empty__rule" aria-hidden="true"></span>
            <h3 class="mb-account-empty__title"><?php esc_html_e('Nothing here', 'granola'); ?></h3>
            <p class="mb-account-empty__body">
                <?php esc_html_e('No assets matched. Try a different word, or browse the folders.', 'granola'); ?>
            </p>
        </div>

        <ul class="mb-assets__grid" data-mb-assets-grid>
                <?php
                // Escaped field by field inside render_items(); the panel and
                // the endpoint share it so a live result is the same markup.
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo render_items($items, $panel_url);
                ?>
            </ul>

            <?php if ($more) : ?>
                <p class="mb-assets__more">
                    <?php
                    // A plain link, so paging works with no JavaScript at all.
                    // The script binds to data-mb-assets-more and fetches the
                    // same page instead of following it.
                    ?>
                    <a
                        class="mb-account-btn mb-account-btn--ghost"
                        data-mb-assets-more
                        href="<?php echo esc_url(add_query_arg($more_args, $panel_url)); ?>"
                    >
                        <?php esc_html_e('Show more', 'granola'); ?>
                    </a>
                </p>
        <?php endif; ?>

        <?php
        // Staff only, for the same reason as the per-asset "In Canto" link:
        // Canto's own interface needs a Canto login, which a distributor or
        // installer does not have. Pointing them at it would only produce a
        // sign-in wall and a support call.
        ?>
        <?php if (\Granola\Components\WC_Account\is_staff()) : ?>
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

    <?php endif; ?>

</div>
