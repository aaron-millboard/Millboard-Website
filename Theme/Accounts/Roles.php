<?php

namespace Theme\Accounts;

/**
 * Partner account types, and what each may reach in My Account.
 *
 * Before this, Brand assets and Sample ordering were gated on "has a
 * millboard.com address, or can edit_posts". That let 22 people in — 19
 * administrators, two shop managers and an editor — and had no way at all to
 * describe a distributor or an installer, who both need the tools and are not
 * staff.
 *
 * So access is a CAPABILITY rather than a role name or an email domain. A
 * panel asks "can this user order samples", never "is this user a
 * distributor", which means the answer can be changed for one person, or for
 * a whole role, without touching a template.
 *
 *   mb_view_brand_assets   the Canto library
 *   mb_order_samples       the sample ordering tool
 *   mb_order_pos           the POS and marketing lines inside it
 *
 * Who gets what:
 *
 *   administrator        all three
 *   editor               all three   (Millboard staff)
 *   shop_manager         all three   (Millboard staff)
 *   millboard_distributor all three  — POS comes out of their marketing budget
 *   millboard_installer   assets + samples, NO POS — installers have none to order
 *   customer             none of them
 *
 * Both partner roles are built from the `customer` role, so a partner is
 * still an ordinary shop customer underneath: their orders, addresses and
 * checkout behave exactly as before and nothing about buying changes.
 */
class Roles
{
    public const CAP_BRAND_ASSETS = 'mb_view_brand_assets';
    public const CAP_ORDER_SAMPLES = 'mb_order_samples';
    public const CAP_ORDER_POS = 'mb_order_pos';

    public const ROLE_STAFF = 'millboard_staff';
    public const ROLE_DISTRIBUTOR = 'millboard_distributor';
    public const ROLE_INSTALLER = 'millboard_installer';
    public const ROLE_ARCHITECT = 'millboard_architect';
    public const ROLE_ASSET_VIEWER = 'millboard_asset_viewer';

    /**
     * Roles whose holders are Millboard rather than a partner. Wording only.
     *
     * @return string[]
     */
    public static function staff_roles(): array
    {
        return (array) \apply_filters('millboard/accounts/staff_roles', [
            'administrator',
            'editor',
            'shop_manager',
            self::ROLE_STAFF,
        ]);
    }

    /**
     * Bump when the map below changes, so existing sites pick it up.
     *
     * Roles live in the database (the `wp_user_roles` option), per site on a
     * multisite, so a code change alone does nothing until they are rewritten.
     */
    private const VERSION = '1.1.0';

    private const OPTION = 'millboard_roles_version';

    public static function init(): void
    {
        \add_action('init', [__CLASS__, 'maybe_install'], 5);
    }

    /**
     * The whole access map, in one place.
     *
     * @return array<string, string[]> role slug => capabilities
     */
    public static function map(): array
    {
        $everything = [self::CAP_BRAND_ASSETS, self::CAP_ORDER_SAMPLES, self::CAP_ORDER_POS];

        // No POS: no point of sale to stock.
        $no_pos = [self::CAP_BRAND_ASSETS, self::CAP_ORDER_SAMPLES];

        return (array) \apply_filters('millboard/accounts/capability_map', [
            'administrator' => $everything,
            'editor' => $everything,
            'shop_manager' => $everything,

            // Millboard people imported from the portal. The account tools,
            // and deliberately nothing else: no WooCommerce administration,
            // no site changes. 35 of them were administrators in Craft, which
            // is not a reason to make them administrators of a shop holding
            // 13,780 orders.
            self::ROLE_STAFF => $everything,

            self::ROLE_DISTRIBUTOR => $everything,
            self::ROLE_INSTALLER => $no_pos,

            // Architects spec projects and want samples to show clients.
            // Same access as an installer, kept as its own role because it is
            // a different relationship and may diverge.
            self::ROLE_ARCHITECT => $no_pos,

            // The portal's imageLibraryAccess group: the Canto library and
            // nothing else.
            self::ROLE_ASSET_VIEWER => [self::CAP_BRAND_ASSETS],
        ]);
    }

    /**
     * Create the roles and apply the capabilities, once per version per site.
     *
     * Writing roles is a database write, so it is guarded rather than run on
     * every request.
     */
    public static function maybe_install(bool $force = false): void
    {
        if (!$force && self::VERSION === \get_option(self::OPTION)) {
            return;
        }

        self::install();

        \update_option(self::OPTION, self::VERSION, false);
    }

    public static function install(): void
    {
        // The partner roles are a customer plus the extra capabilities, so a
        // partner keeps every ordinary shop behaviour.
        $customer = \get_role('customer');
        $base = $customer instanceof \WP_Role ? $customer->capabilities : ['read' => true];

        $labels = [
            self::ROLE_STAFF => 'Millboard staff',
            self::ROLE_DISTRIBUTOR => 'Millboard distributor',
            self::ROLE_INSTALLER => 'Millboard installer',
            self::ROLE_ARCHITECT => 'Millboard architect',
            self::ROLE_ASSET_VIEWER => 'Millboard brand assets',
        ];

        foreach ($labels as $slug => $label) {
            if (!\get_role($slug)) {
                \add_role($slug, $label, $base);
            }
        }

        $all = [self::CAP_BRAND_ASSETS, self::CAP_ORDER_SAMPLES, self::CAP_ORDER_POS];

        foreach (self::map() as $role_slug => $caps) {
            $role = \get_role($role_slug);

            if (!$role instanceof \WP_Role) {
                continue;
            }

            // Remove first, so taking a capability away in the map above
            // actually takes it away on a site that already had it.
            foreach ($all as $cap) {
                $role->remove_cap($cap);
            }

            foreach ($caps as $cap) {
                $role->add_cap($cap);
            }
        }
    }

    /**
     * Remove everything this class added. Not wired to anything: it exists so
     * the change can be undone deliberately rather than by hand.
     */
    public static function uninstall(): void
    {
        foreach ([self::CAP_BRAND_ASSETS, self::CAP_ORDER_SAMPLES, self::CAP_ORDER_POS] as $cap) {
            foreach (\array_keys(\wp_roles()->roles) as $role_slug) {
                $role = \get_role($role_slug);

                if ($role instanceof \WP_Role) {
                    $role->remove_cap($cap);
                }
            }
        }

        \delete_option(self::OPTION);
    }

    /**
     * Is this user Millboard staff, as opposed to a partner?
     *
     * Only used for wording — a partner and a member of staff see the same
     * panels, but the "Millboard team" badge should not appear on a
     * distributor's screen.
     */
    public static function is_staff(?int $user_id = null): bool
    {
        $user_id = $user_id ?: \get_current_user_id();

        if (!$user_id) {
            return false;
        }

        $user = \get_userdata($user_id);
        $staff = false;

        if ($user instanceof \WP_User) {
            // An explicit list, not "anything that is not a partner": a new
            // partner role added later would otherwise silently read as staff
            // and be badged "Millboard team" on a partner's screen.
            $staff = (bool) \array_intersect($user->roles, self::staff_roles());
        }

        return (bool) \apply_filters('millboard/accounts/is_staff', $staff, $user_id);
    }
}
