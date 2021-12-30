<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Environment\Entities;

use CodeKandis\Entities\AbstractEntity;

/**
 * Represents a file entry entity.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
class FileEntryEntity extends AbstractEntity implements FileEntryEntityInterface
{
	/**
	 * Stores the root path of the file.
	 * @var string
	 */
	public string $rootPath = '';

	/**
	 * Stores the path of the file.
	 * @var string
	 */
	public string $path = '';

	/**
	 * Stores the relative path of the file.
	 * @var string
	 */
	public string $relativePath = '';

	/**
	 * Stores the size of the file.
	 * @var int
	 */
	public int $size;

	/**
	 * Stores the MD5 checksum of the file.
	 * @var string
	 */
	public string $md5Checksum = '';

	/**
	 * {@inheritDoc}
	 */
	public function getRootPath(): string
	{
		return $this->rootPath;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setRootPath( string $rootPath ): void
	{
		$this->rootPath = $rootPath;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setPath( string $path ): void
	{
		$this->path = $path;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRelativePath(): string
	{
		return $this->relativePath;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setRelativePath( string $relativePath ): void
	{
		$this->relativePath = $relativePath;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getSize(): int
	{
		return $this->size;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setSize( int $size ): void
	{
		$this->size = $size;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getMd5Checksum(): string
	{
		return $this->md5Checksum;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setMd5Checksum( string $md5Checksum ): void
	{
		$this->md5Checksum = $md5Checksum;
	}
}
