<?php

namespace Theme\Canto;

/**
 * Who may see which Canto asset in My Account.
 *
 * Canto's own permissions cannot do this: the site reads the library as one
 * admin-scoped API client, so every asset is readable by the server and the
 * only gate is the one applied here, before anything is rendered.
 *
 * There are TWO gates and an asset must pass both.
 *
 * 1. Usage rights: is this cleared to leave Millboard at all? See USAGE_FIELD.
 *    Applies to staff as well, because the panel hands out signed links that
 *    work for anyone holding them.
 * 2. Portal access: who may see it. Described below.
 *
 * The rule is driven by the "Portal Access" multiple-choice field on each asset:
 *
 *   Distributor | Installer | Internal staff   audiences allowed to see it
 *   Embargoed                                  hidden from EVERYONE, staff included
 *   (empty)                                    hidden from everyone
 *
 * Empty hides on purpose. A new upload, or an asset nobody has classified yet,
 * fails closed instead of leaking.
 *
 * Staff are the one superset: they see everything that carries any audience
 * value, so an asset marked Distributor-only does not vanish from the people
 * who run the library. Embargoed still beats that.
 *
 * Pure functions, no WordPress calls in the rule itself, so it can be checked
 * with plain PHP (docs/canto-access-selfcheck.php).
 */
final class Access
{
    public const FIELD = 'Portal Access';

    public const EMBARGOED = 'Embargoed';
    public const STAFF = 'Internal staff';
    public const DISTRIBUTOR = 'Distributor';
    public const INSTALLER = 'Installer';

    /**
     * The second gate: usage rights.
     *
     * Portal Access says who may see an asset. This says whether the asset is
     * cleared to leave Millboard at all. Both must pass.
     *
     * An asset shows only when this field holds exactly one value and that
     * value is OPEN_USAGE. Anything else -- empty, "Internal use or project
     * reference only", "Approval needed from Global Marketing", "Usage rights
     * unknown", or OPEN_USAGE sitting alongside any of them -- is hidden.
     *
     * Deliberately strict, and deliberately not a blocklist. A blocklist has
     * to be extended every time someone adds an option to the field in Canto,
     * and until it is, the new option reads as permitted. Requiring the one
     * known-good value means a new option fails closed on the day it appears.
     *
     * This applies to staff as well. Resources hands out signed download links
     * that work for anyone holding them, so "staff can be trusted" is not the
     * question; the asset either cleared for release or it did not.
     */
    public const USAGE_FIELD = 'Usage Rights';
    public const OPEN_USAGE = 'Global open usage-all channels';

    private const CONTAINERS = ['folder', 'album'];

    /**
     * Role slug => audience. Anything not listed gets no audience, so a role
     * added later sees nothing until it is mapped here.
     *
     * Architects and the library-only role are outside the three audiences the
     * field offers, so they read as the closest external one, Installer.
     */
    private const ROLE_AUDIENCE = [
        'millboard_distributor' => self::DISTRIBUTOR,
        'millboard_installer' => self::INSTALLER,
        'millboard_architect' => self::INSTALLER,
        'millboard_asset_viewer' => self::INSTALLER,
    ];

    /**
     * @param string[] $roles       The user's role slugs.
     * @param string[] $staff_roles Roles that count as Millboard staff.
     * @return string[]
     */
    public static function audiences(array $roles, array $staff_roles): array
    {
        $audiences = [];

        foreach ($roles as $role) {
            if (\in_array($role, $staff_roles, true)) {
                $audiences[] = self::STAFF;
            } elseif (isset(self::ROLE_AUDIENCE[$role])) {
                $audiences[] = self::ROLE_AUDIENCE[$role];
            }
        }

        return \array_values(\array_unique($audiences));
    }

    /**
     * The signed-in user's audiences. Nobody signed in has none.
     *
     * @return string[]
     */
    public static function for_current_user(): array
    {
        $user = \wp_get_current_user();

        if (!$user->exists()) {
            return [];
        }

        return self::audiences((array) $user->roles, \Theme\Accounts\Roles::staff_roles());
    }

    /**
     * May a viewer with these audiences see this API item?
     *
     * Folders and albums are navigation, not assets, and always pass: what is
     * inside them is filtered when it is listed.
     *
     * @param array<string,mixed> $item
     * @param string[]            $audiences
     */
    public static function visible(array $item, array $audiences): bool
    {
        if (\in_array((string) ($item['scheme'] ?? ''), self::CONTAINERS, true)) {
            return true;
        }

        if (!self::usage_cleared($item)) {
            return false;
        }

        $values = self::field_values($item, self::FIELD);

        if (!$values || \in_array(self::EMBARGOED, $values, true)) {
            return false;
        }

        $known = [self::STAFF, self::DISTRIBUTOR, self::INSTALLER];

        if (\in_array(self::STAFF, $audiences, true)) {
            return (bool) \array_intersect($values, $known);
        }

        return (bool) \array_intersect($values, $audiences);
    }

    /**
     * Does this asset reach any external audience at all?
     *
     * Used to build the partner index: an asset that is Embargoed, empty or
     * staff-only can never be shown to a partner, so it is never stored there.
     *
     * @param array<string,mixed> $item
     */
    public static function partner_visible(array $item): bool
    {
        if (\in_array((string) ($item['scheme'] ?? ''), self::CONTAINERS, true)) {
            return false;
        }

        if (!self::usage_cleared($item)) {
            return false;
        }

        $values = self::field_values($item, self::FIELD);

        return !\in_array(self::EMBARGOED, $values, true)
            && (bool) \array_intersect($values, [self::DISTRIBUTOR, self::INSTALLER]);
    }

    /**
     * Is this asset cleared for release outside Millboard?
     *
     * True only when Usage Rights holds exactly one value and it is
     * OPEN_USAGE. See the constant for why a single value is required rather
     * than merely containing it: "Global open usage-all channels" together
     * with "Internal use or project reference only" is a contradiction, and
     * the restrictive half is the half that matters.
     *
     * @param array<string,mixed> $item
     */
    public static function usage_cleared(array $item): bool
    {
        return [self::OPEN_USAGE] === self::field_values($item, self::USAGE_FIELD);
    }

    /**
     * The non-empty values of one Canto multiple-choice field, as strings.
     *
     * Canto answers with a list, a bare string, or nothing at all depending on
     * how many options are ticked, so every caller normalising this by hand is
     * a bug waiting to happen.
     *
     * @param array<string,mixed> $item
     * @return string[]
     */
    private static function field_values(array $item, string $field): array
    {
        $additional = $item['additional'] ?? null;
        $value = \is_array($additional) ? ($additional[$field] ?? null) : null;

        return \array_values(\array_filter(\array_map('strval', (array) $value), 'strlen'));
    }

    /**
     * The few fields the brand-assets panel renders, plus the ids of every
     * folder and album the asset sits in (so a listing can be answered from
     * the index without asking Canto again).
     *
     * @param array<string,mixed> $item
     * @return array<string,mixed>
     */
    public static function compact(array $item): array
    {
        $albums = [];

        foreach ((array) ($item['relatedAlbums'] ?? []) as $album) {
            foreach (\explode('/', (string) ($album['idPath'] ?? '')) as $id) {
                if ('' !== $id) {
                    $albums[$id] = true;
                }
            }
        }

        $url = (array) ($item['url'] ?? []);

        return [
            'id' => (string) ($item['id'] ?? ''),
            'scheme' => (string) ($item['scheme'] ?? ''),
            'name' => (string) ($item['name'] ?? ''),
            'url' => [
                'previewURI240' => (string) ($url['previewURI240'] ?? ''),
                // The tile and the View overlay both prefer this 800px render;
                // without it a partner-index asset falls back to the 240px
                // thumbnail and softens in a stretched column.
                'directUrlPreview' => (string) ($url['directUrlPreview'] ?? ''),
                'directUrlOriginal' => (string) ($url['directUrlOriginal'] ?? ''),
                'detail' => (string) ($url['detail'] ?? ''),
            ],
            /*
             * BOTH gating fields, or the record cannot be filtered later.
             * A compact record is re-checked by visible() when the partner
             * index is read, so dropping Usage Rights here would make every
             * indexed asset fail usage_cleared() and the panel would come back
             * empty for partners. The selfcheck covers exactly that.
             */
            'additional' => [
                self::FIELD => (array) ($item['additional'][self::FIELD] ?? []),
                self::USAGE_FIELD => (array) ($item['additional'][self::USAGE_FIELD] ?? []),
            ],
            'albums' => \array_map('strval', \array_keys($albums)),
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @param string[]                       $audiences
     * @return array<int,array<string,mixed>>
     */
    public static function filter(array $items, array $audiences): array
    {
        return \array_values(\array_filter($items, static fn ($item) => \is_array($item) && self::visible($item, $audiences)));
    }
}
