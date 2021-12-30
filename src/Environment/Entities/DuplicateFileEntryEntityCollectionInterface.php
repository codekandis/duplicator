<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Environment\Entities;

use CodeKandis\Entities\Collections\EntityCollectionInterface;
use CodeKandis\Entities\EntityInterface;

/**
 * Represents the interface of any duplicate file entry entity collection.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
interface DuplicateFileEntryEntityCollectionInterface extends EntityCollectionInterface
{
	/**
	 * Gets the current book.
	 * @return DuplicateFileEntryEntityInterface The current book.
	 */
	public function current(): EntityInterface;

	/**
	 * Gets the book at the specified index.
	 * @param int $index The index of the book.
	 * @return DuplicateFileEntryEntityInterface The book to get.
	 */
	public function offsetGet( $index ): EntityInterface;
}
