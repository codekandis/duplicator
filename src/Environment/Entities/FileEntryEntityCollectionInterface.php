<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Environment\Entities;

use CodeKandis\Entities\Collections\EntityCollectionInterface;
use CodeKandis\Entities\EntityInterface;

/**
 * Represents the interface of any file entry entity collection.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
interface FileEntryEntityCollectionInterface extends EntityCollectionInterface
{
	/**
	 * Gets the current file entry.
	 * @return FileEntryEntityInterface The current file entry.
	 */
	public function current(): EntityInterface;

	/**
	 * Gets the file entry at the specified index.
	 * @param int $index The index of the file entry.
	 * @return FileEntryEntityInterface The file entry to get.
	 */
	public function offsetGet( $index ): EntityInterface;

	/**
	 * Gets the path of the directory.
	 * @return string The path of the directory.
	 */
	public function getPath(): string;

	/**
	 * Finds a file entry by its relative path.
	 * @param string $relativePath The relative path of the file entry.
	 * @return ?FileEntryEntityInterface The file entry.
	 */
	public function findByRelativePath( string $relativePath ): ?FileEntryEntityInterface;
}
