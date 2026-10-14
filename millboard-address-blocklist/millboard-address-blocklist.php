<?php
/**
 * Plugin Name:       Millboard Address Blocklist
 * Description:       Adds WooCommerce → Blocked Addresses. Orders to a listed delivery or billing address are refused at checkout (classic and block checkout). Stops prank/spam sample orders.
 * Version:           1.0.0
 * Author:            Logotype Ltd
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Network:           true
 * Text Domain:       mb-address-blocklist
 *
 * The list is stored network-wide, so a ban applies on every locale subsite.
 */

defined( 'ABSPATH' ) || exit;

final class MB_Address_Blocklist {

	const OPT_LIST    = 'mb_blocked_addresses';
	const OPT_LOG     = 'mb_blocked_address_log';
	const OPT_MESSAGE = 'mb_blocked_address_message';
	const LOG_MAX     = 100;
	const SLUG        = 'mb-blocked-addresses';

	public static function init(): void {
		add_action( 'before_woocommerce_init', static function () {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			}
		} );

		add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
		add_action( 'admin_post_mb_ba_add', [ __CLASS__, 'handle_add' ] );
		add_action( 'admin_post_mb_ba_remove', [ __CLASS__, 'handle_remove' ] );
		add_action( 'admin_post_mb_ba_settings', [ __CLASS__, 'handle_settings' ] );
		add_action( 'admin_post_mb_ba_clear_log', [ __CLASS__, 'handle_clear_log' ] );

		// Classic checkout.
		add_action( 'woocommerce_after_checkout_validation', [ __CLASS__, 'check_classic' ], 10, 2 );
		// Block checkout (Store API).
		add_action( 'woocommerce_store_api_checkout_order_processed', [ __CLASS__, 'check_store_api' ] );

		// "Ban this address" on the order screen.
		add_filter( 'woocommerce_order_actions', [ __CLASS__, 'order_actions' ] );
		add_action( 'woocommerce_order_action_mb_ban_address', [ __CLASS__, 'order_action_ban' ] );
	}

	/* ---------- Normalisation & matching ---------- */

	private static function norm_text( string $s ): string {
		$s   = strtolower( remove_accents( $s ) );
		$s   = str_replace( '&', ' and ', $s );
		$s   = preg_replace( '/[^a-z0-9]+/', ' ', $s );
		$map = [
			'road' => 'rd', 'street' => 'st', 'avenue' => 'ave', 'drive' => 'dr', 'lane' => 'ln',
			'close' => 'cl', 'court' => 'ct', 'crescent' => 'cres', 'place' => 'pl', 'gardens' => 'gdns',
			'terrace' => 'ter', 'square' => 'sq', 'boulevard' => 'blvd', 'apartment' => 'flat', 'apt' => 'flat',
		];
		$words = array_filter( explode( ' ', $s ), 'strlen' );
		foreach ( $words as &$w ) {
			if ( isset( $map[ $w ] ) ) {
				$w = $map[ $w ];
			}
		}
		return implode( ' ', $words );
	}

	private static function norm_postcode( string $s ): string {
		return strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', $s ) );
	}

	/** @return array<int,array<string,mixed>> */
	public static function get_list(): array {
		$list = get_site_option( self::OPT_LIST, [] );
		return is_array( $list ) ? $list : [];
	}

	/**
	 * Returns the matching entry for an address, or null.
	 *
	 * @param array<string,string> $addr address_1, address_2, postcode
	 */
	public static function find_match( array $addr ): ?array {
		$pc = self::norm_postcode( (string) ( $addr['postcode'] ?? '' ) );
		if ( '' === $pc ) {
			return null;
		}
		$hay = ' ' . self::norm_text( ( $addr['address_1'] ?? '' ) . ' ' . ( $addr['address_2'] ?? '' ) ) . ' ';

		foreach ( self::get_list() as $entry ) {
			if ( self::norm_postcode( (string) $entry['postcode'] ) !== $pc ) {
				continue;
			}
			$needle = self::norm_text( (string) $entry['line1'] );
			if ( '' === $needle || false !== strpos( $hay, ' ' . $needle . ' ' ) ) {
				return $entry;
			}
		}
		return null;
	}

	private static function message(): string {
		$m = get_site_option( self::OPT_MESSAGE, '' );
		return $m ? (string) $m : __( 'Sorry, we are unable to process an order for this address. Please contact our customer service team if you think this is a mistake.', 'mb-address-blocklist' );
	}

	/* ---------- Checkout enforcement ---------- */

	/**
	 * @param array<string,mixed> $data
	 * @param WP_Error            $errors
	 */
	public static function check_classic( $data, $errors ): void {
		foreach ( [ 'shipping', 'billing' ] as $type ) {
			$addr = [
				'address_1' => (string) ( $data[ $type . '_address_1' ] ?? '' ),
				'address_2' => (string) ( $data[ $type . '_address_2' ] ?? '' ),
				'postcode'  => (string) ( $data[ $type . '_postcode' ] ?? '' ),
			];
			if ( self::find_match( $addr ) ) {
				self::log( $addr, (string) ( $data['billing_email'] ?? '' ), trim( ( $data['billing_first_name'] ?? '' ) . ' ' . ( $data['billing_last_name'] ?? '' ) ), $type );
				$errors->add( 'mb_address_blocked', esc_html( self::message() ) );
				return;
			}
		}
	}

	public static function check_store_api( $order ): void {
		foreach ( [ 'shipping', 'billing' ] as $type ) {
			$addr = $order->get_address( $type );
			if ( self::find_match( $addr ) ) {
				self::log( $addr, $order->get_billing_email(), trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ), $type );
				$order->delete( true ); // Drop the draft order the Store API just created.
				if ( class_exists( '\Automattic\WooCommerce\StoreApi\Exceptions\RouteException' ) ) {
					throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'mb_address_blocked', esc_html( self::message() ), 403 );
				}
				wp_die( esc_html( self::message() ), '', [ 'response' => 403 ] );
			}
		}
	}

	/* ---------- Log ---------- */

	private static function log( array $addr, string $email, string $name, string $type ): void {
		$log = get_site_option( self::OPT_LOG, [] );
		$log = is_array( $log ) ? $log : [];
		array_unshift( $log, [
			'time'  => time(),
			'site'  => get_bloginfo( 'name' ),
			'name'  => $name,
			'email' => $email,
			'type'  => $type,
			'addr'  => trim( ( $addr['address_1'] ?? '' ) . ' ' . ( $addr['address_2'] ?? '' ) ),
			'pc'    => $addr['postcode'] ?? '',
		] );
		update_site_option( self::OPT_LOG, array_slice( $log, 0, self::LOG_MAX ) );
	}

	/* ---------- Order screen action ---------- */

	public static function order_actions( $actions ) {
		$actions['mb_ban_address'] = __( 'Ban this delivery address', 'mb-address-blocklist' );
		return $actions;
	}

	public static function order_action_ban( $order ): void {
		$addr = $order->get_address( 'shipping' );
		if ( empty( $addr['address_1'] ) || empty( $addr['postcode'] ) ) {
			$addr = $order->get_address( 'billing' );
		}
		$added = self::add_entry( (string) $addr['address_1'], (string) $addr['postcode'], sprintf( 'From order #%s', $order->get_order_number() ) );
		$order->add_order_note( $added
			? sprintf( 'Address banned (%s, %s) by %s.', $addr['address_1'], $addr['postcode'], wp_get_current_user()->user_login )
			: 'Address was already on the blocked list (or missing).' );
	}

	/* ---------- List management ---------- */

	private static function add_entry( string $line1, string $postcode, string $note ): bool {
		$line1    = trim( $line1 );
		$postcode = trim( $postcode );
		if ( '' === self::norm_postcode( $postcode ) ) {
			return false;
		}
		if ( '' !== $line1 && self::find_match( [ 'address_1' => $line1, 'postcode' => $postcode ] ) ) {
			return false;
		}
		$list   = self::get_list();
		$list[] = [
			'id'       => wp_generate_uuid4(),
			'line1'    => $line1,
			'postcode' => strtoupper( $postcode ),
			'note'     => $note,
			'added'    => time(),
			'by'       => wp_get_current_user()->user_login,
		];
		update_site_option( self::OPT_LIST, $list );
		return true;
	}

	private static function guard( string $nonce_action ): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'mb-address-blocklist' ), '', [ 'response' => 403 ] );
		}
		check_admin_referer( $nonce_action );
	}

	private static function back( string $notice ): void {
		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'mb_notice' => $notice ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function handle_add(): void {
		self::guard( 'mb_ba_add' );
		$line1    = sanitize_text_field( wp_unslash( $_POST['line1'] ?? '' ) );
		$postcode = sanitize_text_field( wp_unslash( $_POST['postcode'] ?? '' ) );
		$note     = sanitize_text_field( wp_unslash( $_POST['note'] ?? '' ) );
		if ( '' === $line1 && empty( $_POST['whole_postcode'] ) ) {
			self::back( 'need_line' );
		}
		self::back( self::add_entry( $line1, $postcode, $note ) ? 'added' : 'not_added' );
	}

	public static function handle_remove(): void {
		self::guard( 'mb_ba_remove' );
		$id   = sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) );
		$list = array_values( array_filter( self::get_list(), static fn( $e ) => ( $e['id'] ?? '' ) !== $id ) );
		update_site_option( self::OPT_LIST, $list );
		self::back( 'removed' );
	}

	public static function handle_settings(): void {
		self::guard( 'mb_ba_settings' );
		update_site_option( self::OPT_MESSAGE, sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ) );
		self::back( 'saved' );
	}

	public static function handle_clear_log(): void {
		self::guard( 'mb_ba_clear_log' );
		update_site_option( self::OPT_LOG, [] );
		self::back( 'saved' );
	}

	/* ---------- Admin page ---------- */

	public static function menu(): void {
		add_submenu_page( 'woocommerce', 'Blocked Addresses', 'Blocked Addresses', 'manage_woocommerce', self::SLUG, [ __CLASS__, 'render' ] );
	}

	public static function render(): void {
		$post_url = esc_url( admin_url( 'admin-post.php' ) );
		$notices  = [
			'added'     => [ 'success', 'Address added to the blocklist.' ],
			'not_added' => [ 'warning', 'Nothing added: the postcode was missing or that address is already blocked.' ],
			'need_line' => [ 'error', 'Enter the first line of the address, or tick the box to block the whole postcode.' ],
			'removed'   => [ 'success', 'Address removed.' ],
			'saved'     => [ 'success', 'Saved.' ],
		];
		$n = sanitize_key( $_GET['mb_notice'] ?? '' );

		echo '<div class="wrap"><h1>Blocked Addresses</h1>';
		if ( isset( $notices[ $n ] ) ) {
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $notices[ $n ][0] ), esc_html( $notices[ $n ][1] ) );
		}
		echo '<p>Orders (classic and block checkout) with a <strong>delivery or billing</strong> address matching an entry are refused. Matching ignores case, punctuation and common abbreviations (Road/Rd, Street/St), and ignores flat or house-name text around the entered line. This list is shared across <strong>all locale sites</strong>. You can also ban an address from any order using the &ldquo;Ban this delivery address&rdquo; order action.</p>';

		// Add form.
		echo '<h2>Block an address</h2><form method="post" action="' . $post_url . '">';
		wp_nonce_field( 'mb_ba_add' );
		echo '<input type="hidden" name="action" value="mb_ba_add"><table class="form-table"><tbody>
			<tr><th><label for="line1">Address line 1</label></th><td><input name="line1" id="line1" class="regular-text" placeholder="15 Thirlmere Road"></td></tr>
			<tr><th><label for="postcode">Postcode / ZIP</label></th><td><input name="postcode" id="postcode" class="regular-text" placeholder="NG10 4DS" required></td></tr>
			<tr><th><label for="note">Note</label></th><td><input name="note" id="note" class="regular-text" placeholder="Prank sample orders"></td></tr>
			<tr><th>Whole postcode</th><td><label><input type="checkbox" name="whole_postcode" value="1"> Block every address in this postcode (leave line 1 empty). Use with care.</label></td></tr>
			</tbody></table>';
		submit_button( 'Block address' );
		echo '</form>';

		// List.
		$list = self::get_list();
		echo '<h2>Blocked (' . count( $list ) . ')</h2>';
		if ( ! $list ) {
			echo '<p>No addresses blocked yet.</p>';
		} else {
			echo '<table class="widefat striped" style="max-width:900px"><thead><tr><th>Address</th><th>Postcode</th><th>Note</th><th>Added</th><th></th></tr></thead><tbody>';
			foreach ( array_reverse( $list ) as $e ) {
				echo '<tr><td>' . ( '' === $e['line1'] ? '<em>(whole postcode)</em>' : esc_html( $e['line1'] ) ) . '</td><td>' . esc_html( $e['postcode'] ) . '</td><td>' . esc_html( $e['note'] ) . '</td><td>' . esc_html( wp_date( 'j M Y', (int) $e['added'] ) . ' · ' . $e['by'] ) . '</td><td>';
				echo '<form method="post" action="' . $post_url . '" onsubmit="return confirm(&quot;Unblock this address?&quot;)">';
				wp_nonce_field( 'mb_ba_remove' );
				echo '<input type="hidden" name="action" value="mb_ba_remove"><input type="hidden" name="id" value="' . esc_attr( $e['id'] ) . '"><button class="button-link button-link-delete">Remove</button></form></td></tr>';
			}
			echo '</tbody></table>';
		}

		// Message.
		echo '<h2>Message shown to the customer</h2><form method="post" action="' . $post_url . '">';
		wp_nonce_field( 'mb_ba_settings' );
		echo '<input type="hidden" name="action" value="mb_ba_settings"><textarea name="message" rows="3" class="large-text" style="max-width:600px" placeholder="' . esc_attr( self::message() ) . '">' . esc_textarea( (string) get_site_option( self::OPT_MESSAGE, '' ) ) . '</textarea>';
		submit_button( 'Save message', 'secondary' );
		echo '</form>';

		// Log.
		$log = get_site_option( self::OPT_LOG, [] );
		echo '<h2>Blocked attempts (latest ' . self::LOG_MAX . ')</h2>';
		if ( ! $log ) {
			echo '<p>None yet.</p>';
		} else {
			echo '<table class="widefat striped" style="max-width:1100px"><thead><tr><th>When</th><th>Site</th><th>Name</th><th>Email</th><th>Matched on</th><th>Address</th></tr></thead><tbody>';
			foreach ( $log as $l ) {
				echo '<tr><td>' . esc_html( wp_date( 'j M Y H:i', (int) $l['time'] ) ) . '</td><td>' . esc_html( $l['site'] ) . '</td><td>' . esc_html( $l['name'] ) . '</td><td>' . esc_html( $l['email'] ) . '</td><td>' . esc_html( $l['type'] ) . '</td><td>' . esc_html( $l['addr'] . ', ' . $l['pc'] ) . '</td></tr>';
			}
			echo '</tbody></table><form method="post" action="' . $post_url . '" style="margin-top:8px">';
			wp_nonce_field( 'mb_ba_clear_log' );
			echo '<input type="hidden" name="action" value="mb_ba_clear_log">';
			submit_button( 'Clear log', 'secondary', '', false );
			echo '</form>';
		}
		echo '</div>';
	}
}

add_action( 'plugins_loaded', static function () {
	if ( class_exists( 'WooCommerce' ) ) {
		MB_Address_Blocklist::init();
	}
} );
