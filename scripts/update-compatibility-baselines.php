<?php

declare(strict_types=1);

function webmastery_mcp_baseline_fail( string $message ): void {
	fwrite( STDERR, "ERROR {$message}\n" );
	exit( 1 );
}

function webmastery_mcp_replace_baseline( string $path, string $pattern, string $replacement ): string {
	$contents = file_get_contents( $path );
	if ( false === $contents ) {
		webmastery_mcp_baseline_fail( "Could not read {$path}." );
	}

	$updated = preg_replace( $pattern, $replacement, $contents, 1, $count );
	if ( null === $updated || 1 !== $count ) {
		webmastery_mcp_baseline_fail( "Expected exactly one version reference in {$path}." );
	}

	return $updated;
}

$options = getopt( '', array( 'wordpress:', 'mcp-adapter:' ) );

$wordpress_version   = (string) ( $options['wordpress'] ?? '' );
$mcp_adapter_version = (string) ( $options['mcp-adapter'] ?? '' );

if ( ! preg_match( '/^\d+\.\d+(?:\.\d+)?$/', $wordpress_version ) ) {
	webmastery_mcp_baseline_fail( 'WordPress version must use X.Y or X.Y.Z format.' );
}

if ( ! preg_match( '/^\d+\.\d+\.\d+$/', $mcp_adapter_version ) ) {
	webmastery_mcp_baseline_fail( 'MCP Adapter version must use X.Y.Z format.' );
}

$repo_root    = dirname( __DIR__ );
$versions     = $repo_root . '/.github/compatibility-versions.json';
$e2e_runner   = $repo_root . '/scripts/e2e-test.sh';
$compose_file = $repo_root . '/docker-compose.yml';

$versions_contents = json_encode(
	array(
		'wordpress'  => $wordpress_version,
		'mcp_adapter' => $mcp_adapter_version,
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
);

if ( false === $versions_contents ) {
	webmastery_mcp_baseline_fail( 'Could not encode compatibility versions.' );
}

$updates = array(
	$versions     => $versions_contents . PHP_EOL,
	$e2e_runner   => webmastery_mcp_replace_baseline(
		$e2e_runner,
		'#releases/download/v\d+\.\d+\.\d+/mcp-adapter\.zip#',
		"releases/download/v{$mcp_adapter_version}/mcp-adapter.zip"
	),
	$compose_file => webmastery_mcp_replace_baseline(
		$compose_file,
		'#wordpress:\d+\.\d+(?:\.\d+)?-php8\.2-apache#',
		"wordpress:{$wordpress_version}-php8.2-apache"
	),
);

foreach ( $updates as $path => $contents ) {
	if ( false === file_put_contents( $path, $contents ) ) {
		webmastery_mcp_baseline_fail( "Could not write {$path}." );
	}
}

printf(
	"Updated compatibility baselines: WordPress %s, MCP Adapter %s\n",
	$wordpress_version,
	$mcp_adapter_version
);
