<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Environment\Io;

use Closure;
use CodeKandis\Duplicator\Environment\Entities\DirectoryListingEntityInterface;

/**
 * Represents the interface of any directory scanner.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
interface DirectoryScannerInterface
{
	/**
	 * Adds an event handler to the progress maximum counted event.
	 * @param Closure $eventHandler The event handler to add.
	 */
	public function addProgressMaximumCountedEventHandler( Closure $eventHandler ): void;

	/**
	 * Adds an event handler to the progress changed event.
	 * @param Closure $eventHandler The event handler to add.
	 */
	public function addProgressChangedEventHandler( Closure $eventHandler ): void;

	/**
	 * Scans the directory for all file entries.
	 * @return DirectoryListingEntityInterface The directory listing with all file entries.
	 */
	public function scan(): DirectoryListingEntityInterface;
}
