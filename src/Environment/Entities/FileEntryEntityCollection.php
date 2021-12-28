<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Environment\Entities;

use CodeKandis\Entities\Collections\AbstractEntityCollection;
use CodeKandis\Entities\EntityInterface;

/**
 * Represents the interface of any file entry entity collection.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
class FileEntryEntityCollection extends AbstractEntityCollection implements FileEntryEntityCollectionInterface
{
	/**
	 * Constructor method.
	 * @param FileEntryEntityInterface[] $fileEntries The file entries of the collection.
	 */
	public function __construct( FileEntryEntityInterface ...$fileEntries )
	{
		parent::__construct( ...$fileEntries );
	}

	/**
	 * {@inheritDoc}
	 */
	public function current(): EntityInterface
	{
		return parent::current();
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetGet( $index ): EntityInterface
	{
		return parent::offsetGet( $index );
	}

	/**
	 * {@inheritDoc}
	 */
	public function findByRelativePath( string $relativePath ): ?FileEntryEntityInterface
	{
		foreach ( $this as $fileEntry )
		{
			if ( $fileEntry->getRelativePath() === $relativePath )
			{
				return $fileEntry;
			}
		}

		return null;
	}
}
