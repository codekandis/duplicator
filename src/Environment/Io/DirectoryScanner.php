<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Environment\Io;

use Closure;
use CodeKandis\Duplicator\Environment\Entities\FileEntryEntity;
use CodeKandis\Duplicator\Environment\Entities\FileEntryEntityCollection;
use CodeKandis\Duplicator\Environment\Entities\FileEntryEntityCollectionInterface;
use CodeKandis\Duplicator\Environment\Entities\FileEntryEntityInterface;
use CodeKandis\RegularExpressions\RegularExpression;
use DirectoryIterator;
use ReflectionException;
use function array_merge;
use function clearstatcache;
use function filesize;
use function md5_file;
use function realpath;
use function sprintf;

/**
 * Represents the interface of any directory scanner.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
class DirectoryScanner implements DirectoryScannerInterface
{
	/**
	 * Represents the regular expression pattern to extract a relative path.
	 * @var string
	 */
	private const REGEX_RELATIVE_PATH_PATTERN = '~^%s(.+)~';

	/**
	 * Represents the regular expression replacement to extract a relative path.
	 * @var string
	 */
	private const REGEX_RELATIVE_PATH_REPLACEMENT = '$1';

	/**
	 * Stores the path of the directory.
	 * @var string
	 */
	private string $path;

	/**
	 * Stores the event handlers of the progress maximum counted event.
	 * @var Closure[]
	 */
	private array $progressMaximumCountedEventHandlers = [];

	/**
	 * Stores the event handlers of the progress changed event.
	 * @var Closure[]
	 */
	private array $progressChangedEventHandlers = [];

	/**
	 * Constructor method.
	 * @param string $path The path of the directory.
	 */
	public function __construct( string $path )
	{
		$this->path = realpath( $path );
	}

	/**
	 * Raises the progress maximum counted event.
	 * @param int $progressMaximum The progress maximum.
	 */
	private function raiseProgressMaximumCounted( int $progressMaximum ): void
	{
		foreach ( $this->progressMaximumCountedEventHandlers as $eventHandler )
		{
			$eventHandler( $progressMaximum );
		}
	}

	/**
	 * Raises the progress changed event.
	 * @param string $currentFile The path of the current processed file.
	 * @param int $currentProgress The current progress.
	 */
	private function raiseProgressChanged( string $currentFile, int $currentProgress ): void
	{
		foreach ( $this->progressChangedEventHandlers as $eventHandler )
		{
			$eventHandler( $currentFile, $currentProgress );
		}
	}

	/**
	 * Counts all files that will be scanned.
	 * @param string $path The path to get counted.
	 * @param int $amount The current amount.
	 * @return int The amount of files that will be scanned.
	 */
	private function countFileEntries( string $path, int $amount = 0 ): int
	{
		/**
		 * @var DirectoryIterator $directoryEntry
		 */
		foreach ( new DirectoryIterator( $path ) as $directoryEntry )
		{
			if ( true === $directoryEntry->isDot() )
			{
				continue;
			}

			if ( true === $directoryEntry->isDir() )
			{
				$amount = $this->countFileEntries( $directoryEntry->getPathname(), $amount );

				continue;
			}

			$amount++;
		}

		return $amount;
	}

	/**
	 * Reads the file entries of a specific path recursively.
	 * @param string $path The path to add its file entries.
	 * @param int &$currentProgress The current progress.
	 * @return FileEntryEntityInterface[] The file entries.
	 * @throws ReflectionException An error occured during the creation of a file entry.
	 */
	private function readFileEntries( string $path, int &$currentProgress = 0 ): array
	{
		$fileEntries = [];

		/**
		 * @var DirectoryIterator $directoryEntry
		 */
		foreach ( new DirectoryIterator( $path ) as $directoryEntry )
		{
			if ( true === $directoryEntry->isDot() )
			{
				continue;
			}

			if ( true === $directoryEntry->isDir() )
			{
				$fileEntries = array_merge( $fileEntries, $this->readFileEntries( $directoryEntry->getPathname(), $currentProgress ) );

				continue;
			}

			clearstatcache( true, $directoryEntry->getPathname() );
			$fileEntries[] = FileEntryEntity::fromArray(
				[
					'rootPath' => $this->path,
					'path' => $directoryEntry->getPathname(),
					'relativePath' => ( new RegularExpression(
						sprintf(
							static::REGEX_RELATIVE_PATH_PATTERN,
							$this->path
						)
					) )
						->replace( static::REGEX_RELATIVE_PATH_REPLACEMENT, $directoryEntry->getPathname(), true ),
					'size' => filesize( $directoryEntry->getPathname() ),
					'md5Checksum' => md5_file( $directoryEntry->getPathname() )
				]
			);

			$this->raiseProgressChanged( $directoryEntry->getPathname(), ++$currentProgress );
		}

		return $fileEntries;
	}

	/**
	 * {@inheritDoc}
	 */
	public function addProgressMaximumCountedEventHandler( Closure $eventHandler ): void
	{
		$this->progressMaximumCountedEventHandlers[] = $eventHandler;
	}

	/**
	 * {@inheritDoc}
	 */
	public function addProgressChangedEventHandler( Closure $eventHandler ): void
	{
		$this->progressChangedEventHandlers[] = $eventHandler;
	}

	/**
	 * {@inheritDoc}
	 * @throws ReflectionException An error occured during the creation of a file entry.
	 */
	public function scan(): FileEntryEntityCollectionInterface
	{
		$this->raiseProgressMaximumCounted(
			$this->countFileEntries( $this->path )
		);
		$this->raiseProgressChanged( '', 0 );

		return new FileEntryEntityCollection(
			$this->path,
			...$this->readFileEntries( $this->path )
		);
	}
}
