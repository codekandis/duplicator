<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Environment\Entities;

/**
 * Represents the interface of any duplicate file entry entity.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
interface DuplicateFileEntryEntityInterface
{
	/**
	 * Gets the target file entry.
	 * @return ?FileEntryEntityInterface The target file entry.
	 */
	public function getTargetFileEntry(): ?FileEntryEntityInterface;

	/**
	 * Sets the target file entry.
	 * @param ?FileEntryEntityInterface $targetFileEntry The target file entry.
	 */
	public function setTargetFileEntry( ?FileEntryEntityInterface $targetFileEntry ): void;

	/**
	 * Gets the merge file entry.
	 * @return ?FileEntryEntityInterface The merge file entry.
	 */
	public function getMergeFileEntry(): ?FileEntryEntityInterface;

	/**
	 * Sets the merge file entry.
	 * @param ?FileEntryEntityInterface $mergeFileEntry The merge file entry.
	 */
	public function setMergeFileEntry( ?FileEntryEntityInterface $mergeFileEntry ): void;
}
