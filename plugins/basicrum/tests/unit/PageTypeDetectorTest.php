<?php
/**
 * Unit tests for PageTypeDetector.
 *
 * @package Basicrum\Tests\Unit
 */

namespace Basicrum\WP\Tests\Unit;

use Basicrum\WP\PageTypeDetector;
use Basicrum\WP\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * PageTypeDetectorTest - tests all conditional tag mappings.
 */
class PageTypeDetectorTest extends TestCase {

	/**
	 * The detector instance under test.
	 *
	 * @var PageTypeDetector
	 */
	private $detector;

	/**
	 * Set up test fixtures.
	 */
	protected function set_up() {
		parent::set_up();
		$this->detector = new PageTypeDetector();
	}

	/**
	 * Stub all WP conditionals to return false, then override specifics.
	 *
	 * @param array $true_conditionals List of conditional function names that should return true.
	 */
	private function stub_conditionals( $true_conditionals = array() ) {
		$wp_conditionals = array(
			'is_front_page',
			'is_page',
			'is_category',
			'is_tag',
			'is_tax',
			'is_author',
			'is_date',
			'is_archive',
			'is_search',
			'is_404',
		);

		foreach ( $wp_conditionals as $func ) {
			$return = in_array( $func, $true_conditionals, true );
			Functions\when( $func )->justReturn( $return );
		}

		Functions\when( 'is_singular' )->alias(
			function( $post_types = '' ) use ( $true_conditionals ) {
				if ( 'post' === $post_types ) {
					return in_array( 'is_singular_post', $true_conditionals, true );
				}

				return in_array( 'is_singular', $true_conditionals, true );
			}
		);

		Functions\when( 'apply_filters' )->alias( function() {
			$args = func_get_args();
			return $args[1];
		});
	}

	/**
	 * Test detection of home page.
	 */
	public function test_detects_home_page() {
		$this->stub_conditionals( array( 'is_front_page' ) );
		$this->assertSame( 'home', $this->detector->detect() );
	}

	/**
	 * Test detection of single post.
	 */
	public function test_detects_single_post() {
		$this->stub_conditionals( array( 'is_singular_post', 'is_singular' ) );
		$this->assertSame( 'post', $this->detector->detect() );
	}

	/**
	 * Test detection of page.
	 */
	public function test_detects_page() {
		$this->stub_conditionals( array( 'is_page', 'is_singular' ) );
		$this->assertSame( 'page', $this->detector->detect() );
	}

	/**
	 * Test detection of a custom post type.
	 */
	public function test_detects_custom_post() {
		$this->stub_conditionals( array( 'is_singular' ) );
		$this->assertSame( 'custom_post', $this->detector->detect() );
	}

	/**
	 * Test detection of category archive.
	 */
	public function test_detects_category() {
		$this->stub_conditionals( array( 'is_category' ) );
		$this->assertSame( 'category_archive', $this->detector->detect() );
	}

	/**
	 * Test detection of tag archive.
	 */
	public function test_detects_tag() {
		$this->stub_conditionals( array( 'is_tag' ) );
		$this->assertSame( 'tag_archive', $this->detector->detect() );
	}

	/**
	 * Test detection of a custom taxonomy archive.
	 */
	public function test_detects_taxonomy_archive() {
		$this->stub_conditionals( array( 'is_tax' ) );
		$this->assertSame( 'taxonomy_archive', $this->detector->detect() );
	}

	/**
	 * Test detection of author archive.
	 */
	public function test_detects_author() {
		$this->stub_conditionals( array( 'is_author' ) );
		$this->assertSame( 'author_archive', $this->detector->detect() );
	}

	/**
	 * Test detection of date archive.
	 */
	public function test_detects_date_archive() {
		$this->stub_conditionals( array( 'is_date' ) );
		$this->assertSame( 'date_archive', $this->detector->detect() );
	}

	/**
	 * Test detection of generic archive.
	 */
	public function test_detects_archive() {
		$this->stub_conditionals( array( 'is_archive' ) );
		$this->assertSame( 'archive', $this->detector->detect() );
	}

	/**
	 * Test detection of search results page.
	 */
	public function test_detects_search() {
		$this->stub_conditionals( array( 'is_search' ) );
		$this->assertSame( 'search', $this->detector->detect() );
	}

	/**
	 * Test detection of 404 page.
	 */
	public function test_detects_404() {
		$this->stub_conditionals( array( 'is_404' ) );
		$this->assertSame( '404', $this->detector->detect() );
	}

	/**
	 * Test 404 takes priority over other checks.
	 */
	public function test_404_takes_priority() {
		$this->stub_conditionals( array( 'is_404', 'is_page' ) );
		$this->assertSame( '404', $this->detector->detect() );
	}

	/**
	 * Test search takes priority over archive.
	 */
	public function test_search_takes_priority_over_archive() {
		$this->stub_conditionals( array( 'is_search', 'is_archive' ) );
		$this->assertSame( 'search', $this->detector->detect() );
	}

	/**
	 * Test unknown fallback when no condition matches.
	 */
	public function test_returns_unknown_when_nothing_matches() {
		$this->stub_conditionals();
		$this->assertSame( 'unknown', $this->detector->detect() );
	}

	/**
	 * Test the basicrum_page_type filter is applied.
	 */
	public function test_page_type_filter_is_applied() {
		$wp_conditionals = array(
			'is_front_page', 'is_singular', 'is_page', 'is_category',
			'is_tag', 'is_tax', 'is_author', 'is_date', 'is_archive', 'is_search', 'is_404',
		);
		foreach ( $wp_conditionals as $func ) {
			Functions\when( $func )->justReturn( false );
		}

		Functions\expect( 'apply_filters' )
			->once()
			->with( 'basicrum_page_type', 'unknown' )
			->andReturn( 'custom_type' );

		$this->assertSame( 'custom_type', $this->detector->detect() );
	}

	// -------------------------------------------------------------------------
	// WooCommerce page type tests
	// -------------------------------------------------------------------------

	/**
	 * Stub all WP + WooCommerce conditionals, with WooCommerce active.
	 *
	 * @param array $true_conditionals List of conditional function names that should return true.
	 */
	private function stub_woocommerce_conditionals( $true_conditionals = array() ) {
		// Standard WP conditionals - all false.
		$wp_conditionals = array(
			'is_front_page', 'is_singular', 'is_page', 'is_category',
			'is_tag', 'is_tax', 'is_author', 'is_date', 'is_archive', 'is_search', 'is_404',
		);
		foreach ( $wp_conditionals as $func ) {
			Functions\when( $func )->justReturn( false );
		}

		// WooCommerce conditionals - define all, set matching ones to true.
		$wc_conditionals = array(
			'is_order_received_page', 'is_checkout_pay_page', 'is_checkout', 'is_cart',
			'is_product', 'is_product_category', 'is_shop', 'is_account_page',
		);

		Functions\when( 'class_exists' )->alias( function( $class ) {
			return $class === 'WooCommerce' || \class_exists( $class );
		});

		foreach ( $wc_conditionals as $func ) {
			$return = in_array( $func, $true_conditionals, true );
			Functions\when( $func )->justReturn( $return );
		}

		Functions\when( 'apply_filters' )->alias( function() {
			$args = func_get_args();
			return $args[1];
		});
	}

	/**
	 * Test detection of WooCommerce checkout success page.
	 */
	public function test_detects_woocommerce_checkout_success() {
		$this->stub_woocommerce_conditionals( array( 'is_order_received_page' ) );
		$this->assertSame( 'checkout_success', $this->detector->detect() );
	}

	/**
	 * Test detection of WooCommerce order payment page before generic checkout.
	 */
	public function test_detects_woocommerce_checkout_payment_before_checkout() {
		$this->stub_woocommerce_conditionals( array( 'is_checkout_pay_page', 'is_checkout' ) );
		$this->assertSame( 'checkout_payment', $this->detector->detect() );
	}

	/**
	 * Test detection of WooCommerce checkout page.
	 */
	public function test_detects_woocommerce_checkout() {
		$this->stub_woocommerce_conditionals( array( 'is_checkout' ) );
		$this->assertSame( 'checkout', $this->detector->detect() );
	}

	/**
	 * Test detection of WooCommerce cart page.
	 */
	public function test_detects_woocommerce_cart() {
		$this->stub_woocommerce_conditionals( array( 'is_cart' ) );
		$this->assertSame( 'cart', $this->detector->detect() );
	}

	/**
	 * Test detection of WooCommerce product page.
	 */
	public function test_detects_woocommerce_product() {
		$this->stub_woocommerce_conditionals( array( 'is_product' ) );
		$this->assertSame( 'product', $this->detector->detect() );
	}

	/**
	 * Test detection of WooCommerce product category page.
	 */
	public function test_detects_woocommerce_product_category() {
		$this->stub_woocommerce_conditionals( array( 'is_product_category' ) );
		$this->assertSame( 'product_category', $this->detector->detect() );
	}

	/**
	 * Test detection of WooCommerce shop page.
	 */
	public function test_detects_woocommerce_shop() {
		$this->stub_woocommerce_conditionals( array( 'is_shop' ) );
		$this->assertSame( 'shop', $this->detector->detect() );
	}

	/**
	 * Test detection of WooCommerce account page.
	 */
	public function test_detects_woocommerce_account() {
		$this->stub_woocommerce_conditionals( array( 'is_account_page' ) );
		$this->assertSame( 'account', $this->detector->detect() );
	}

	/**
	 * Test WooCommerce types take priority over WP types.
	 */
	public function test_woocommerce_takes_priority_over_wordpress() {
		$this->stub_woocommerce_conditionals( array( 'is_product' ) );
		// A singular post would normally return 'post', but WooCommerce should win.
		Functions\when( 'is_singular' )->justReturn( true );
		$this->assertSame( 'product', $this->detector->detect() );
	}
}
