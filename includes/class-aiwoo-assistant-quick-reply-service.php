<?php
/**
 * Quick Reply Service — rule-based keyword matching to bypass AI calls.
 *
 * @package AIWooAssistant
 */

namespace AIWooAssistant;

defined( 'ABSPATH' ) || exit;

final class Quick_Reply_Service {

	const DB_VERSION    = '1';
	const DB_OPTION_KEY = 'aiwoo_qr_db_version';
	const SEED_OPTION   = 'aiwoo_qr_seeded';
	const CACHE_KEY     = 'aiwoo_quick_replies_cache';
	const CACHE_TTL     = 300;

	/** @var string */
	private $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'aiwoo_quick_replies';

		add_action( 'admin_post_aiwoo_save_quick_reply',         array( $this, 'handle_save' ) );
		add_action( 'admin_post_aiwoo_delete_quick_reply',        array( $this, 'handle_delete' ) );
		add_action( 'admin_post_aiwoo_save_quick_reply_from_ai',  array( $this, 'handle_save_from_ai' ) );
	}

	// -------------------------------------------------------------------------
	// Schema management
	// -------------------------------------------------------------------------

	public static function create_table() {
		global $wpdb;

		$table           = $wpdb->prefix . 'aiwoo_quick_replies';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  title varchar(255) NOT NULL DEFAULT '',
  keywords text NOT NULL,
  response text NOT NULL,
  match_type varchar(20) NOT NULL DEFAULT 'contains',
  priority int(11) NOT NULL DEFAULT 0,
  status tinyint(1) NOT NULL DEFAULT 1,
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  KEY idx_status (status),
  KEY idx_priority (priority)
) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::DB_OPTION_KEY, self::DB_VERSION );
	}

	public static function maybe_create_table() {
		if ( get_option( self::DB_OPTION_KEY, '' ) !== self::DB_VERSION ) {
			self::create_table();
		}
	}

	public static function drop_table() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'DROP TABLE IF EXISTS `' . $wpdb->prefix . 'aiwoo_quick_replies`' );
		delete_option( self::DB_OPTION_KEY );
		delete_option( self::SEED_OPTION );
	}

	// -------------------------------------------------------------------------
	// Seeding
	// -------------------------------------------------------------------------

	/**
	 * Called from the activation hook — no option guard, just a row count check.
	 * Safe to call on re-activation: skips insert if any rows already exist.
	 */
	public static function seed_on_activation() {
		global $wpdb;
		$table = $wpdb->prefix . 'aiwoo_quick_replies';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );

		if ( $count > 0 ) {
			update_option( self::SEED_OPTION, '1', true );
			return;
		}

		self::insert_defaults();
		update_option( self::SEED_OPTION, '1', true );
	}

	/**
	 * Called on every plugin init via Plugin::__construct().
	 * Uses an autoloaded option as a cheap short-circuit so normal page loads
	 * cost nothing after the first successful seed.
	 *
	 * Re-seeds only when:
	 *   - The seed option is absent (fresh install, uninstall/reinstall, or
	 *     someone deleted the option alongside a TRUNCATE).
	 *   - AND the table exists but is empty.
	 */
	public static function maybe_seed_defaults() {
		// Fast path: option set → already seeded, nothing to do.
		if ( '1' === get_option( self::SEED_OPTION, '' ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'aiwoo_quick_replies';

		// Confirm table exists before querying it.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $table !== $exists ) {
			return;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );

		if ( $count === 0 ) {
			self::insert_defaults();
		}

		// Mark as seeded regardless — if rows existed we still set the flag
		// so subsequent requests skip both DB queries.
		update_option( self::SEED_OPTION, '1', true );
	}

	/**
	 * Insert all 19 default quick-reply rules.
	 * Must only be called after verifying the table is empty.
	 */
	private static function insert_defaults() {
		global $wpdb;
		$table = $wpdb->prefix . 'aiwoo_quick_replies';
		$now   = current_time( 'mysql' );

		$defaults = array(
			array(
				'title'    => 'Greeting',
				'keywords' => 'hi,hello,hey,hlo,hii',
				'response' => 'Hi! 😊 How can I help you today?',
				'priority' => 100,
			),
			array(
				'title'    => 'Small Talk',
				'keywords' => 'how are you,how r u,hw r u',
				'response' => "I'm doing great, thanks for asking! 😊 What can I help you find today?",
				'priority' => 90,
			),
			array(
				'title'    => 'Order Tracking',
				'keywords' => 'order status,track order,where is my order',
				'response' => 'I can help with that 😊 Please share your order ID.',
				'priority' => 85,
			),
			array(
				'title'    => 'Complaint Handling',
				'keywords' => 'problem,issue,complaint,not working',
				'response' => "I'm sorry about that 😔 Please share the issue details, and I'll help you resolve it quickly.",
				'priority' => 85,
			),
			array(
				'title'    => 'Product Search Intent',
				'keywords' => 'i want,looking for,need product,show me,find',
				'response' => "Sure! 😊 What kind of product are you looking for? You can tell me the name, category, or budget.",
				'priority' => 80,
			),
			array(
				'title'    => 'COD Availability',
				'keywords' => 'cod,cash on delivery',
				'response' => 'Yes 👍 Cash on Delivery is available for most locations. Want me to check for your area?',
				'priority' => 75,
			),
			array(
				'title'    => 'Returns & Refund',
				'keywords' => 'return,refund,replace,exchange',
				'response' => 'We offer an easy return and refund process 👍 Let me know the product and I\'ll guide you.',
				'priority' => 75,
			),
			array(
				'title'    => 'Price Inquiry',
				'keywords' => 'price,cost,rate,how much',
				'response' => 'I can help with that 👍 Which product are you interested in?',
				'priority' => 70,
			),
			array(
				'title'    => 'Budget Intent',
				'keywords' => 'cheap,low price,budget,affordable',
				'response' => "Got it 👍 What's your budget range? I'll suggest the best options for you.",
				'priority' => 70,
			),
			array(
				'title'    => 'Stock Check',
				'keywords' => 'available,in stock,stock',
				'response' => "I'll check that for you 👍 Which product are you referring to?",
				'priority' => 70,
			),
			array(
				'title'    => 'Delivery Info',
				'keywords' => 'delivery,shipping,how long,arrival',
				'response' => 'Delivery depends on your location 😊 Can you share your area or pincode?',
				'priority' => 70,
			),
			array(
				'title'    => 'Recommendations',
				'keywords' => 'best product,recommend,suggestion',
				'response' => "I'd be happy to help 👍 What are you looking for, and what's your budget?",
				'priority' => 70,
			),
			array(
				'title'    => 'Human Support',
				'keywords' => 'contact,support,call,talk to human',
				'response' => 'Sure 😊 Please share your details, and our team will get in touch with you shortly.',
				'priority' => 65,
			),
			array(
				'title'    => 'Bulk / Wholesale',
				'keywords' => 'bulk,wholesale,quantity',
				'response' => "Great 👍 Please share the quantity you need, and we'll suggest the best deal.",
				'priority' => 65,
			),
			array(
				'title'    => 'Offers & Discounts',
				'keywords' => 'discount,offer,deal,coupon',
				'response' => "We often have great deals 👍 Tell me the product, and I'll check current offers for you.",
				'priority' => 65,
			),
			array(
				'title'    => 'Warranty',
				'keywords' => 'warranty,guarantee',
				'response' => "Warranty depends on the product 👍 Tell me which item you're checking, and I'll confirm.",
				'priority' => 60,
			),
			array(
				'title'    => 'Payment Options',
				'keywords' => 'payment,pay,payment options',
				'response' => 'We support multiple payment options 👍 UPI, cards, net banking, and COD.',
				'priority' => 60,
			),
			array(
				'title'    => 'Thank You',
				'keywords' => 'thanks,thank you,thx',
				'response' => "You're welcome 😊 Happy to help anytime!",
				'priority' => 50,
			),
			array(
				'title'    => 'Goodbye',
				'keywords' => 'bye,goodbye,see you',
				'response' => 'Thanks for visiting 😊 Have a great day!',
				'priority' => 50,
			),

			// ── 🛍️ Product Discovery & Buying ──────────────────────────────
			array(
				'title'    => 'New Arrivals',
				'keywords' => 'latest,new arrivals',
				'response' => "We've got some great new arrivals 😊 Want me to show you the latest products?",
				'priority' => 73,
			),
			array(
				'title'    => 'Trending Products',
				'keywords' => 'trending,popular',
				'response' => 'These are some of our most popular picks 👍 Want recommendations based on your needs?',
				'priority' => 73,
			),
			array(
				'title'    => 'Best Sellers',
				'keywords' => 'best seller,top selling',
				'response' => 'Our best-selling products are highly rated 👍 Want me to show you the top options?',
				'priority' => 73,
			),
			array(
				'title'    => 'Product Comparison',
				'keywords' => 'compare',
				'response' => "Sure 👍 Tell me the products you want to compare, and I'll help you decide.",
				'priority' => 68,
			),
			array(
				'title'    => 'Which Is Better',
				'keywords' => 'which is better',
				'response' => "I can help you choose 👍 Share the options you're considering.",
				'priority' => 68,
			),

			// ── 💰 Pricing & Offers ─────────────────────────────────────────
			array(
				'title'    => 'Any Offer',
				'keywords' => 'any offer',
				'response' => "Yes 👍 We have ongoing offers. Tell me the product, and I'll check the best deal.",
				'priority' => 68,
			),
			array(
				'title'    => 'Best Price',
				'keywords' => 'best price',
				'response' => "I'll help you get the best deal 👍 Which product are you looking at?",
				'priority' => 70,
			),
			array(
				'title'    => 'Price Drop',
				'keywords' => 'price drop',
				'response' => 'Prices may change based on offers 👍 Want me to check current deals for you?',
				'priority' => 65,
			),
			array(
				'title'    => 'EMI Options',
				'keywords' => 'emi',
				'response' => 'EMI options may be available 👍 Tell me the product, and I\'ll confirm.',
				'priority' => 63,
			),
			array(
				'title'    => 'No Cost EMI',
				'keywords' => 'no cost emi',
				'response' => "Some products support no-cost EMI 👍 Share the item, and I'll check.",
				'priority' => 63,
			),

			// ── 🚚 Delivery & Logistics ─────────────────────────────────────
			array(
				'title'    => 'Fast Delivery',
				'keywords' => 'fast delivery',
				'response' => "We try to deliver as quickly as possible 🚀 Share your location, and I'll estimate delivery time.",
				'priority' => 67,
			),
			array(
				'title'    => 'Same Day Delivery',
				'keywords' => 'same day delivery',
				'response' => "Same-day delivery depends on your location 👍 Can you share your area?",
				'priority' => 70,
			),
			array(
				'title'    => 'Delivery Charges',
				'keywords' => 'delivery charges',
				'response' => "Delivery charges may vary 👍 Tell me your location, and I'll check for you.",
				'priority' => 63,
			),
			array(
				'title'    => 'International Shipping',
				'keywords' => 'international shipping',
				'response' => "We may support international shipping 🌍 Share your country, and I'll confirm.",
				'priority' => 58,
			),
			array(
				'title'    => 'Delayed Delivery',
				'keywords' => 'delayed delivery',
				'response' => "Sorry for the delay 😔 Please share your order ID, and I'll check the status.",
				'priority' => 82,
			),

			// ── 📦 Order & Account ──────────────────────────────────────────
			array(
				'title'    => 'Cancel Order',
				'keywords' => 'cancel order',
				'response' => "I can help with that 👍 Please share your order ID.",
				'priority' => 83,
			),
			array(
				'title'    => 'Change Address',
				'keywords' => 'change address',
				'response' => "Sure 👍 Share your order ID, and I'll guide you on updating the address.",
				'priority' => 80,
			),
			array(
				'title'    => 'Modify Order',
				'keywords' => 'modify order',
				'response' => "Let's check that 👍 Please provide your order ID.",
				'priority' => 78,
			),
			array(
				'title'    => 'Invoice Request',
				'keywords' => 'invoice',
				'response' => "You can download your invoice from your order details 👍 Need help finding it?",
				'priority' => 62,
			),
			array(
				'title'    => 'Login Problem',
				'keywords' => 'login problem',
				'response' => "I'm here to help 😊 Can you tell me what issue you're facing?",
				'priority' => 73,
			),

			// ── 🔁 Returns & Support ────────────────────────────────────────
			array(
				'title'    => 'Return Status',
				'keywords' => 'return status',
				'response' => "I'll check that 👍 Please share your return request ID.",
				'priority' => 80,
			),
			array(
				'title'    => 'Refund Status',
				'keywords' => 'refund status',
				'response' => "Refunds usually take a few days 👍 Share your order ID, and I'll check.",
				'priority' => 78,
			),
			array(
				'title'    => 'Damaged Product',
				'keywords' => 'damaged product',
				'response' => "Sorry about that 😔 Please share photos and order details so we can help.",
				'priority' => 85,
			),
			array(
				'title'    => 'Wrong Item',
				'keywords' => 'wrong item',
				'response' => "That shouldn't happen 😔 Please share your order ID, and we'll fix it.",
				'priority' => 85,
			),
			array(
				'title'    => 'Missing Item',
				'keywords' => 'missing item',
				'response' => "I'm sorry about that 😔 Please share your order ID so we can check.",
				'priority' => 83,
			),

			// ── 🧾 Product Info ─────────────────────────────────────────────
			array(
				'title'    => 'Specifications',
				'keywords' => 'specification,specs',
				'response' => "I can share the details 👍 Which product are you checking?",
				'priority' => 63,
			),
			array(
				'title'    => 'Size Info',
				'keywords' => 'size',
				'response' => "Sure 👍 Let me know the product, and I'll help with sizing details.",
				'priority' => 62,
			),
			array(
				'title'    => 'Color Options',
				'keywords' => 'color options',
				'response' => "Multiple color options may be available 🎨 Which product are you looking at?",
				'priority' => 60,
			),
			array(
				'title'    => 'Material Info',
				'keywords' => 'material',
				'response' => "I can help with that 👍 Tell me the product name.",
				'priority' => 60,
			),
			array(
				'title'    => 'Brand Info',
				'keywords' => 'brand',
				'response' => "We offer products from trusted brands 👍 Which one are you interested in?",
				'priority' => 62,
			),

			// ── 📊 Trust & Assurance ────────────────────────────────────────
			array(
				'title'    => 'Genuine Product',
				'keywords' => 'is it genuine',
				'response' => "Yes 👍 We only offer genuine products from trusted sources.",
				'priority' => 70,
			),
			array(
				'title'    => 'Quality Inquiry',
				'keywords' => 'quality',
				'response' => "Our products are quality-checked 👍 Let me know if you need details on a specific item.",
				'priority' => 67,
			),
			array(
				'title'    => 'Reviews',
				'keywords' => 'reviews',
				'response' => "Reviews can help a lot 👍 Want me to show feedback for a product?",
				'priority' => 63,
			),
			array(
				'title'    => 'Product Rating',
				'keywords' => 'rating',
				'response' => "Ratings are available for most products 👍 Which one are you checking?",
				'priority' => 62,
			),
			array(
				'title'    => 'Safe Payment',
				'keywords' => 'safe payment',
				'response' => "Yes 👍 All payments are secure and encrypted.",
				'priority' => 72,
			),

			// ── 🎯 Conversion Boosters ──────────────────────────────────────
			array(
				'title'    => 'Should I Buy',
				'keywords' => 'should i buy',
				'response' => "I can help you decide 👍 Tell me what you're looking for and your budget.",
				'priority' => 75,
			),
			array(
				'title'    => 'Worth It',
				'keywords' => 'worth it',
				'response' => "It depends on your needs 👍 Tell me more, and I'll guide you.",
				'priority' => 72,
			),
			array(
				'title'    => 'Recommend Me',
				'keywords' => 'recommend me',
				'response' => "Sure 😊 What type of product are you looking for?",
				'priority' => 73,
			),
			array(
				'title'    => 'Gift Suggestion',
				'keywords' => 'gift',
				'response' => "Great idea 🎁 Who is it for and what's your budget?",
				'priority' => 67,
			),
			array(
				'title'    => 'Combo Deals',
				'keywords' => 'combo',
				'response' => "Combo deals are available 👍 Want me to show some bundles?",
				'priority' => 63,
			),

			// ── 🧑‍💼 Support & Engagement ──────────────────────────────────
			array(
				'title'    => 'Callback Request',
				'keywords' => 'callback',
				'response' => "Sure 👍 Please share your contact details, and we'll call you.",
				'priority' => 67,
			),
			array(
				'title'    => 'WhatsApp Support',
				'keywords' => 'whatsapp',
				'response' => "We can connect on WhatsApp 👍 Please share your number.",
				'priority' => 63,
			),
			array(
				'title'    => 'Email Support',
				'keywords' => 'email support',
				'response' => "You can reach us via email 👍 Want me to share the details?",
				'priority' => 60,
			),
			array(
				'title'    => 'Working Hours',
				'keywords' => 'working hours',
				'response' => "Our team is available during business hours 😊 Need help now?",
				'priority' => 58,
			),
			array(
				'title'    => 'Location',
				'keywords' => 'location',
				'response' => "We operate online 👍 Let me know how I can help you.",
				'priority' => 57,
			),

			// ── ⚡ Smart Fallback / Engagement ──────────────────────────────
			array(
				'title'    => 'Confused',
				'keywords' => 'confused',
				'response' => "No worries 😊 Tell me what you're looking for, and I'll guide you step by step.",
				'priority' => 68,
			),
			array(
				'title'    => 'Help',
				'keywords' => 'help',
				'response' => "I'm here to help 😊 What do you need assistance with?",
				'priority' => 78,
			),
			array(
				'title'    => 'Not Sure',
				'keywords' => 'not sure',
				'response' => "That's okay 👍 Tell me your requirement, and I'll suggest options.",
				'priority' => 67,
			),
			array(
				'title'    => 'Show Options',
				'keywords' => 'show options',
				'response' => "Sure 👍 What kind of product are you interested in?",
				'priority' => 70,
			),
			array(
				'title'    => 'Browse',
				'keywords' => 'browse',
				'response' => "You can explore our products easily 😊 Want me to suggest something popular?",
				'priority' => 63,
			),
		);

		foreach ( $defaults as $rule ) {
			$wpdb->insert(
				$table,
				array(
					'title'      => $rule['title'],
					'keywords'   => $rule['keywords'],
					'response'   => $rule['response'],
					'match_type' => 'contains',
					'priority'   => (int) $rule['priority'],
					'status'     => 1,
					'created_at' => $now,
				),
				array( '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
			);
		}

		delete_transient( self::CACHE_KEY );
	}

	// -------------------------------------------------------------------------
	// Matching engine
	// -------------------------------------------------------------------------

	/**
	 * Find the first matching quick reply for a given message.
	 * Returns the response string on match, null otherwise.
	 */
	public function find_match( $message ) {
		$normalized = strtolower( trim( (string) $message ) );

		if ( '' === $normalized ) {
			return null;
		}

		foreach ( $this->get_rules() as $rule ) {
			$keywords = array_filter(
				array_map( 'trim', explode( ',', (string) $rule->keywords ) )
			);

			foreach ( $keywords as $keyword ) {
				$keyword = strtolower( $keyword );

				if ( '' === $keyword ) {
					continue;
				}

				$matched = ( 'exact' === $rule->match_type )
					? ( $normalized === $keyword )
					: str_contains( $normalized, $keyword );

				if ( $matched ) {
					return (string) $rule->response;
				}
			}
		}

		return null;
	}

	// -------------------------------------------------------------------------
	// Cache
	// -------------------------------------------------------------------------

	/**
	 * Load active rules from cache or DB, ordered by priority DESC.
	 *
	 * @return object[]
	 */
	private function get_rules() {
		$cached = get_transient( self::CACHE_KEY );

		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$rules = $wpdb->get_results(
			"SELECT keywords, response, match_type
			 FROM `{$this->table}`
			 WHERE status = 1
			 ORDER BY priority DESC, id ASC"
		);

		$rules = is_array( $rules ) ? $rules : array();

		set_transient( self::CACHE_KEY, $rules, self::CACHE_TTL );

		return $rules;
	}

	public function flush_cache() {
		delete_transient( self::CACHE_KEY );
	}

	// -------------------------------------------------------------------------
	// CRUD
	// -------------------------------------------------------------------------

	/** @return object[] */
	public function get_all() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		return $wpdb->get_results(
			"SELECT * FROM `{$this->table}` ORDER BY priority DESC, id ASC"
		) ?: array();
	}

	/** @return object|null */
	public function get_by_id( $id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `{$this->table}` WHERE id = %d", absint( $id ) )
		);

		return $row ?: null;
	}

	/**
	 * Insert a new rule. Returns the new ID on success, false on failure.
	 *
	 * @param array $data Keys: title, keywords, response, match_type, priority, status
	 * @return int|false
	 */
	public function insert( array $data ) {
		global $wpdb;

		$result = $wpdb->insert(
			$this->table,
			array(
				'title'      => mb_substr( (string) $data['title'], 0, 255 ),
				'keywords'   => (string) $data['keywords'],
				'response'   => (string) $data['response'],
				'match_type' => (string) $data['match_type'],
				'priority'   => (int) $data['priority'],
				'status'     => (int) $data['status'],
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
		);

		if ( false !== $result ) {
			$this->flush_cache();
			return (int) $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Update an existing rule. Returns true on success.
	 *
	 * @param int   $id
	 * @param array $data
	 */
	public function update( $id, array $data ) {
		global $wpdb;

		$result = $wpdb->update(
			$this->table,
			array(
				'title'      => mb_substr( (string) $data['title'], 0, 255 ),
				'keywords'   => (string) $data['keywords'],
				'response'   => (string) $data['response'],
				'match_type' => (string) $data['match_type'],
				'priority'   => (int) $data['priority'],
				'status'     => (int) $data['status'],
			),
			array( 'id' => absint( $id ) ),
			array( '%s', '%s', '%s', '%s', '%d', '%d' ),
			array( '%d' )
		);

		if ( false !== $result ) {
			$this->flush_cache();
			return true;
		}

		return false;
	}

	/** Delete a rule by ID. Returns true on success. */
	public function delete( $id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$this->table,
			array( 'id' => absint( $id ) ),
			array( '%d' )
		);

		if ( $result ) {
			$this->flush_cache();
			return true;
		}

		return false;
	}

	// -------------------------------------------------------------------------
	// admin_post handlers
	// -------------------------------------------------------------------------

	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'ai-woocommerce-assistant' ) );
		}

		check_admin_referer( 'aiwoo_save_quick_reply' );

		$id         = isset( $_POST['qr_id'] ) ? absint( $_POST['qr_id'] ) : 0;
		$title      = isset( $_POST['title'] )    ? sanitize_text_field( wp_unslash( $_POST['title'] ) )        : '';
		$keywords   = isset( $_POST['keywords'] ) ? sanitize_text_field( wp_unslash( $_POST['keywords'] ) )     : '';
		$response   = isset( $_POST['response'] ) ? sanitize_textarea_field( wp_unslash( $_POST['response'] ) ) : '';
		$match_type = ( isset( $_POST['match_type'] ) && 'exact' === $_POST['match_type'] ) ? 'exact' : 'contains';
		$priority   = isset( $_POST['priority'] ) ? max( 0, absint( $_POST['priority'] ) ) : 0;
		$status     = ! empty( $_POST['status'] ) ? 1 : 0;

		if ( '' === $title || '' === $keywords || '' === $response ) {
			wp_safe_redirect( admin_url( 'admin.php?page=sellora-ai-quick-replies&aiwoo_qr_msg=invalid' ) );
			exit;
		}

		$data = compact( 'title', 'keywords', 'response', 'match_type', 'priority', 'status' );

		if ( $id > 0 ) {
			$this->update( $id, $data );
			$msg = 'updated';
		} else {
			$this->insert( $data );
			$msg = 'saved';
		}

		wp_safe_redirect( admin_url( 'admin.php?page=sellora-ai-quick-replies&aiwoo_qr_msg=' . $msg ) );
		exit;
	}

	public function handle_delete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'ai-woocommerce-assistant' ) );
		}

		check_admin_referer( 'aiwoo_delete_quick_reply' );

		$id = isset( $_POST['qr_id'] ) ? absint( $_POST['qr_id'] ) : 0;

		if ( $id > 0 ) {
			$this->delete( $id );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=sellora-ai-quick-replies&aiwoo_qr_msg=deleted' ) );
		exit;
	}

	public function handle_save_from_ai() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'ai-woocommerce-assistant' ) );
		}

		check_admin_referer( 'aiwoo_save_qr_from_ai' );

		$keywords     = isset( $_POST['keywords'] )     ? sanitize_text_field( wp_unslash( $_POST['keywords'] ) )        : '';
		$response     = isset( $_POST['response'] )     ? sanitize_textarea_field( wp_unslash( $_POST['response'] ) )    : '';
		$priority     = isset( $_POST['priority'] )     ? max( 0, min( 100, absint( $_POST['priority'] ) ) )             : 60;
		$source_query = isset( $_POST['source_query'] ) ? sanitize_text_field( wp_unslash( $_POST['source_query'] ) )    : '';

		if ( '' === $keywords || '' === $response ) {
			wp_safe_redirect( admin_url( 'admin.php?page=sellora-ai-top-requests&aiwoo_tr_msg=invalid' ) );
			exit;
		}

		if ( $this->keyword_exists( $keywords ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=sellora-ai-top-requests&aiwoo_tr_msg=duplicate' ) );
			exit;
		}

		// Auto-generate title from the first 5 words of the source query.
		$words = array_filter( explode( ' ', trim( $source_query ) ) );
		$title = ucfirst( implode( ' ', array_slice( $words, 0, 5 ) ) );
		$title = mb_substr( $title, 0, 255 ) ?: __( 'Quick Reply', 'ai-woocommerce-assistant' );

		$this->insert( array(
			'title'      => $title,
			'keywords'   => $keywords,
			'response'   => $response,
			'match_type' => 'contains',
			'priority'   => $priority,
			'status'     => 1,
		) );

		wp_safe_redirect( admin_url( 'admin.php?page=sellora-ai-top-requests&aiwoo_tr_msg=saved' ) );
		exit;
	}

	/**
	 * Check whether any of the given comma-separated keywords already exist
	 * in an active quick reply rule.
	 */
	public function keyword_exists( $keywords_input ) {
		global $wpdb;

		$input_keywords = array_filter(
			array_map( 'trim', explode( ',', strtolower( (string) $keywords_input ) ) )
		);

		if ( empty( $input_keywords ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$stored = $wpdb->get_col( "SELECT keywords FROM `{$this->table}` WHERE status = 1" );

		$existing = array();
		foreach ( $stored as $rule_kw ) {
			foreach ( array_filter( array_map( 'trim', explode( ',', strtolower( $rule_kw ) ) ) ) as $kw ) {
				$existing[ $kw ] = true;
			}
		}

		foreach ( $input_keywords as $kw ) {
			if ( isset( $existing[ $kw ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return all active quick reply response strings as a hash-set for O(1) lookup.
	 * Used by the Top Requests page to classify responses as Quick Reply vs AI.
	 *
	 * @return array<string, true>
	 */
	public function get_response_set() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$responses = $wpdb->get_col( "SELECT DISTINCT response FROM `{$this->table}` WHERE status = 1" );

		return array_fill_keys( array_map( 'trim', $responses ), true );
	}
}
