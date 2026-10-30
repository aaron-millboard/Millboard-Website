<?php

namespace Theme\Plugins;

/**
 * Stops the Addressy/Loqate lookup treating the company box as a search box.
 *
 * Aaron, 30 Oct 2026, on the German sample order form: "theres a field called
 * 'Firmenname' which is company name but when you start typing the auto address
 * complete starts searching for an address? this isnt right at all ... that
 * should only happen on the first line of the address field".
 *
 * WHY IT HAPPENS. WooCommerce Address Validation maps the company input to
 * Loqate's `Company` field in mode `DEFAULT|PRESERVE`, and the Loqate SDK the
 * site loads defines
 *
 *     fieldMode = { NONE:0, SEARCH:1, POPULATE:2, DEFAULT:3, PRESERVE:4, COUNTRY:8 }
 *
 * so DEFAULT is SEARCH|POPULATE and the company box is a search box by design:
 * the SDK pushes every element carrying the SEARCH bit into its search list.
 * Loqate can genuinely look a business up by name, so this is the plugin's
 * intent, not a bug in it. It is simply wrong for us, because the name typed
 * there is the CUSTOMER'S company, not somewhere to look up, and the address
 * dropdown lands over the next field.
 *
 * THE FIX is the mode, not the field. `POPULATE|PRESERVE` drops the SEARCH bit,
 * so typing does nothing, while POPULATE still fills the company in when a
 * picked address carries one and PRESERVE stops that overwriting a name the
 * user typed first. Address line 1 is left alone: it has no mode at all, which
 * the SDK reads as DEFAULT, so it goes on searching. That is the one box Aaron
 * wants searching and the only one that now does.
 *
 * WHERE IT SHOWS. Every market's sample order form, which is the screen that
 * prompted this, plus the French shop checkout. Those are the only screens with
 * a company box at all: the other five shop checkouts have the field removed in
 * Checkout Field Editor, and no market's My Account edit-address form has one.
 *
 * Keyed on Loqate's field name rather than the input's id, because the plugin
 * builds that id with a separator that differs between the classic checkout
 * (billing_company) and the block checkout (billing-company).
 *
 * This is a workaround for a third-party default. If the plugin stops shipping
 * the company field in a searching mode, delete this class and its init() call.
 */
class AddressLookupFields
{
    /** Loqate's own name for the field we are demoting. */
    public const LOQATE_COMPANY = 'Company';

    /** SEARCH dropped; POPULATE and PRESERVE kept. */
    public const FILL_ONLY = 'POPULATE|PRESERVE';

    public static function init(): void
    {
        \add_filter('wc_address_validation_addressy_addresses', [__CLASS__, 'company_does_not_search']);
    }

    /**
     * @param mixed $addresses address id => list of mapped fields
     * @return mixed
     */
    public static function company_does_not_search($addresses)
    {
        if (!\is_array($addresses)) {
            return $addresses;
        }

        foreach ($addresses as $address_id => $fields) {
            if (!\is_array($fields)) {
                continue;
            }

            foreach ($fields as $i => $field) {
                if (\is_array($field) && self::LOQATE_COMPANY === ($field['field'] ?? '')) {
                    $addresses[$address_id][$i]['mode'] = self::FILL_ONLY;
                }
            }
        }

        return $addresses;
    }
}
