<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Cli\Commands;

use CodeKandis\Console\Commands\CommandCollection;
use CodeKandis\Duplicator\Cli\Commands\Directory;
use CodeKandis\Duplicator\Configurations\CliConfigurationRegistryInterface;
use CodeKandis\SentryClient\SentryClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Represents the collection of commands of the application.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
class ApplicationCommandCollection extends CommandCollection
{
	/**
	 * Constructor method.
	 * @param LoggerInterface $logger The logger to inject into the commands.
	 * @param CliConfigurationRegistryInterface $configurationRegistry The configuration registry to inject into the command.
	 * @param SentryClientInterface $sentryClient The Sentry client to inject into the commands.
	 */
	public function __construct( LoggerInterface $logger, CliConfigurationRegistryInterface $configurationRegistry, SentryClientInterface $sentryClient )
	{
		parent::__construct(
			new Directory\Write\CompareScansCommand( $logger, null, $configurationRegistry, $sentryClient ),
			new Directory\Write\MoveDuplicatesCommand( $logger, null, $configurationRegistry, $sentryClient ),
			new Directory\Write\RemoveDuplicatesCommand( $logger, null, $configurationRegistry, $sentryClient ),
			new Directory\Write\ScanCommand( $logger, null, $configurationRegistry, $sentryClient )
		);
	}
}
