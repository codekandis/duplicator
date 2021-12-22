<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Environment\Io;

use CodeKandis\Duplicator\Environment\Entities\FileEntryEntityCollectionInterface;

/**
 * Represents the interface of any directory scanner.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
interface DirectoryScannerInterface
{
	/**
	 * Scans the directory for all file entries.
	 * @return FileEntryEntityCollectionInterface The directory listing with all file entries.
	 */
	public function scan(): FileEntryEntityCollectionInterface;
}
