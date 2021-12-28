<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Environment\Entities;

use CodeKandis\Entities\AbstractEntity;

/**
 * Represents a directory listing.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
class DirectoryListingEntity extends AbstractEntity implements DirectoryListingEntityInterface
{
	/**
	 * Stores the path of the directory.
	 * @var string
	 */
	public string $path = '';

	/**
	 * Stores the file entries.
	 * @var FileEntryEntityCollectionInterface
	 */
	public FileEntryEntityCollectionInterface $fileEntries;

	/**
	 * Constructor method.
	 */
	public function __construct()
	{
		$this->fileEntries = new FileEntryEntityCollection();
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
	public function getFileEntries(): FileEntryEntityCollectionInterface
	{
		return $this->fileEntries;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setFileEntries( FileEntryEntityCollectionInterface $fileEntries ): void
	{
		$this->fileEntries = $fileEntries;
	}
}
