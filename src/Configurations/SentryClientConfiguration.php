<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Configurations;

use CodeKandis\Configurations\AbstractConfiguration;
use CodeKandis\SentryClient\Configurations\SentryClientConfigurationInterface;

/**
 * Represents a `SentryClient` configuration.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
class SentryClientConfiguration extends AbstractConfiguration implements SentryClientConfigurationInterface
{
	/**
	 * {@inheritDoc}
	 */
	public function getDsn(): string
	{
		return $this->read( 'dsn' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getErrorTypes(): ?int
	{
		return $this->readOrDefault( null, 'errorTypes' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDisplayErrors(): bool
	{
		return $this->read( 'displayErrors' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRelease(): ?string
	{
		return $this->readOrDefault( null, 'release' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getEnvironment(): ?string
	{
		return $this->readOrDefault( null, 'environment' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getSampleRate(): ?float
	{
		return $this->readOrDefault( null, 'sampleRate' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getMaxBreadcrumbs(): ?int
	{
		return $this->readOrDefault( null, 'maxBreadcrumbs' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAttachStacktrace(): ?bool
	{
		return $this->readOrDefault( null, 'attachStacktrace' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getSendDefaultPii(): ?bool
	{
		return $this->readOrDefault( null, 'sendDefaultPii' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getServerName(): ?string
	{
		return $this->readOrDefault( null, 'serverName' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getInAppExclude(): ?array
	{
		return $this->readOrDefault( null, 'inAppExclude' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRequestBodies(): ?string
	{
		return $this->readOrDefault( null, 'requestBodies' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIntegrations(): ?string
	{
		return $this->readOrDefault( null, 'integrations' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDefaultIntegrations(): ?bool
	{
		return $this->readOrDefault( null, 'defaultIntegrations' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getBeforeSend(): ?callable
	{
		return $this->readOrDefault( null, 'beforeSend' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getBeforeBreadcrumb(): ?callable
	{
		return $this->readOrDefault( null, 'beforeBreadcrumb' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getHttpProxy(): ?string
	{
		return $this->readOrDefault( null, 'httpProxy' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getCaptureSilencedErrors(): ?bool
	{
		return $this->readOrDefault( null, 'captureSilencedErrors' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getContextLines(): ?int
	{
		return $this->readOrDefault( null, 'contextLines' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getEnableCompression(): ?bool
	{
		return $this->readOrDefault( null, 'enableCompression' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getExcludedAppPaths(): ?array
	{
		return $this->readOrDefault( null, 'excludedAppPaths' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getExcludedExceptions(): ?array
	{
		return $this->readOrDefault( null, 'excludedExceptions' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPrefixes(): ?array
	{
		return $this->readOrDefault( null, 'prefixes' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getProjectRoot(): ?string
	{
		return $this->readOrDefault( null, 'projectRoot' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getSendAttempts(): ?int
	{
		return $this->readOrDefault( null, 'sendAttempts' );
	}
}
