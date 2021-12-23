<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Environment\Entities;

use CodeKandis\Entities\Collections\AbstractEntityCollection;
use CodeKandis\Entities\EntityInterface;

/**
 * Represents the interface of any duplicate file entry entity collection.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
class DuplicateFileEntryEntityCollection extends AbstractEntityCollection implements DuplicateFileEntryEntityCollectionInterface
{
	/**
	 * Constructor method.
	 * @param DuplicateFileEntryEntityInterface[] $duplicateFileEntries The duplicate file entries of the collection.
	 */
	public function __construct( DuplicateFileEntryEntityInterface ...$duplicateFileEntries )
	{
		parent::__construct( ...$duplicateFileEntries );
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
}
