<?php

namespace Theme\Canto;

/**
 * Chosen cover images for Canto folders and albums.
 *
 * Client::cover() picks a cover automatically: the first partner-visible image
 * inside a container. That is right most of the time and costs nothing, but
 * "first by name" is not art direction, and the tiles people actually look at
 * deserve a deliberate picture. This stores that choice.
 *
 * An ASSET ID is stored, never a URL. Canto's direct URLs carry a signature it
 * can rotate, so a stored URL would work until one day it quietly did not. An
 * id is resolved through the partner index at render time, which also means a
 * cover that later becomes restricted falls back to the automatic pick instead
 * of 404ing on a tile.
 *
 * The candidates offered are exactly the index entries for that container, so
 * nothing can be chosen that a partner is not allowed to see. That is the same
 * rule the automatic pick follows, and it keeps the two consistent.
 */
final class Covers
{
    public const OPTION = 'millboard_canto_covers';

    private const MENU_SLUG = 'millboard-canto-covers';

    public static function init(): void
    {
        \add_action('admin_menu', [self::class, 'register_page']);
        \add_action('admin_post_millboard_canto_cover', [self::class, 'handle_save']);
    }

    /**
     * container id => asset id.
     *
     * @return array<string,string>
     */
    public static function all(): array
    {
        $stored = \get_option(self::OPTION, []);

        if (!\is_array($stored)) {
            return [];
        }

        $clean = [];

        foreach ($stored as $container => $asset) {
            $container = (string) $container;
            $asset = (string) $asset;

            if ('' !== $container && '' !== $asset) {
                $clean[$container] = $asset;
            }
        }

        return $clean;
    }

    /**
     * The asset chosen for this container, or '' when it is automatic.
     */
    public static function for_container(string $container_id): string
    {
        return self::all()[$container_id] ?? '';
    }

    /**
     * Turn a stored choice into a URL, whichever library it came from.
     *
     * A WordPress attachment is stored as `wp:123`; anything else is a Canto
     * asset id. The prefix is what keeps the two apart, and an id that no
     * longer resolves returns '' so the tile falls back to the automatic pick
     * rather than showing a broken image.
     *
     * ⚠️ A WordPress image is NOT subject to the Canto usage-rights gate.
     * Whoever picks one is asserting it is cleared for a partner-facing page,
     * which is why the Canto library is offered first and this is the fallback
     * for folders Canto cannot cover.
     */
    public static function resolve_url(string $stored): string
    {
        if ('' === $stored) {
            return '';
        }

        if (0 === \strpos($stored, 'wp:')) {
            $attachment = (int) \substr($stored, 3);

            if ($attachment <= 0 || !\wp_attachment_is_image($attachment)) {
                return '';
            }

            return (string) \wp_get_attachment_image_url($attachment, 'large');
        }

        return Client::asset_url($stored);
    }

    /**
     * Images that may be used as this container's cover.
     *
     * Read from the partner index, which is already cached, so opening the
     * picker costs no Canto requests.
     *
     * @return array<int,array{id: string, name: string, url: string}>
     */
    public static function candidates(string $container_id): array
    {
        $out = [];

        foreach ((array) Client::partner_index() as $asset) {
            if (!\is_array($asset) || 'image' !== (string) ($asset['scheme'] ?? '')) {
                continue;
            }

            if (!\in_array($container_id, \array_map('strval', (array) ($asset['albums'] ?? [])), true)) {
                continue;
            }

            $url = (string) ($asset['url']['directUrlPreview'] ?? '');

            if ('' === $url) {
                continue;
            }

            $out[] = ['id' => (string) $asset['id'], 'name' => (string) ($asset['name'] ?? ''), 'url' => $url];
        }

        return $out;
    }

    /**
     * Images anywhere in the library whose file name matches.
     *
     * Searched over the cached partner index, so it costs no Canto requests
     * and can only return something a partner is allowed to see. Capped,
     * because a bare search can match hundreds and a page of hundreds of
     * thumbnails helps nobody.
     *
     * @return array<int,array{id: string, name: string, url: string}>
     */
    public static function search_candidates(string $term, int $limit = 60): array
    {
        $term = \trim($term);

        if ('' === $term) {
            return [];
        }

        $out = [];

        foreach ((array) Client::partner_index() as $asset) {
            if (!\is_array($asset) || 'image' !== (string) ($asset['scheme'] ?? '')) {
                continue;
            }

            $name = (string) ($asset['name'] ?? '');

            if (false === \stripos($name, $term)) {
                continue;
            }

            $url = (string) ($asset['url']['directUrlPreview'] ?? '');

            if ('' === $url) {
                continue;
            }

            $out[] = ['id' => (string) $asset['id'], 'name' => $name, 'url' => $url];

            if (\count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    // ------------------------------------------------------------------
    // Admin screen
    // ------------------------------------------------------------------

    public static function register_page(): void
    {
        // Nested under ACF's options page where the rest of the site's
        // settings live, so it is where someone would look. Falls back to
        // Settings if ACF is not active, rather than vanishing.
        $parent = \menu_page_url('acf-options', false) ? 'acf-options' : 'options-general.php';

        \add_submenu_page(
            $parent,
            \__('Resources cover images', 'granola'),
            \__('Resources covers', 'granola'),
            'manage_options',
            self::MENU_SLUG,
            [self::class, 'render_page']
        );
    }

    public static function render_page(): void
    {
        if (!\current_user_can('manage_options')) {
            return;
        }

        // wp.media is not on an admin page unless asked for, and the picker
        // button silently does nothing without it.
        \wp_enqueue_media();

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation.
        $container = isset($_GET['container']) ? \sanitize_text_field(\wp_unslash($_GET['container'])) : '';

        echo '<div class="wrap">';

        if ('' !== $container) {
            self::render_picker($container);
        } else {
            self::render_list();
        }

        echo '</div>';
    }

    /**
     * Every container, with whatever cover it currently shows.
     */
    private static function render_list(): void
    {
        $overrides = self::all();

        echo '<h1>' . \esc_html__('Resources cover images', 'granola') . '</h1>';
        echo '<p>' . \esc_html__('Each folder and album in My Account shows a cover image. By default it is the first image inside it. Choose one here to pin a specific picture instead.', 'granola') . '</p>';

        $rows = self::flatten(Client::tree());

        if (!$rows) {
            echo '<div class="notice notice-warning"><p>' . \esc_html__('The Canto library could not be read, so there is nothing to show.', 'granola') . '</p></div>';

            return;
        }

        echo '<table class="wp-list-table widefat striped"><thead><tr>';
        echo '<th style="width:90px">' . \esc_html__('Cover', 'granola') . '</th>';
        echo '<th>' . \esc_html__('Folder or album', 'granola') . '</th>';
        echo '<th style="width:110px">' . \esc_html__('Source', 'granola') . '</th>';
        echo '<th style="width:140px"></th>';
        echo '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $id = $row['id'];
            $chosen = $overrides[$id] ?? '';
            $url = Client::cover($row['scheme'], $id);

            $link = \add_query_arg(
                ['page' => self::MENU_SLUG, 'container' => $id],
                \admin_url('admin.php')
            );

            echo '<tr>';

            echo '<td>';
            if ('' !== $url) {
                \printf(
                    '<img src="%s" alt="" style="width:72px;height:54px;object-fit:cover;display:block" />',
                    \esc_url($url)
                );
            } else {
                echo '<span style="color:#888">&mdash;</span>';
            }
            echo '</td>';

            \printf(
                '<td>%s<strong>%s</strong></td>',
                \str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', (int) $row['depth']),
                \esc_html($row['name'])
            );

            \printf(
                '<td>%s</td>',
                '' !== $chosen
                    ? '<span style="color:#135e96">' . \esc_html__('Chosen', 'granola') . '</span>'
                    : ('' !== $url ? \esc_html__('Automatic', 'granola') : \esc_html__('None', 'granola'))
            );

            \printf('<td><a class="button" href="%s">%s</a></td>', \esc_url($link), \esc_html__('Choose image', 'granola'));

            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * The images available for one container.
     */
    private static function render_picker(string $container_id): void
    {
        $chosen = self::for_container($container_id);
        $name = self::name_of($container_id);
        $back = \add_query_arg(['page' => self::MENU_SLUG], \admin_url('admin.php'));

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter.
        $search = isset($_GET['s']) ? \sanitize_text_field(\wp_unslash($_GET['s'])) : '';

        \printf('<h1>%s</h1>', \esc_html($name ?: $container_id));
        \printf('<p><a href="%s">&larr; %s</a></p>', \esc_url($back), \esc_html__('All folders', 'granola'));

        /*
         * The container's own images first, because that is usually what you
         * want, then the rest of the library behind a search.
         *
         * The first version offered ONLY the container's own images and said
         * "nothing here can be used" when it had none, which is a dead end:
         * a cover is decoration and there is no reason it has to live inside
         * the thing it decorates. Envello Coloured Head Screws has exactly one
         * asset, internal-only and awaiting marketing approval, so the folder
         * could never have had a cover at all.
         */
        $own = self::candidates($container_id);
        $library = '' !== $search ? self::search_candidates($search) : [];

        // Search form, always available, so there is never a dead end.
        echo '<form method="get" style="margin:12px 0">';
        echo '<input type="hidden" name="page" value="' . \esc_attr(self::MENU_SLUG) . '" />';
        \printf('<input type="hidden" name="container" value="%s" />', \esc_attr($container_id));
        \printf(
            '<input type="search" name="s" value="%s" placeholder="%s" style="width:320px" /> ',
            \esc_attr($search),
            \esc_attr__('Search the whole library by file name…', 'granola')
        );
        \submit_button(\__('Search', 'granola'), 'secondary', '', false);
        echo '</form>';

        echo '<form method="post" action="' . \esc_url(\admin_url('admin-post.php')) . '">';
        \wp_nonce_field('millboard_canto_cover');
        echo '<input type="hidden" name="action" value="millboard_canto_cover" />';
        \printf('<input type="hidden" name="container" value="%s" />', \esc_attr($container_id));

        $grid = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin:16px 0">';

        echo '<h2>' . \esc_html__('In this folder', 'granola') . '</h2>';
        echo $grid; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup.

        // "Automatic" first, so clearing a choice is as easy as making one.
        self::render_choice('', \__('Automatic (first image)', 'granola'), '', '' === $chosen);

        foreach ($own as $candidate) {
            self::render_choice($candidate['id'], $candidate['name'], $candidate['url'], $chosen === $candidate['id']);
        }

        echo '</div>';

        if (!$own) {
            echo '<p class="description">';
            echo \esc_html__('This folder has no images of its own that reach distributors and installers, so search the library below instead.', 'granola');
            echo '</p>';
        }

        if ('' !== $search) {
            \printf(
                '<h2>%s</h2>',
                \esc_html(
                    \sprintf(
                        /* translators: %1$d: number of matches. %2$s: the search term. */
                        \__('%1$d match for &ldquo;%2$s&rdquo;', 'granola'),
                        \count($library),
                        $search
                    )
                )
            );

            if ($library) {
                echo $grid; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup.

                foreach ($library as $candidate) {
                    self::render_choice($candidate['id'], $candidate['name'], $candidate['url'], $chosen === $candidate['id']);
                }

                echo '</div>';
            }
        }

        /*
         * The escape hatch. Canto cannot cover every folder: Envello Coloured
         * Head Screws holds one asset, internal-only and awaiting marketing
         * approval, so no amount of searching the library helps.
         *
         * Deliberately last and labelled, because an image from here does NOT
         * pass the Canto usage-rights gate. Whoever picks one is asserting it
         * is cleared for a partner-facing page.
         */
        $wp_id = 0 === \strpos($chosen, 'wp:') ? (int) \substr($chosen, 3) : 0;

        echo '<h2>' . \esc_html__('Or use a WordPress image', 'granola') . '</h2>';
        echo '<p class="description">';
        echo \esc_html__('For folders Canto has no suitable image for. These are not checked against Canto usage rights, so only pick one you know is cleared for partners.', 'granola');
        echo '</p>';

        echo '<p>';
        \printf(
            '<label style="display:inline-flex;align-items:center;gap:8px"><input type="radio" name="asset" value="%s" %s /> %s</label>',
            $wp_id ? 'wp:' . $wp_id : 'wp:0',
            \checked($wp_id > 0, true, false),
            \esc_html__('Use the image below', 'granola')
        );
        echo '</p>';

        \printf(
            '<p><input type="hidden" id="mb-cover-wp-id" name="wp_attachment" value="%d" />'
                . '<button type="button" class="button" id="mb-cover-pick">%s</button></p>',
            $wp_id,
            \esc_html__('Choose from media library', 'granola')
        );

        echo '<p id="mb-cover-wp-preview">';
        if ($wp_id) {
            echo \wp_get_attachment_image($wp_id, 'medium', false, ['style' => 'max-width:260px;height:auto']);
        }
        echo '</p>';

        \submit_button(\__('Save cover', 'granola'));
        echo '</form>';

        self::render_picker_script();
    }

    /**
     * The media-library button.
     *
     * Inline rather than a build-step asset: it is a dozen lines that only
     * ever run on this one admin screen, and routing it through the theme
     * build would make it something to find rather than something to read.
     */
    private static function render_picker_script(): void
    {
        ?>
        <script>
        (function () {
            var button = document.getElementById('mb-cover-pick');
            var field = document.getElementById('mb-cover-wp-id');
            var preview = document.getElementById('mb-cover-wp-preview');

            if (!button || !field || !window.wp || !window.wp.media) {
                return;
            }

            var frame;

            button.addEventListener('click', function () {
                if (!frame) {
                    frame = window.wp.media({
                        title: button.textContent,
                        library: { type: 'image' },
                        multiple: false
                    });

                    frame.on('select', function () {
                        var picked = frame.state().get('selection').first().toJSON();

                        field.value = picked.id;

                        // Tick the radio for the person, so choosing an image
                        // and forgetting to select it cannot silently do nothing.
                        var radio = document.querySelector('input[name="asset"][value^="wp:"]');

                        if (radio) {
                            radio.value = 'wp:' + picked.id;
                            radio.checked = true;
                        }

                        var size = (picked.sizes && (picked.sizes.medium || picked.sizes.full)) || null;
                        preview.innerHTML = size
                            ? '<img src="' + size.url + '" style="max-width:260px;height:auto" alt="" />'
                            : '';
                    });
                }

                frame.open();
            });
        }());
        </script>
        <?php
    }

    private static function render_choice(string $value, string $label, string $url, bool $checked): void
    {
        echo '<label style="display:block;cursor:pointer;border:2px solid ' . ($checked ? '#2271b1' : 'transparent') . ';padding:6px;background:#fff">';
        \printf(
            '<input type="radio" name="asset" value="%s" %s style="margin-bottom:6px" />',
            \esc_attr($value),
            \checked($checked, true, false)
        );

        if ('' !== $url) {
            \printf('<img src="%s" alt="" style="width:100%%;height:120px;object-fit:cover;display:block" />', \esc_url($url));
        } else {
            echo '<span style="display:block;height:120px;background:#f0f0f1"></span>';
        }

        \printf('<span style="display:block;font-size:11px;word-break:break-all;margin-top:4px">%s</span>', \esc_html($label));
        echo '</label>';
    }

    public static function handle_save(): void
    {
        if (!\current_user_can('manage_options')) {
            \wp_die(\esc_html__('You cannot change this.', 'granola'));
        }

        \check_admin_referer('millboard_canto_cover');

        $container = isset($_POST['container']) ? \sanitize_text_field(\wp_unslash($_POST['container'])) : '';
        $asset = isset($_POST['asset']) ? \sanitize_text_field(\wp_unslash($_POST['asset'])) : '';

        /*
         * The WordPress option carries the attachment id in its own field, so
         * the radio only has to say "use the WordPress one". Taking the id
         * from here rather than from the radio's value means the two cannot
         * disagree if someone picks an image and the radio did not update.
         */
        if (0 === \strpos($asset, 'wp:')) {
            $attachment = isset($_POST['wp_attachment']) ? (int) $_POST['wp_attachment'] : 0;

            // Nothing picked, or not an image: treat as automatic rather than
            // storing a reference to something that cannot render.
            $asset = ($attachment > 0 && \wp_attachment_is_image($attachment)) ? 'wp:' . $attachment : '';
        }

        if ('' !== $container) {
            $all = self::all();

            // An empty asset means "go back to automatic", so the row is
            // removed rather than stored as a blank nobody can interpret later.
            if ('' === $asset) {
                unset($all[$container]);
            } else {
                $all[$container] = $asset;
            }

            \update_option(self::OPTION, $all, false);
        }

        \wp_safe_redirect(\add_query_arg(['page' => self::MENU_SLUG], \admin_url('admin.php')));
        exit;
    }

    /**
     * The tree as a flat list, keeping the nesting as a depth number.
     *
     * @param array<int,array<string,mixed>> $nodes
     * @return array<int,array{id: string, name: string, scheme: string, depth: int}>
     */
    private static function flatten(array $nodes, int $depth = 0): array
    {
        $out = [];

        foreach ($nodes as $node) {
            if (!\is_array($node)) {
                continue;
            }

            $scheme = (string) ($node['scheme'] ?? '');

            if (\in_array($scheme, ['folder', 'album'], true)) {
                $out[] = [
                    'id' => (string) ($node['id'] ?? ''),
                    'name' => (string) ($node['name'] ?? ''),
                    'scheme' => $scheme,
                    'depth' => $depth,
                ];
            }

            if (!empty($node['children'])) {
                $out = \array_merge($out, self::flatten((array) $node['children'], $depth + 1));
            }
        }

        return $out;
    }

    private static function name_of(string $container_id): string
    {
        foreach (self::flatten(Client::tree()) as $row) {
            if ($row['id'] === $container_id) {
                return $row['name'];
            }
        }

        return '';
    }
}
