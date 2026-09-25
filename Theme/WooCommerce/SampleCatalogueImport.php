<?php

namespace Theme\WooCommerce;

/**
 * Create the sample-ordering catalogue as WooCommerce products.
 *
 * 284 of the portal's 373 live SKUs do not exist here: sample boxes, presenter
 * packs, single sample pieces, teaser boards, name plates. All standalone
 * items, so all simple products — none of them is a variation of a board the
 * way the existing 90 are.
 *
 * IDEMPOTENT, and that is the point. Everything is keyed on SKU: a second run
 * updates rather than duplicates, so when the audit comes back with costs,
 * categories and keep-or-retire decisions, applying them is another run of
 * the same importer rather than a rebuild.
 *
 * It will only ever touch a product it created itself, marked with
 * `_millboard_portal_sku`. A product that already existed before this — the
 * 89 that do — is left completely alone.
 *
 * ⚠️ Every product is created HIDDEN from the catalogue. These are ordering
 * lines for the account area, not shop stock: they must not appear in shop
 * listings, search or the sitemap.
 */
class SampleCatalogueImport
{
    public const META_PORTAL_SKU = '_millboard_portal_sku';
    public const META_PORTAL_TYPE = '_millboard_portal_section';
    public const META_MAX_QTY = '_millboard_max_qty';

    /** Product category that marks a line as POS / marketing stock. */
    public const CAT_POS = 'marketing-pos';

    /** Product category every imported line sits in. */
    public const CAT_ROOT = 'sample-ordering';

    /**
     * @param array<int,array<string,mixed>> $records Rows of {sku,title,section,max,min}
     * @param bool $commit False (the default) changes nothing.
     * @return array<string,mixed>
     */
    public static function run(array $records, bool $commit = false): array
    {
        $report = [
            'mode' => $commit ? 'COMMIT' : 'DRY RUN',
            'total' => \count($records),
            'create' => 0,
            'update' => 0,
            'skip_existing_not_ours' => 0,
            'skipped' => [],
            'by_section' => [],
            'errors' => [],
        ];

        if (!\function_exists('wc_get_product_id_by_sku')) {
            $report['errors'][] = 'WooCommerce is not loaded';
            return $report;
        }

        $terms = $commit ? self::ensure_terms() : [];

        foreach ($records as $r) {
            $sku = \strtoupper(\trim((string) ($r['sku'] ?? '')));
            $title = \trim((string) ($r['title'] ?? ''));

            if ('' === $sku || '' === $title) {
                $report['skipped']['no sku or title'] = ($report['skipped']['no sku or title'] ?? 0) + 1;
                continue;
            }

            $existing_id = (int) \wc_get_product_id_by_sku($sku);

            if ($existing_id && !\get_post_meta($existing_id, self::META_PORTAL_SKU, true)) {
                // Already in WooCommerce and not ours. Leave it be.
                $report['skip_existing_not_ours']++;
                continue;
            }

            $section = (string) ($r['section'] ?? '');
            $report['by_section'][$section] = ($report['by_section'][$section] ?? 0) + 1;

            if ($existing_id) {
                $report['update']++;
            } else {
                $report['create']++;
            }

            if (!$commit) {
                continue;
            }

            $result = self::upsert($existing_id, $sku, $title, $r, $terms);

            if (\is_wp_error($result)) {
                $report['errors'][] = $sku . ': ' . $result->get_error_message();
            }
        }

        return $report;
    }

    /**
     * Create or update one product.
     *
     * @return int|\WP_Error
     */
    private static function upsert(int $id, string $sku, string $title, array $r, array $terms)
    {
        $product = $id ? \wc_get_product($id) : new \WC_Product_Simple();

        if (!$product instanceof \WC_Product) {
            return new \WP_Error('mb_sof_import', 'could not load or create a product');
        }

        $is_pos = 'internalMarketingCatalogue' === ($r['section'] ?? '');

        $product->set_name($title);
        $product->set_sku($sku);
        $product->set_status('publish');

        // Not shop stock. Hidden keeps it out of listings, search and the
        // sitemap while leaving it orderable from the account tool.
        $product->set_catalog_visibility('hidden');

        $product->set_manage_stock(false);
        $product->set_stock_status('instock');
        $product->set_virtual(false);

        /*
         * Price. Samples are free. POS carries a real cost that comes out of
         * a distributor's marketing budget — and the export has no price
         * field, so POS is left at zero until the audit supplies one. A cost
         * already set by hand is never overwritten.
         */
        if (!$is_pos) {
            $product->set_regular_price('0');
            $product->set_price('0');
        } elseif ('' === (string) $product->get_regular_price()) {
            $product->set_regular_price('');
            $product->set_price('');
        }

        $cats = [$terms[self::CAT_ROOT] ?? 0];

        if ($is_pos) {
            $cats[] = $terms[self::CAT_POS] ?? 0;
        }

        $product->set_category_ids(\array_values(\array_filter($cats)));

        $product_id = $product->save();

        if (!$product_id) {
            return new \WP_Error('mb_sof_import', 'save returned no id');
        }

        \update_post_meta($product_id, self::META_PORTAL_SKU, $sku);
        \update_post_meta($product_id, self::META_PORTAL_TYPE, (string) ($r['section'] ?? ''));

        if (isset($r['max']) && '' !== (string) $r['max']) {
            \update_post_meta($product_id, self::META_MAX_QTY, (int) $r['max']);
        }

        return (int) $product_id;
    }

    /**
     * The two product categories, created if absent.
     *
     * @return array<string,int> slug => term id
     */
    public static function ensure_terms(): array
    {
        $out = [];

        $wanted = [
            self::CAT_ROOT => 'Sample ordering',
            self::CAT_POS => 'Marketing and POS',
        ];

        foreach ($wanted as $slug => $name) {
            $term = \get_term_by('slug', $slug, 'product_cat');

            if ($term instanceof \WP_Term) {
                $out[$slug] = (int) $term->term_id;
                continue;
            }

            $created = \wp_insert_term($name, 'product_cat', ['slug' => $slug]);

            if (!\is_wp_error($created)) {
                $out[$slug] = (int) $created['term_id'];
            }
        }

        return $out;
    }

    /**
     * Everything this importer created, for an undo.
     *
     * @return int[]
     */
    public static function imported_ids(): array
    {
        global $wpdb;

        return \array_map('intval', (array) $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s",
            self::META_PORTAL_SKU
        )));
    }
}
