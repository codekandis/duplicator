<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Environment\Io;

use CodeKandis\Duplicator\Environment\Entities\FileEntryEntity;
use CodeKandis\Duplicator\Environment\Entities\FileEntryEntityCollection;
use CodeKandis\Duplicator\Environment\Entities\FileEntryEntityCollectionInterface;
use CodeKandis\Duplicator\Environment\Entities\FileEntryEntityInterface;
use CodeKandis\RegularExpressions\RegularExpression;
use DirectoryIterator;
use ReflectionException;
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
	 * Constructor method.
	 * @param string $path The path of the directory.
	 */
	public function __construct( string $path )
	{
		$this->path = realpath( $path );
	}

	/**
	 * Reads the file entries of a specific path recursively.
	 * @param string $path The path to add its file entries.
	 * @return FileEntryEntityInterface[] The file entries.
	 * @throws ReflectionException An error occured during the creation of a file entry.
	 */
	private function readFileEntries( string $path ): array
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
				$fileEntries = array_merge( $fileEntries, $this->readFileEntries( $directoryEntry->getPathname() ) );

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
		}

		return $fileEntries;
	}

	/**
	 * {@inheritDoc}
	 * @throws ReflectionException An error occured during the creation of a file entry.
	 */
	public function scan(): FileEntryEntityCollectionInterface
	{
		return new FileEntryEntityCollection(
			$this->path,
			...$this->readFileEntries( $this->path )
		);
	}
}
