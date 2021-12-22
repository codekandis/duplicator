<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Cli\Commands;

use CodeKandis\Console\Commands\LoggableCommand;
use CodeKandis\Duplicator\Configurations\CliConfigurationRegistryInterface;
use CodeKandis\SentryClient\SentryClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Represents the base class of any command.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
abstract class AbstractCommand extends LoggableCommand
{
	/**
	 * Stores the configuration registry of the command.
	 * @var CliConfigurationRegistryInterface
	 */
	protected CliConfigurationRegistryInterface $configurationRegistry;

	/**
	 * Stores the Sentry client of the command.
	 * @var SentryClientInterface
	 */
	protected SentryClientInterface $sentryClient;

	/**
	 * Constructor method.
	 * @param LoggerInterface $logger The logger of the command.
	 * @param ?string $name The name of the command.
	 * @param CliConfigurationRegistryInterface $configurationRegistry The configuration registry of the command.
	 * @param SentryClientInterface $sentryClient The Sentry client of the command.
	 */
	public function __construct( LoggerInterface $logger, ?string $name, CliConfigurationRegistryInterface $configurationRegistry, SentryClientInterface $sentryClient )
	{
		parent::__construct( $logger, $name );

		$this->configurationRegistry = $configurationRegistry;
		$this->sentryClient          = $sentryClient;
	}
}
