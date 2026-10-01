/**
 * "Sample ordering" report, inside WooCommerce Analytics.
 *
 * Written against the globals WordPress and WooCommerce already expose
 * (wp.element, wp.hooks, wp.apiFetch, wc.components) rather than the npm
 * packages, so this adds nothing to the theme's dependencies and no build
 * configuration beyond being an entry. The PHP side declares the matching
 * script handles, so the globals are guaranteed present by the time this runs.
 *
 * No JSX, for the same reason: the theme has no React toolchain and does not
 * need one for a single table.
 *
 * See Theme\WooCommerce\SampleOrderReport for the page registration and the
 * endpoint this reads.
 */

( function () {
	'use strict';

	var wp = window.wp || {};
	var wc = window.wc || {};
	var settings = window.MB_SOF_REPORT || {};

	if ( ! wp.element || ! wp.hooks || ! wc.components ) {
		return;
	}

	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var __ = wp.i18n ? wp.i18n.__ : function ( s ) { return s; };

	var TableCard = wc.components.TableCard;
	var SummaryList = wc.components.SummaryList;
	var SummaryNumber = wc.components.SummaryNumber;

	var HEADERS = [
		{ key: 'company', label: __( 'Company', 'granola' ), isLeftAligned: true, required: true },
		{ key: 'role', label: __( 'Account type', 'granola' ), isLeftAligned: true },
		{ key: 'orders', label: __( 'Orders', 'granola' ), isNumeric: true, isSortable: true },
		{ key: 'lines', label: __( 'Lines', 'granola' ), isNumeric: true, isSortable: true },
		{ key: 'units', label: __( 'Items', 'granola' ), isNumeric: true, isSortable: true, defaultSort: true },
		{ key: 'last', label: __( 'Last order', 'granola' ), isLeftAligned: true },
	];

	/**
	 * Role slugs are not for reading. Anything unmapped falls through as its
	 * slug rather than being hidden, so a new role is visible rather than
	 * silently blank.
	 */
	function roleLabel( slug ) {
		var map = {
			millboard_distributor: __( 'Distributor', 'granola' ),
			millboard_installer: __( 'Installer', 'granola' ),
			millboard_architect: __( 'Architect', 'granola' ),
			millboard_staff: __( 'Millboard staff', 'granola' ),
			millboard_asset_viewer: __( 'Brand assets only', 'granola' ),
			administrator: __( 'Administrator', 'granola' ),
			shop_manager: __( 'Shop manager', 'granola' ),
			editor: __( 'Editor', 'granola' ),
		};

		return map[ slug ] || slug || __( 'Unknown', 'granola' );
	}

	function toRows( rows ) {
		return rows.map( function ( r ) {
			return [
				{ display: r.company, value: r.company },
				{ display: roleLabel( r.role ), value: r.role },
				{ display: String( r.orders ), value: r.orders },
				{ display: String( r.lines ), value: r.lines },
				{ display: String( r.units ), value: r.units },
				{ display: r.last || '—', value: r.last },
			];
		} );
	}

	function Report() {
		var state = useState( { loading: true, rows: [], totals: null, error: '' } );
		var data = state[ 0 ];
		var setData = state[ 1 ];

		useEffect( function () {
			if ( ! wp.apiFetch || ! settings.rest ) {
				setData( { loading: false, rows: [], totals: null, error: __( 'The report endpoint is unavailable.', 'granola' ) } );
				return;
			}

			wp.apiFetch( { path: settings.rest } )
				.then( function ( res ) {
					setData( {
						loading: false,
						rows: res && res.rows ? res.rows : [],
						totals: res && res.totals ? res.totals : null,
						error: '',
					} );
				} )
				.catch( function ( err ) {
					setData( {
						loading: false,
						rows: [],
						totals: null,
						error: ( err && err.message ) || __( 'The report could not be loaded.', 'granola' ),
					} );
				} );
		}, [] );

		var summary = null;

		if ( data.totals && SummaryList && SummaryNumber ) {
			summary = el(
				SummaryList,
				null,
				function () {
					return [
						el( SummaryNumber, { key: 'orders', value: data.totals.orders, label: __( 'Orders', 'granola' ) } ),
						el( SummaryNumber, { key: 'lines', value: data.totals.lines, label: __( 'Lines', 'granola' ) } ),
						el( SummaryNumber, { key: 'units', value: data.totals.units, label: __( 'Items', 'granola' ) } ),
					];
				}
			);
		}

		return el(
			wp.element.Fragment,
			null,
			summary,
			el( TableCard, {
				title: __( 'Sample ordering, last 90 days', 'granola' ),
				headers: HEADERS,
				rows: toRows( data.rows ),
				rowsPerPage: 25,
				totalRows: data.rows.length,
				isLoading: data.loading,
				// Heaviest first, which is the reason this page exists.
				summary: null,
				emptyMessage: data.error
					? data.error
					: __( 'No sample orders have been placed yet.', 'granola' ),
			} )
		);
	}

	wp.hooks.addFilter(
		'woocommerce_admin_reports_list',
		'millboard/sample-ordering',
		function ( reports ) {
			return reports.concat( {
				report: 'sample-ordering',
				title: __( 'Sample ordering', 'granola' ),
				component: Report,
			} );
		}
	);
} )();
