<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Configurations;

use CodeKandis\Configurations\AbstractConfigurationRegistry;
use CodeKandis\Configurations\PlainConfigurationLoader;
use CodeKandis\SentryClient\Configurations\SentryClientConfigurationInterface;
use function dirname;

/**
 * Represents a CLI configuration registry.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
class CliConfigurationRegistry extends AbstractConfigurationRegistry implements CliConfigurationRegistryInterface
{
	/**
	 * Stores the `SentryClient` configuration.
	 * @var ?SentryClientConfigurationInterface
	 */
	private ?SentryClientConfigurationInterface $sentryClientConfiguration = null;

	/**
	 * {@inheritDoc}
	 */
	public function getSentryClientConfiguration(): ?SentryClientConfigurationInterface
	{
		return $this->sentryClientConfiguration;
	}

	/**
	 * Creates the singleton instance of the CLI configuration registry.
	 * @return CliConfigurationRegistryInterface The singleton instance of the CLI configuration registry.
	 */
	public static function _(): CliConfigurationRegistryInterface
	{
		return parent::_();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function initialize(): void
	{
		$this->sentryClientConfiguration = new SentryClientConfiguration(
			( new PlainConfigurationLoader() )
				->load( __DIR__ . '/Plain', 'sentryClient' )
				->load( dirname( __DIR__, 2 ) . '/config', 'sentryClient' )
				->getPlainConfiguration()
		);
	}
}
