<?php
/**
 * Unit tests for WordPress privacy-policy integration.
 *
 * @package Basicrum\Tests\Unit
 */

namespace Basicrum\WP\Tests\Unit;

use Basicrum\WP\Admin\Privacy;
use Basicrum\WP\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * PrivacyTest - tests suggested privacy-policy disclosure text.
 */
class PrivacyTest extends TestCase {

	/**
	 * Set up WordPress function stubs.
	 */
	protected function set_up() {
		parent::set_up();

		Functions\when( 'add_action' )->justReturn();
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( 'wp_parse_args' )->alias(
			function( $args, $defaults ) {
				return array_merge( $defaults, $args );
			}
		);
	}

	/**
	 * Test consent-controlled policy text describes data and synchronization.
	 */
	public function test_consent_controlled_policy_content_is_transparent() {
		$this->set_settings(
			array(
				'enabled'         => '1',
				'beacon_url'      => 'https://collector.example.test/beacon',
				'brum_site_id'    => '550e8400-e29b-41d4-a716-446655440000',
				'consent_enabled' => '1',
				'strip_query_string' => '1',
			)
		);

		$title   = null;
		$content = null;
		Functions\expect( 'wp_add_privacy_policy_content' )
			->once()
			->andReturnUsing(
				function( $policy_title, $policy_content ) use ( &$title, &$content ) {
					$title   = $policy_title;
					$content = $policy_content;
				}
			);

		$privacy = new Privacy();
		$privacy->add_policy_content();

		$this->assertSame( 'Basicrum - Real User Monitoring', $title );
		$this->assertStringContainsString( 'page and resource URLs', $content );
		$this->assertStringContainsString( 'replace complete query strings', $content );
		$this->assertStringContainsString( '?qs-redacted', $content );
		$this->assertStringContainsString( 'URL paths are still collected', $content );
		$this->assertStringContainsString( 'IP address and user agent', $content );
		$this->assertStringContainsString( '<code>https://collector.example.test/beacon</code>', $content );
		$this->assertStringContainsString( 'consent tool on every page', $content );
		$this->assertStringContainsString( 'does not persist consent across page loads', $content );
		$this->assertStringContainsString( 'opt-out policy applies', $content );
		$this->assertStringContainsString( 'does not retract data already sent', $content );
	}

	/**
	 * Test disabled query-string stripping is disclosed as a privacy risk.
	 */
	public function test_disabled_query_string_stripping_is_disclosed() {
		$this->set_settings(
			array(
				'enabled'            => '1',
				'beacon_url'         => 'https://collector.example.test/beacon',
				'brum_site_id'       => '550e8400-e29b-41d4-a716-446655440000',
				'strip_query_string' => '0',
			)
		);

		$content = null;
		Functions\expect( 'wp_add_privacy_policy_content' )
			->once()
			->andReturnUsing(
				function( $policy_title, $policy_content ) use ( &$content ) {
					$content = $policy_content;
				}
			);

		$privacy = new Privacy();
		$privacy->add_policy_content();

		$this->assertStringContainsString( 'Query-string stripping is disabled', $content );
		$this->assertStringContainsString( 'personal or sensitive information', $content );
	}

	/**
	 * Test immediate-loading policy text discloses the lack of a consent gate.
	 */
	public function test_immediate_loading_policy_content_is_transparent() {
		$this->set_settings(
			array(
				'enabled'         => '1',
				'beacon_url'      => 'https://collector.example.test/beacon',
				'brum_site_id'    => '550e8400-e29b-41d4-a716-446655440000',
				'consent_enabled' => '0',
			)
		);

		$content = null;
		Functions\expect( 'wp_add_privacy_policy_content' )
			->once()
			->andReturnUsing(
				function( $policy_title, $policy_content ) use ( &$content ) {
					$content = $policy_content;
				}
			);

		$privacy = new Privacy();
		$privacy->add_policy_content();

		$this->assertStringContainsString( 'start immediately on eligible pages', $content );
		$this->assertStringContainsString( 'collector configured at', $content );
	}

	/**
	 * Test an inactive installation is clearly marked as tutorial-only.
	 */
	public function test_inactive_monitoring_is_not_described_as_running() {
		$this->set_settings(
			array(
				'enabled'         => '0',
				'consent_enabled' => '0',
			)
		);

		$content = null;
		Functions\expect( 'wp_add_privacy_policy_content' )
			->once()
			->andReturnUsing(
				function( $policy_title, $policy_content ) use ( &$content ) {
					$content = $policy_content;
				}
			);

		$privacy = new Privacy();
		$privacy->add_policy_content();

		$this->assertStringContainsString( 'monitoring is currently inactive', $content );
		$this->assertStringNotContainsString( 'configured to start immediately', $content );
	}
}
