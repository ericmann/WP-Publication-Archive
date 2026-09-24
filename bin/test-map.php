<?php
/**
 * Verifies that every includes/**\/class-*.php and interface-*.php has a
 * matching tests/unit/test-<slug>.php or tests/integration/test-<slug>.php,
 * declares the plugin namespace, names the SPEC section it implements and
 * carries an @author tag in its file docblock.
 *
 * Implements SPEC.md §3: "Every class has a test file."
 */

$root     = dirname( __DIR__ );
$includes = $root . '/includes';
$failures = array();
$checked  = 0;

$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $includes, FilesystemIterator::SKIP_DOTS ) );

/** @var array<int, SplFileInfo> $files */
$files = array();
foreach ( $iterator as $file ) {
	if ( ! $file->isFile() || 'php' !== $file->getExtension() ) {
		continue;
	}

	$name = $file->getFilename();
	if ( 0 !== strpos( $name, 'class-' ) && 0 !== strpos( $name, 'interface-' ) ) {
		continue;
	}

	$files[] = $file;
}

sort( $files );

foreach ( $files as $file ) {
	++$checked;

	$name = $file->getFilename();
	$slug = preg_replace( '/^(class|interface)-/', '', $name );
	$slug = preg_replace( '/\.php$/', '', (string) $slug );

	$unit_test        = $root . '/tests/unit/test-' . $slug . '.php';
	$integration_test = $root . '/tests/integration/test-' . $slug . '.php';

	if ( ! file_exists( $unit_test ) && ! file_exists( $integration_test ) ) {
		$failures[] = 'MISSING TEST: ' . $file->getPathname();
	}

	// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- reason: bin/ scripts run outside WordPress on local source files, not remote data.
	$contents = file_get_contents( $file->getPathname() );

	if ( false === $contents || ! preg_match( '/^namespace WPPA(\\\\[A-Za-z]+)?;/m', $contents ) ) {
		$failures[] = 'MISSING NAMESPACE: ' . $file->getPathname();
	}

	if ( false === $contents || false === strpos( $contents, 'SPEC.md §' ) ) {
		$failures[] = 'MISSING SPEC REF: ' . $file->getPathname();
	}

	if ( false === $contents || ! preg_match( '/^ \* @author .+$/m', $contents ) ) {
		$failures[] = 'MISSING @author: ' . $file->getPathname();
	}
}

if ( $failures ) {
	foreach ( $failures as $failure ) {
		echo $failure . PHP_EOL;
	}
	exit( 1 );
}

echo 'test map OK (' . $checked . ' files)' . PHP_EOL;
exit( 0 );
