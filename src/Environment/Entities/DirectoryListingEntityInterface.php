<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Environment\Entities;

use CodeKandis\Entities\EntityInterface;

/**
 * Represents the interface of any directory listing.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
interface DirectoryListingEntityInterface extends EntityInterface
{
	/**
	 * Gets the path of the directory.
	 * @return string The path of the directory.
	 */
	public function getPath(): string;

	/**
	 * Sets the path of the directory.
	 * @param string $path The path of the directory.
	 */
	public function setPath( string $path ): void;

	/**
	 * Gets the file entries of the directory.
	 * @return FileEntryEntityCollectionInterface The file entries of the directory.
	 */
	public function getFileEntries(): FileEntryEntityCollectionInterface;

	/**
	 * Sets the file entries of the directory.
	 * @param FileEntryEntityCollectionInterface $fileEntries The file entries of the directory.
	 */
	public function setFileEntries( FileEntryEntityCollectionInterface $fileEntries ): void;
}
