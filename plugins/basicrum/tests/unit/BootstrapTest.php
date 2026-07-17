<?php
/**
 * Tests for the plugin bootstrap failure path.
 *
 * @package Basicrum\Tests\Unit
 */

namespace Basicrum\WP\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifies bootstrap failures remain visible and non-fatal.
 */
class BootstrapTest extends TestCase {

	/**
	 * Ensure a missing Composer autoloader produces an administrator notice.
	 *
	 * The bootstrap is executed in a separate PHP process because it defines
	 * plugin constants and returns early when the autoloader is unavailable.
	 *
	 * @return void
	 */
	public function test_missing_composer_autoloader_registers_admin_notice() {
		$temporary_directory = sys_get_temp_dir() . '/basicrum-bootstrap-' . bin2hex( random_bytes( 8 ) );
		$bootstrap_file       = $temporary_directory . '/basicrum.php';
		$source_file          = dirname( __DIR__, 2 ) . '/basicrum.php';

		$this->assertTrue( mkdir( $temporary_directory ) );
		$this->assertTrue( copy( $source_file, $bootstrap_file ) );

		$runner = <<<'PHP'
define( 'ABSPATH', sys_get_temp_dir() . '/' );
$GLOBALS['basicrum_test_actions'] = array();

function plugin_dir_path( $file ) {
	return dirname( $file ) . DIRECTORY_SEPARATOR;
}

function add_action( $hook_name, $callback ) {
	$GLOBALS['basicrum_test_actions'][ $hook_name ] = $callback;
}

function esc_html__( $text, $domain ) {
	return $text;
}

function current_user_can( $capability ) {
	return 'activate_plugins' === $capability;
}

require $argv[1];

if ( ! isset( $GLOBALS['basicrum_test_actions']['admin_notices'] ) ) {
	fwrite( STDERR, "Missing admin notice callback.\n" );
	exit( 1 );
}

ob_start();
call_user_func( $GLOBALS['basicrum_test_actions']['admin_notices'] );
$notice = ob_get_clean();

if ( false === strpos( $notice, 'Composer dependencies are missing' ) ) {
	fwrite( STDERR, "Unexpected admin notice: $notice\n" );
	exit( 1 );
}
PHP;

		$command = escapeshellarg( PHP_BINARY )
			. ' -r ' . escapeshellarg( $runner )
			. ' ' . escapeshellarg( $bootstrap_file )
			. ' 2>&1';
		$output  = array();
		$status  = 1;

		try {
			exec( $command, $output, $status );
			$this->assertSame( 0, $status, implode( "\n", $output ) );
		} finally {
			unlink( $bootstrap_file );
			rmdir( $temporary_directory );
		}
	}
}
