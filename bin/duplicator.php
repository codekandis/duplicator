<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Bin;

use CodeKandis\Console\Commands\ApplicationCommandsInjector;
use CodeKandis\Duplicator\Cli\Commands\AbstractCommand;
use CodeKandis\Duplicator\Cli\Commands\ApplicationCommandCollection;
use CodeKandis\Duplicator\Cli\Loggers\ApplicationLoggerCollection;
use CodeKandis\Duplicator\Configurations\CliConfigurationRegistry;
use CodeKandis\SentryClient\SentryClient;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Throwable;
use function dirname;
use function error_reporting;
use function ini_set;
use function set_time_limit;
use const E_ALL;

/**
 * Represents the bootstrap script of the application.
 * @package codekandis/duplicator
 * @author  Christian Ramelow <info@codekandis.net>
 */
error_reporting( E_ALL );
ini_set( 'display_errors', 'On' );
ini_set( 'html_errors', 'Off' );

require_once dirname( __DIR__, 1 ) . '/vendor/autoload.php';

set_time_limit( 0 );

$cliConfigurationRegistry = CliConfigurationRegistry::_();

$sentryClient = new SentryClient(
	$cliConfigurationRegistry->getSentryClientConfiguration()
);
$sentryClient->register();

$application = new Application( 'codekandis/duplicator', 'development' );
$application->setCatchExceptions( false );

$applicationLoggerCollection = new ApplicationLoggerCollection();

try
{
	( new ApplicationCommandsInjector( $application ) )
		->inject(
			new ApplicationCommandCollection( $applicationLoggerCollection, $cliConfigurationRegistry, $sentryClient )
		);

	$application->run();
}
catch ( Throwable $throwable )
{
	$applicationLoggerCollection->log( LogLevel::ERROR, $throwable->getMessage() );
	$application->renderThrowable(
		$throwable,
		( new ConsoleOutput() )
			->getErrorOutput()
	);
	$sentryClient->captureThrowable( $throwable );

	exit( AbstractCommand::FAILURE );
}
