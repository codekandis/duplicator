<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Configurations\Plain;

use const E_ALL;

return [
	'dsn'           => '',
	'displayErrors' => true,
	'errorTypes'    => E_ALL,
	'environment'   => 'development',
	'release'       => '0.1.2',
	'serverName'    => 'duplicator.codekandis'
];
