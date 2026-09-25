<?php
/**
 * Sample catalogue — sourced live from WooCommerce products.
 *
 * Replaces the generated catalogue file the HubSpot build used. Products come
 * from the site's own WooCommerce catalogue; the 22 categories are derived by
 * classifying product names, because the WooCommerce category tree does not
 * mirror this taxonomy.
 *
 * The classifier below is a line-for-line transcription of the Python original
 * documented in build.md section 5. It was validated against all 460 rows of
 * products.csv and agrees with the Python output exactly, including the
 * deliberate exclusions.
 *
 * @package Millboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Accordion order. Rendered in exactly this sequence; empty categories stay
 * visible on purpose (Modello has no SKUs yet).
 *
 * @return string[]
 */
function mb_sof_category_order() {
	return array(
		'Brochures',
		'Sample Packs',
		'Full Ranges',
		'Standard Board Single 100mm Samples',
		'Bullnosed Board Single 100mm Samples',
		'Fascia Board Single 100mm Samples',
		'Standard Board Single 300mm Samples',
		'Bullnosed Board Single 300mm Samples',
		'Cladding Board Single 100mm Samples',
		'Cladding Board Single 300mm Samples',
		'Cladding Board Accessories 100mm Samples',
		'Accessories',
		'Bullnosed Step Edge (Flexible) 100mm Samples',
		'Square Step Edge (Flexible) 100mm Samples',
		'Cladding Sample Packs',
		'126mm Board Single 100mm Samples',
		'126mm Board Single 300mm Samples',
		'Misc. POS',
		'Cladding Envello Décor',
		'Modello Decking 300mm Samples',
		'Modello Decking 100mm Samples',
		'Sample Panels',
	);
}

/**
 * SKUs that belong in the catalogue but are not named "Sample", so the name
 * pre-filter would miss them. From build.md section 3.
 *
 * @return string[] Upper-case SKUs.
 */
function mb_sof_extra_skus() {
	return apply_filters(
		'mb_sof_extra_skus',
		array(
			'AMB6M7B50', // Millboard Envello Cladding Brochure (English) - Box of 50
			'AMB6B165',  // Millboard Envello Cladding Brochure (English) - Box of 165
		)
	);
}

/**
 * First NNNmm dimension in a product name.
 *
 * Handles the compact form first (100x25x13mm -> 100), otherwise the first
 * NNNmm found.
 *
 * @param string $name Product name.
 * @return int|null
 */
function mb_sof_leading_size( $name ) {
	if ( preg_match( '/(\d+)x\d+x\d+\s*mm/i', $name, $m ) ) {
		return (int) $m[1];
	}
	if ( preg_match( '/(\d+)\s*mm/i', $name, $m ) ) {
		return (int) $m[1];
	}
	return null;
}

/**
 * True if the haystack contains any of the needles.
 *
 * @param string   $haystack Lower-cased subject.
 * @param string[] $needles  Lower-case needles.
 * @return bool
 */
function mb_sof_has_any( $haystack, array $needles ) {
	foreach ( $needles as $needle ) {
		if ( false !== strpos( $haystack, $needle ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Map a product name onto one of the 22 categories.
 *
 * Rules apply in order; first match wins. Returns null for products that are
 * deliberately out of scope (all 600mm, 300mm Fascia, 300mm flexible edges,
 * rigid Square Step Edge (Standard)) and for anything that matches nothing.
 *
 * MILLBOARD EDIT — $size_override.
 *
 * Upstream the 100/300 split is read out of the product name, because the
 * HubSpot catalogue encoded the sample's own dimension there. On this store a
 * sample is a VARIATION of a board, and the size lives in the `pa_sample-size`
 * attribute instead: small is the 100mm sample, large the 300mm. Worse, the
 * decking parents carry the board width in their name ("Enhanced Grain 126mm
 * Antique Oak"), so mb_sof_leading_size() reads 126 and every sample of that
 * board lands in one category whatever its size.
 *
 * Passing the size in fixes that without touching a rule. Left null the
 * function behaves exactly as before, so the 460-row validation still holds
 * for anything named the old way.
 *
 * @param string   $name          Product name.
 * @param int|null $size_override Sample size in mm (100 or 300), when known.
 * @return string|null Category name, or null if out of scope.
 */
function mb_sof_classify( $name, $size_override = null ) {
	$n    = function_exists( 'mb_strtolower' ) ? mb_strtolower( $name, 'UTF-8' ) : strtolower( $name );
	$size = null !== $size_override ? (int) $size_override : mb_sof_leading_size( $name );

	// ── Exclusions (drop entirely) ──
	if ( 600 === $size ) {
		return null;
	}
	if ( false !== strpos( $n, 'fascia' ) && 300 === $size ) {
		return null;
	}
	if ( ( false !== strpos( $n, 'flexible bullnose' ) || false !== strpos( $n, 'flexible square edge' ) ) && 300 === $size ) {
		return null;
	}
	if ( false !== strpos( $n, 'square step edge (standard)' ) ) {
		return null;
	}

	// ── Non-piece groups first ──
	if ( false !== strpos( $n, 'brochure' ) && false === strpos( $n, 'display rack' ) ) {
		return 'Brochures';
	}
	if ( mb_sof_has_any(
		$n,
		array(
			'teaser board',
			'display rack',
			'brochure display',
			'stuffer',
			'flat pack',
			'sample labels',
			'cladding tower',
			'assembled',
			'mitre joint',
			'corner sample',
			'corner deck sample',
		)
	) ) {
		return 'Misc. POS';
	}
	if ( false !== strpos( $n, 'sample panel' ) ) {
		return 'Sample Panels';
	}
	if ( false !== strpos( $n, 'full range' ) || false !== strpos( $n, 'global samples box' ) ) {
		return 'Full Ranges';
	}
	if ( false !== strpos( $n, 'décor' ) || false !== strpos( $n, 'decor' ) ) {
		return 'Cladding Envello Décor';
	}

	// ── Cladding sets / boxes (before the single-board rules) ──
	if ( false !== strpos( $n, 'sample box' ) && false !== strpos( $n, 'envello' ) ) {
		return 'Cladding Sample Packs';
	}
	if ( false !== strpos( $n, 'fr cladding samples' )
		|| false !== strpos( $n, 'fr cladding 8 samples' )
		|| ( false !== strpos( $n, 'cladding samples' )
			&& ( false !== strpos( $n, 'shoe box' ) || false !== strpos( $n, 'presenter box' ) ) ) ) {
		return 'Cladding Sample Packs';
	}
	if ( false !== strpos( $n, 'sample box' ) ) {
		return 'Sample Packs'; // decking boxes, including the Modello box
	}

	// ── Cladding accessories (before the single-board rules) ──
	if ( mb_sof_has_any(
		$n,
		array(
			'corner trim',
			'external corner',
			'internal corner',
			'reveal board',
			'vertical starter',
			'horizontal starter',
			'perforated closure',
		)
	) ) {
		return 'Cladding Board Accessories 100mm Samples';
	}

	// ── Cladding single boards ──
	if ( false !== strpos( $n, 'board & batten' )
		|| false !== strpos( $n, 'shadow line' )
		|| false !== strpos( $n, 'shadowline' ) ) {
		return 100 === $size
			? 'Cladding Board Single 100mm Samples'
			: 'Cladding Board Single 300mm Samples';
	}

	// ── Decking single boards ──
	if ( false !== strpos( $n, 'enhanced grain' ) && false !== strpos( $n, '126mm' ) ) {
		return 100 === $size
			? '126mm Board Single 100mm Samples'
			: '126mm Board Single 300mm Samples';
	}
	if ( false !== strpos( $n, 'enhanced grain' )
		|| false !== strpos( $n, 'weathered oak' )
		|| false !== strpos( $n, 'lasta-grip' )
		|| false !== strpos( $n, 'lastagrip' ) ) {
		return 100 === $size
			? 'Standard Board Single 100mm Samples'
			: 'Standard Board Single 300mm Samples';
	}

	// ── Bullnosed board ──
	if ( false !== strpos( $n, 'bullnosed board' ) ) {
		return 100 === $size
			? 'Bullnosed Board Single 100mm Samples'
			: 'Bullnosed Board Single 300mm Samples';
	}

	// ── Fascia (100 only; 300 excluded above) ──
	if ( false !== strpos( $n, 'fascia' ) ) {
		return 'Fascia Board Single 100mm Samples';
	}

	// ── Flexible edges (100 only; 300 excluded above) ──
	if ( false !== strpos( $n, 'flexible bullnose' ) ) {
		return 'Bullnosed Step Edge (Flexible) 100mm Samples';
	}
	if ( false !== strpos( $n, 'flexible square edge' ) ) {
		return 'Square Step Edge (Flexible) 100mm Samples';
	}

	// ── Subframe ──
	if ( mb_sof_has_any( $n, array( 'plaspro', 'plas-pro', 'duospan', 'duolift' ) ) ) {
		return 'Accessories';
	}

	// ── Modello single pieces (none today; future-proof) ──
	if ( false !== strpos( $n, 'modello' ) ) {
		return 100 === $size
			? 'Modello Decking 100mm Samples'
			: 'Modello Decking 300mm Samples';
	}

	return null;
}

/**
 * Is this product in scope for the sample catalogue at all?
 *
 * Mirrors the HubSpot source query, which pulled SKUs matching a name search
 * for "Sample" and then added the brochures by SKU. Without this pre-filter
 * the classifier would also catch real sellable boards, because a product
 * named "... 176mm Enhanced Grain ... 100mm" classifies happily.
 *
 * @param string $name Product name.
 * @param string $sku  Product SKU.
 * @return bool
 */
function mb_sof_in_scope( $name, $sku ) {
	$n = function_exists( 'mb_strtolower' ) ? mb_strtolower( $name, 'UTF-8' ) : strtolower( $name );

	if ( false !== strpos( $n, 'sample' ) ) {
		return true;
	}
	if ( in_array( strtoupper( trim( $sku ) ), mb_sof_extra_skus(), true ) ) {
		return true;
	}
	return (bool) apply_filters( 'mb_sof_in_scope', false, $name, $sku );
}

/**
 * Every published simple product, as id + title + sku, in one query.
 *
 * Deliberately $wpdb rather than wc_get_products(): this needs three columns
 * for the whole catalogue, and wc_get_products() would either hydrate every
 * product object (heavy) or cost one query per id. The result is cached, so
 * this runs rarely.
 *
 * Variations are excluded — sample products are simple products.
 *
 * @return array[] Rows of { id, name, sku }.
 */
function mb_sof_fetch_products() {
	global $wpdb;

	$cat = defined( 'MB_SOF_PRODUCT_CAT' ) ? trim( (string) MB_SOF_PRODUCT_CAT ) : '';

	if ( '' !== $cat ) {
		// Restricted to one WooCommerce product category.
		$sql = "
			SELECT p.ID AS id, p.post_title AS name, COALESCE( sku.meta_value, '' ) AS sku
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} sku
				ON sku.post_id = p.ID AND sku.meta_key = '_sku'
			INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
			INNER JOIN {$wpdb->term_taxonomy} tt
				ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'product_cat'
			INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
			WHERE p.post_type = 'product'
				AND p.post_status = 'publish'
				AND t.slug = %s
			GROUP BY p.ID
		";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepared on the next line.
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $cat ), ARRAY_A );
	} else {
		$sql = "
			SELECT p.ID AS id, p.post_title AS name, COALESCE( sku.meta_value, '' ) AS sku
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} sku
				ON sku.post_id = p.ID AND sku.meta_key = '_sku'
			WHERE p.post_type = 'product'
				AND p.post_status = 'publish'
		";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input in this statement.
		$rows = $wpdb->get_results( $sql, ARRAY_A );
	}

	return is_array( $rows ) ? $rows : array();
}

/**
 * MILLBOARD ADDITION — per-SKU order limits.
 *
 * The portal this replaces caps each SKU individually rather than applying one
 * figure: 1, 3, 5, 10, 22 and 30 are all in use, and two colours of the same
 * board can differ. `mb_sof_max_qty` is a single number for the whole form, so
 * it stays as the fallback and this supplies the per-line ceiling.
 *
 * Seeded from the portal export and NOT yet audited -- swapping
 * data/sample-limits.json is how the reviewed numbers land.
 *
 * @return array<string,int> Upper-case SKU => max units.
 */
function mb_sof_sku_limits() {
	static $limits = null;

	if ( is_array( $limits ) ) {
		return $limits;
	}

	$limits = array();
	$file   = __DIR__ . '/../data/sample-limits.json';

	if ( is_readable( $file ) ) {
		$json = json_decode( (string) file_get_contents( $file ), true );

		if ( isset( $json['limits'] ) && is_array( $json['limits'] ) ) {
			foreach ( $json['limits'] as $sku => $max ) {
				$limits[ strtoupper( trim( (string) $sku ) ) ] = (int) $max;
			}
		}
	}

	$limits = (array) apply_filters( 'mb_sof_sku_limits', $limits );

	return $limits;
}

/**
 * The ceiling for one SKU: its own limit, or the form-wide default.
 *
 * @param string $sku Product SKU.
 * @return int
 */
function mb_sof_limit_for_sku( $sku ) {
	$limits = mb_sof_sku_limits();
	$key    = strtoupper( trim( (string) $sku ) );

	$max = isset( $limits[ $key ] ) ? (int) $limits[ $key ] : (int) mb_sof_max_qty();

	return max( 0, $max );
}

/**
 * MILLBOARD ADDITION — the sample variations.
 *
 * On this store a sample is not a product. It is a variation of a board, under
 * the `pa_sample-size` attribute: `small` is the 100mm sample, `large` the
 * 300mm one, and `full` is the board itself and never a sample. The SKUs are
 * the AM* codes (AME105A, AME305A and the rest).
 *
 * One query for the same reason the upstream one is: three columns for the
 * whole catalogue, and the result is cached.
 *
 * The name is the parent's, with the size appended so two variations of one
 * board are tellable apart in the list. Classification does NOT read that
 * name for the size -- it is passed explicitly, see mb_sof_classify().
 *
 * @return array[] Rows of { id, name, sku, sample_size, parent_id }.
 */
function mb_sof_fetch_sample_variations() {
	global $wpdb;

	$sql = "
		SELECT
			v.ID              AS id,
			v.post_parent     AS parent_id,
			parent.post_title AS name,
			COALESCE( sku.meta_value, '' ) AS sku,
			size_term.slug    AS size_slug
		FROM {$wpdb->posts} v
		INNER JOIN {$wpdb->posts} parent ON parent.ID = v.post_parent AND parent.post_status = 'publish'
		LEFT JOIN {$wpdb->postmeta} sku ON sku.post_id = v.ID AND sku.meta_key = '_sku'
		INNER JOIN {$wpdb->postmeta} size_meta
			ON size_meta.post_id = v.ID AND size_meta.meta_key = 'attribute_pa_sample-size'
		INNER JOIN {$wpdb->terms} size_term ON size_term.slug = size_meta.meta_value
		INNER JOIN {$wpdb->term_taxonomy} size_tax
			ON size_tax.term_id = size_term.term_id AND size_tax.taxonomy = 'pa_sample-size'
		WHERE v.post_type = 'product_variation'
			AND v.post_status = 'publish'
			AND size_meta.meta_value IN ( 'small', 'large' )
		GROUP BY v.ID
	";

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input in this statement.
	$rows = $wpdb->get_results( $sql, ARRAY_A );

	if ( ! is_array( $rows ) ) {
		return array();
	}

	$out = array();

	foreach ( $rows as $row ) {
		$is_small = ( 'small' === $row['size_slug'] );

		/*
		 * post_title stores HTML entities, so "Board & Batten+" arrives as
		 * "Board &amp; Batten+". The classifier matches a literal "&" and
		 * would drop all twenty of them, and the widget escapes names again
		 * on output, so an undecoded one renders as "&amp;" on screen.
		 */
		$name = html_entity_decode( (string) $row['name'], ENT_QUOTES, 'UTF-8' );

		$out[] = array(
			'id'          => (int) $row['id'],
			'parent_id'   => (int) $row['parent_id'],
			'sku'         => (string) $row['sku'],
			'name'        => $name . ( $is_small ? ' — 100mm sample' : ' — 300mm sample' ),
			'sample_size' => $is_small ? 100 : 300,
		);
	}

	return $out;
}

/**
 * Stock status for a set of product ids, in one query.
 *
 * @param int[] $ids Product ids.
 * @return array<int,string> id => stock status ('instock', 'outofstock', ...).
 */
function mb_sof_fetch_stock( array $ids ) {
	global $wpdb;

	if ( empty( $ids ) ) {
		return array();
	}

	$ids         = array_map( 'absint', $ids );
	$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

	$sql = "
		SELECT post_id, meta_value
		FROM {$wpdb->postmeta}
		WHERE meta_key = '_stock_status'
			AND post_id IN ( {$placeholders} )
	";

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- placeholders built from absint ids.
	$rows = $wpdb->get_results( $wpdb->prepare( $sql, $ids ), ARRAY_A );

	$out = array();
	foreach ( (array) $rows as $row ) {
		$out[ (int) $row['post_id'] ] = (string) $row['meta_value'];
	}
	return $out;
}

/**
 * The classified sample catalogue.
 *
 * Cached in a transient because it costs two queries plus a classification
 * pass over the whole product catalogue. Invalidated whenever a product is
 * saved or deleted (see mb_sof_flush_catalogue).
 *
 * @param bool $force Skip the cache.
 * @return array[] Rows of { id, sku, name, category, in_stock }.
 */
function mb_sof_get_catalogue( $force = false ) {
	$key = 'mb_sof_catalogue_v1';

	if ( ! $force ) {
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}

	$valid = array_flip( mb_sof_category_order() );
	$items = array();

	/*
	 * MILLBOARD EDIT — sample variations are read first, and are in scope by
	 * definition: carrying a `pa_sample-size` of small or large IS what makes
	 * something a sample here, so there is no name to test. Their size is
	 * passed to the classifier rather than parsed back out of a string.
	 */
	foreach ( mb_sof_fetch_sample_variations() as $row ) {
		$category = mb_sof_classify( $row['name'], $row['sample_size'] );

		if ( null === $category || ! isset( $valid[ $category ] ) ) {
			continue;
		}

		$items[] = array(
			'id'          => (int) $row['id'],
			'parent_id'   => (int) $row['parent_id'],
			'sku'         => (string) $row['sku'],
			'name'        => (string) $row['name'],
			'category'    => $category,
			'sample_size' => (int) $row['sample_size'],
		);
	}

	$seen = array_flip( wp_list_pluck( $items, 'id' ) );

	foreach ( mb_sof_fetch_products() as $row ) {
		// Entities, for the same reason as the variations above.
		$name = html_entity_decode( (string) $row['name'], ENT_QUOTES, 'UTF-8' );
		$sku  = (string) $row['sku'];

		if ( isset( $seen[ (int) $row['id'] ] ) || ! mb_sof_in_scope( $name, $sku ) ) {
			continue;
		}

		$category = mb_sof_classify( $name );
		if ( null === $category || ! isset( $valid[ $category ] ) ) {
			continue;
		}

		$items[] = array(
			'id'        => (int) $row['id'],
			'parent_id' => 0,
			'sku'       => $sku,
			'name'      => $name,
			'category'  => $category,
		);
	}

	// Stock, so the UI can grey out anything WooCommerce would refuse anyway.
	$stock = mb_sof_fetch_stock( wp_list_pluck( $items, 'id' ) );
	foreach ( $items as &$item ) {
		$status            = isset( $stock[ $item['id'] ] ) ? $stock[ $item['id'] ] : 'instock';
		$item['in_stock'] = ( 'outofstock' !== $status );

		// MILLBOARD EDIT — each line carries its own ceiling.
		$item['max_qty'] = mb_sof_limit_for_sku( $item['sku'] );
	}
	unset( $item );

	// Name-sorted within each category; size is encoded in the category name,
	// so there is no within-accordion size split.
	usort(
		$items,
		static function ( $a, $b ) {
			return strcasecmp( $a['name'], $b['name'] );
		}
	);

	set_transient( $key, $items, 12 * HOUR_IN_SECONDS );

	return $items;
}

/**
 * Drop the cached catalogue when the product catalogue changes.
 */
function mb_sof_flush_catalogue() {
	delete_transient( 'mb_sof_catalogue_v1' );
}
add_action( 'save_post_product', 'mb_sof_flush_catalogue' );
add_action( 'deleted_post', 'mb_sof_flush_catalogue' );
add_action( 'woocommerce_update_product', 'mb_sof_flush_catalogue' );
add_action( 'woocommerce_product_set_stock_status', 'mb_sof_flush_catalogue' );

/**
 * Index the catalogue by product id, for validating a submitted order.
 *
 * @return array<int,array> id => item.
 */
function mb_sof_catalogue_by_id() {
	$by_id = array();
	foreach ( mb_sof_get_catalogue() as $item ) {
		$by_id[ $item['id'] ] = $item;
	}
	return $by_id;
}

/**
 * Coverage report: which expected sample SKUs are missing from WooCommerce.
 *
 * Not every sample SKU exists as a WooCommerce product yet. Without this, a
 * partial catalogue looks like a complete one and staff quietly order a short
 * list — the same failure the HubSpot line-item action was rewritten to avoid.
 *
 * Supply the expected SKUs via the mb_sof_expected_skus filter (see
 * inc/mb-sof-expected-skus.php if that file was generated) to get a real
 * count. With no expected list, this reports what was found and says so.
 *
 * @return array{found:int,missing:string[],expected:int,by_category:array<string,int>}
 */
function mb_sof_coverage() {
	$catalogue = mb_sof_get_catalogue();

	$by_category = array_fill_keys( mb_sof_category_order(), 0 );
	$found_skus  = array();

	foreach ( $catalogue as $item ) {
		++$by_category[ $item['category'] ];
		$found_skus[ strtoupper( trim( $item['sku'] ) ) ] = true;
	}

	$expected = array_map(
		static function ( $s ) {
			return strtoupper( trim( $s ) );
		},
		(array) apply_filters( 'mb_sof_expected_skus', array() )
	);

	$missing = array();
	foreach ( $expected as $sku ) {
		if ( '' !== $sku && ! isset( $found_skus[ $sku ] ) ) {
			$missing[] = $sku;
		}
	}

	return array(
		'found'       => count( $catalogue ),
		'expected'    => count( $expected ),
		'missing'     => $missing,
		'by_category' => $by_category,
	);
}
