<?php
/**
 * Minimal PSR-4 autoloader for the Divi 5 SmartVideo module.
 *
 * Maps the Swarmify\Divi5\Modules\ namespace to the modules/ directory.
 * Replace with a proper Composer autoloader when running `composer install`.
 *
 * @package Swarmify\Divi5
 */

spl_autoload_register( function ( $class ) {
	$prefix   = 'Swarmify\\Divi5\\Modules\\';
	$base_dir = __DIR__ . '/../modules/';

	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );
	$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	if ( file_exists( $file ) ) {
		require $file;
	}
} );
