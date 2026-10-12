<?php

namespace Theme\Canto;

/**
 * Who may see which Canto asset in My Account.
 *
 * Canto's own permissions cannot do this: the site reads the library as one
 * admin-scoped API client, so every asset is readable by the server and the
 * only gate is the one applied here, before anything is rendered.
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

        $additional = $item['additional'] ?? null;
        $value = \is_array($additional) ? ($additional[self::FIELD] ?? null) : null;
        $values = \array_values(\array_filter(\array_map('strval', (array) $value), 'strlen'));

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
     * @param array<int,array<string,mixed>> $items
     * @param string[]                       $audiences
     * @return array<int,array<string,mixed>>
     */
    public static function filter(array $items, array $audiences): array
    {
        return \array_values(\array_filter($items, static fn ($item) => \is_array($item) && self::visible($item, $audiences)));
    }
}
