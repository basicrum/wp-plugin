<?php
/**
 * Integration tests for page type detection against real WordPress queries.
 *
 * @package Basicrum\Tests\Integration
 */

namespace Basicrum\WP\Tests\Integration;

use Basicrum\WP\PageTypeDetector;

/**
 * Verifies custom content is classified using real WordPress conditionals.
 */
class PageTypeDetectorIntegrationTest extends \WP_UnitTestCase {

	/**
	 * Custom post type used by the test fixture.
	 *
	 * @var string
	 */
	const POST_TYPE = 'basicrum_test_item';

	/**
	 * Custom taxonomy used by the test fixture.
	 *
	 * @var string
	 */
	const TAXONOMY = 'basicrum_genre';

	/**
	 * Register custom content types before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		register_post_type(
			self::POST_TYPE,
			array(
				'public'    => true,
				'query_var' => true,
				'rewrite'   => false,
			)
		);

		register_taxonomy(
			self::TAXONOMY,
			'post',
			array(
				'public'    => true,
				'query_var' => true,
				'rewrite'   => false,
			)
		);
	}

	/**
	 * Unregister custom content types after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		unregister_post_type( self::POST_TYPE );
		unregister_taxonomy( self::TAXONOMY );

		parent::tear_down();
	}

	/**
	 * A single custom post must not be classified as a standard post.
	 *
	 * @return void
	 */
	public function test_detects_custom_post_from_main_query() {
		$post_id = self::factory()->post->create(
			array(
				'post_name' => 'basicrum-integration-item',
				'post_type' => self::POST_TYPE,
			)
		);

		$this->go_to(
			add_query_arg(
				self::POST_TYPE,
				get_post_field( 'post_name', $post_id ),
				home_url( '/' )
			)
		);

		$this->assertTrue( is_singular( self::POST_TYPE ) );
		$this->assertFalse( is_singular( 'post' ) );
		$this->assertSame( 'custom_post', ( new PageTypeDetector() )->detect() );
	}

	/**
	 * A custom taxonomy query must use the custom taxonomy page type.
	 *
	 * @return void
	 */
	public function test_detects_custom_taxonomy_from_main_query() {
		$post_id = self::factory()->post->create();
		$term    = wp_insert_term( 'Performance', self::TAXONOMY );

		$this->assertIsArray( $term );
		wp_set_object_terms( $post_id, (int) $term['term_id'], self::TAXONOMY );

		$this->go_to(
			add_query_arg(
				self::TAXONOMY,
				'performance',
				home_url( '/' )
			)
		);

		$this->assertTrue( is_tax( self::TAXONOMY ) );
		$this->assertSame( 'taxonomy_archive', ( new PageTypeDetector() )->detect() );
	}
}
