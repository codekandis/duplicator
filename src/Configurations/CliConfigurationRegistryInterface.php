<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Configurations;

use CodeKandis\Configurations\ConfigurationRegistryInterface;
use CodeKandis\SentryClient\Configurations\SentryClientConfigurationInterface;

/**
 * Represents the interface of any CLI configuration registry.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
interface CliConfigurationRegistryInterface extends ConfigurationRegistryInterface
{
	/**
	 * Gets the `SentryClient` configuration.
	 * @return ?SentryClientConfigurationInterface The `SentryClient` configuration.
	 */
	public function getSentryClientConfiguration(): ?SentryClientConfigurationInterface;
}
