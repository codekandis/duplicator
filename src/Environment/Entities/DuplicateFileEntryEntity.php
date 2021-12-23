<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Environment\Entities;

use CodeKandis\Entities\AbstractEntity;

/**
 * Represents a duplicate file entry entity.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
class DuplicateFileEntryEntity extends AbstractEntity implements DuplicateFileEntryEntityInterface
{
	/**
	 * Stores the target file entry.
	 * @var ?FileEntryEntityInterface
	 */
	public ?FileEntryEntityInterface $targetFileEntry = null;

	/**
	 * Stores the merge file entry.
	 * @var ?FileEntryEntityInterface
	 */
	public ?FileEntryEntityInterface $mergeFileEntry = null;

	/**
	 * {@inheritDoc}
	 */
	public function getTargetFileEntry(): ?FileEntryEntityInterface
	{
		return $this->targetFileEntry;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setTargetFileEntry( ?FileEntryEntityInterface $targetFileEntry ): void
	{
		$this->targetFileEntry = $targetFileEntry;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getMergeFileEntry(): ?FileEntryEntityInterface
	{
		return $this->mergeFileEntry;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setMergeFileEntry( ?FileEntryEntityInterface $mergeFileEntry ): void
	{
		$this->mergeFileEntry = $mergeFileEntry;
	}
}
