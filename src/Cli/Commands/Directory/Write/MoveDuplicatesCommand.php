<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Cli\Commands\Directory\Write;

use CodeKandis\Duplicator\Cli\Commands\AbstractCommand;
use CodeKandis\Duplicator\Environment\Entities\DirectoryListingEntity;
use CodeKandis\Duplicator\Environment\Entities\DirectoryListingEntityInterface;
use CodeKandis\Duplicator\Environment\Entities\FileEntryEntity;
use CodeKandis\Duplicator\Environment\Entities\FileEntryEntityCollection;
use CodeKandis\Duplicator\Environment\Entities\FileEntryEntityInterface;
use CodeKandis\Duplicator\Environment\Io\DirectoryNotFoundException;
use CodeKandis\Duplicator\Environment\Io\DirectoryNotWritableException;
use CodeKandis\Duplicator\Environment\Io\FileFoundException;
use CodeKandis\Duplicator\Environment\Io\FileNotFoundException;
use CodeKandis\Duplicator\Environment\Io\FileNotReadableException;
use CodeKandis\JsonCodec\JsonDecoder;
use CodeKandis\JsonCodec\JsonDecoderOptions;
use JsonException;
use ReflectionException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function array_keys;
use function array_map;
use function count;
use function fclose;
use function feof;
use function fgets;
use function fopen;
use function is_dir;
use function is_file;
use function is_readable;
use function is_writable;
use function pathinfo;
use function sprintf;
use const PATHINFO_DIRNAME;

/**
 * Represents the command to move duplicates.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
class MoveDuplicatesCommand extends AbstractCommand
{
	/**
	 * Represents the error message if a file does not exist.
	 * @var string
	 */
	protected const ERROR_FILE_NOT_FOUND = 'The file `%s` does not exist.';

	/**
	 * Represents the error message if the duplicate file listing file does not exist.
	 * @var string
	 */
	protected const ERROR_DUPLICATE_FILE_LISTING_FILE_NOT_FOUND = 'The duplicate file listing file `%s` does not exist.';

	/**
	 * Represents the error message if the duplicate file listing file is not readable.
	 * @var string
	 */
	protected const ERROR_DUPLICATE_FILE_LISTING_FILE_NOT_READABLE = 'The duplicate file listing file `%s` is not readable.';

	/**
	 * Represents the error message if the target directory does not exist.
	 * @var string
	 */
	protected const ERROR_TARGET_DIRECTORY_NOT_FOUND = 'The target directory `%s` does not exist.';

	/**
	 * Represents the error message if the target directory is not writable.
	 * @var string
	 */
	protected const ERROR_TARGET_DIRECTORY_NOT_WRITABLE = 'The target directory `%s` is not writable.';

	/**
	 * Represents the error message if a target file already exists.
	 * @var string
	 */
	protected const ERROR_TARGET_FILE_FOUND = 'The target file `%s` already exists.';

	/**
	 * Represents the progress bar format definition of `processed decoded file`.
	 * @var string
	 */
	protected const PROGRESS_BAR_FORMAT_DEFINITION_PROCESSED_DECODED_FILE = "Decode JSON duplicate file listing data: %s\n%%path%%\n%%current%%/%%max%% [%%bar%%] %%percent%%%%\n%%elapsed%%/%%estimated%% %%memory%%\n";

	/**
	 * Represents the progress bar format definition of `processed moving duplicate file`.
	 * @var string
	 */
	protected const PROGRESS_BAR_FORMAT_DEFINITION_PROCESSED_MOVING_DUPLICATE_FILE = "Moving duplicates: %s\n%%relativePath%%\n%%current%%/%%max%% [%%bar%%] %%percent%%%%\n%%elapsed%%/%%estimated%% %%memory%%\n";

	/**
	 * {@inheritDoc}
	 */
	protected const COMMAND_NAME = 'directory:move-duplicates';

	/**
	 * {@inheritDoc}
	 */
	protected const COMMAND_DESCRIPTION = 'Moves duplicate files.';

	/**
	 * Represents the command argument `duplicate-file-listing-file`.
	 * @var string
	 */
	protected const COMMAND_ARGUMENT_DUPLICATE_FILE_LISTING_FILE = 'duplicate-file-listing-file';

	/**
	 * Represents the command argument `target-directory`.
	 * @var string
	 */
	protected const COMMAND_ARGUMENT_TARGET_DIRECTORY = 'target-directory';

	/**
	 * {@inheritDoc}
	 */
	protected const COMMAND_ARGUMENTS = [
		[
			'name'        => self::COMMAND_ARGUMENT_DUPLICATE_FILE_LISTING_FILE,
			'mode'        => InputArgument::REQUIRED,
			'description' => 'The duplicate file listing file.'
		],
		[
			'name'        => self::COMMAND_ARGUMENT_TARGET_DIRECTORY,
			'mode'        => InputArgument::REQUIRED,
			'description' => 'The target directory of the files to move.'
		]
	];

	/**
	 * Validates the command arguments.
	 * @param string $duplicateFileListingFile The duplicate file listing file.
	 * @throws FileNotFoundException The duplicate file listing file does not exist.
	 * @throws FileNotReadableException The duplicate file listing file is not readable.
	 */
	public function validateArguments( string $duplicateFileListingFile, string $targetDirectory ): void
	{
		if ( false === is_file( $duplicateFileListingFile ) )
		{
			throw new FileNotFoundException(
				sprintf(
					static::ERROR_DUPLICATE_FILE_LISTING_FILE_NOT_FOUND,
					$duplicateFileListingFile
				)
			);
		}
		if ( false === is_readable( $duplicateFileListingFile ) )
		{
			throw new FileNotReadableException(
				sprintf(
					static::ERROR_DUPLICATE_FILE_LISTING_FILE_NOT_READABLE,
					$duplicateFileListingFile
				)
			);
		}

		if ( false === is_dir( $targetDirectory ) )
		{
			throw new DirectoryNotFoundException(
				sprintf(
					static::ERROR_TARGET_DIRECTORY_NOT_FOUND,
					$targetDirectory
				)
			);
		}
		if ( false === is_writable( $targetDirectory ) )
		{
			throw new DirectoryNotWritableException(
				sprintf(
					static::ERROR_TARGET_DIRECTORY_NOT_WRITABLE,
					$targetDirectory
				)
			);
		}
	}

	/**
	 * Creates a progress bar.
	 * @param OutputInterface $output The output to use for the progress bar.
	 * @param string $duplicateFileListingFile The directory scan file.
	 * @return ProgressBar The created progress bar.
	 */
	private function createDecodeJsonDuplicateFileListingDataProgressBar( OutputInterface $output, string $duplicateFileListingFile ): ProgressBar
	{
		ProgressBar::setFormatDefinition(
			'processedDecodedFile',
			sprintf(
				static::PROGRESS_BAR_FORMAT_DEFINITION_PROCESSED_DECODED_FILE,
				$duplicateFileListingFile
			)
		);

		$progressBar = $output instanceof ConsoleOutputInterface
			? new ProgressBar( $output->section() )
			: new ProgressBar( $output );
		$progressBar->setFormat( 'processedDecodedFile' );
		$progressBar->setMessage( '', 'path' );

		return $progressBar;
	}

	/**
	 * Creates a progress bar.
	 * @param OutputInterface $output The output to use for the progress bar.
	 * @param string $path The path of the directory listing.
	 * @return ProgressBar The created progress bar.
	 */
	private function createMoveDuplicatesProgressBar( OutputInterface $output, string $path ): ProgressBar
	{
		ProgressBar::setFormatDefinition(
			'processedMovingDuplicateFile',
			sprintf(
				static::PROGRESS_BAR_FORMAT_DEFINITION_PROCESSED_MOVING_DUPLICATE_FILE,
				$path
			)
		);

		$progressBar = $output instanceof ConsoleOutputInterface
			? new ProgressBar( $output->section() )
			: new ProgressBar( $output );
		$progressBar->setFormat( 'processedMovingDuplicateFile' );
		$progressBar->setMessage( '', 'relativePath' );

		return $progressBar;
	}

	/**
	 * Reads a duplicate file listing file.
	 * @param string $duplicateFileListingFile The directory scan file to read.
	 * @return string The read JSON data.
	 * @throws FileNotReadableException The duplicate file listing file is not readable.
	 */
	private function readDuplicateFileListingFile( string $duplicateFileListingFile ): string
	{
		$fileHandle = fopen( $duplicateFileListingFile, 'rb' );

		if ( false === $fileHandle )
		{
			throw new FileNotReadableException(
				sprintf(
					static::ERROR_DUPLICATE_FILE_LISTING_FILE_NOT_READABLE,
					$duplicateFileListingFile
				)
			);
		}

		$duplicateFileListingData = '';
		while ( false === feof( $fileHandle ) )
		{
			$readData = fgets( $fileHandle );
			if ( false === $readData )
			{
				fclose( $fileHandle );

				throw new FileNotReadableException(
					sprintf(
						static::ERROR_DUPLICATE_FILE_LISTING_FILE_NOT_READABLE,
						$duplicateFileListingFile
					)
				);
			}

			$duplicateFileListingData .= $readData;
		}

		return $duplicateFileListingData;
	}

	/**
	 * Decodes the JSON duplicate file listing data into a duplicate file listing.
	 * @param ProgressBar $progressBar The progress bar to use for updating the progress.
	 * @param string $jsonDuplicateFileListingData The JSON duplicate file listing to decode.
	 * @return DirectoryListingEntityInterface The decoded duplicate file listing.
	 * @throws JsonException An error occured during JSON decoding.
	 * @throws ReflectionException An error occured during the creation of a file entry.
	 * @throws ReflectionException An error occured during the creation of the duplicate file listing.
	 */
	private function decodeJsonDuplicateFileListingData( ProgressBar $progressBar, string $jsonDuplicateFileListingData ): DirectoryListingEntityInterface
	{
		$decodedJsonDirectoryScanData = ( new JsonDecoder() )
			->decode(
				$jsonDuplicateFileListingData,
				new JsonDecoderOptions( JsonDecoderOptions::OBJECT_AS_ARRAY )
			);

		$progressBar->setMaxSteps(
			count( $decodedJsonDirectoryScanData )
		);

		return 0 === count( $decodedJsonDirectoryScanData )
			? DirectoryListingEntity::fromArray(
				[
					'path'        => '',
					'fileEntries' => new FileEntryEntityCollection()
				]
			)
			: DirectoryListingEntity::fromArray(
				[
					'path'        => $decodedJsonDirectoryScanData[ 0 ][ 'mergeFileEntry' ][ 'path' ],
					'fileEntries' => new FileEntryEntityCollection(
						...array_map(
							function ( array $duplicateFileEntry, int $duplicateFileEntryIndex ) use ( $progressBar ): FileEntryEntityInterface
							{
								$progressBar->setMessage( $duplicateFileEntry[ 'mergeFileEntry' ][ 'path' ], 'path' );
								$progressBar->setProgress( $duplicateFileEntryIndex + 1 );

								return FileEntryEntity::fromArray(
									[
										'rootPath'     => $duplicateFileEntry[ 'mergeFileEntry' ][ 'rootPath' ],
										'path'         => $duplicateFileEntry[ 'mergeFileEntry' ][ 'path' ],
										'relativePath' => $duplicateFileEntry[ 'mergeFileEntry' ][ 'relativePath' ],
										'size'         => $duplicateFileEntry[ 'mergeFileEntry' ][ 'size' ],
										'md5Checksum'  => $duplicateFileEntry[ 'mergeFileEntry' ][ 'md5Checksum' ]
									]
								);
							},
							$decodedJsonDirectoryScanData,
							array_keys( $decodedJsonDirectoryScanData )
						)
					)
				]
			);
	}

	/**
	 * Removes all duplicate file entries.
	 * @param ProgressBar $progressBar The progress bar to use for updating the progress.
	 * @param DirectoryListingEntityInterface $duplicateFileListing The duplicate file listing.
	 * @param string $targetDirectory The target directory to move the files to.
	 * @throws FileNotFoundException The file to move does not exist.
	 * @throws FileFoundException A target file already exist.
	 */
	private function moveDuplicateFileEntries( ProgressBar $progressBar, DirectoryListingEntityInterface $duplicateFileListing, string $targetDirectory ): void
	{
		$progressBar->setMaxSteps(
			count(
				$duplicateFileListing->getFileEntries()
			)
		);

		foreach ( $duplicateFileListing->getFileEntries() as $fileEntryIndex => $fileEntry )
		{
			$progressBar->setProgress( $fileEntryIndex + 1 );
			$progressBar->setMessage(
				$fileEntry->getRelativePath(),
				'relativePath'
			);

			if ( false === is_file( $fileEntry->getPath() ) )
			{
				throw new FileNotFoundException(
					sprintf(
						static::ERROR_FILE_NOT_FOUND,
						$fileEntry->getPath()
					)
				);
			}

			$targetFilePath = sprintf(
				'%s%s',
				$targetDirectory,
				$fileEntry->getRelativePath()
			);

			if ( true === is_file( $targetFilePath ) )
			{
				throw new FileFoundException(
					sprintf(
						static::ERROR_TARGET_FILE_FOUND,
						$targetFilePath
					)
				);
			}

			$targetDirectoryPath = pathinfo( $targetFilePath, PATHINFO_DIRNAME );
			if ( false === is_dir( $targetDirectoryPath ) )
			{
				mkdir( $targetDirectoryPath, 0755, true );
			}

			rename( $fileEntry->getPath(), $targetFilePath );
		}
	}

	/**
	 * {@inheritDoc}
	 * @throws FileNotFoundException The duplicate file listing file does not exist.
	 * @throws FileNotReadableException The duplicate file listing file is not readable.
	 * @throws FileNotFoundException The file to remove does not exist.
	 * @throws FileFoundException A target file already exist.
	 * @throws JsonException An error occured during JSON decoding.
	 * @throws ReflectionException An error occured during the creation of a file entry.
	 * @throws ReflectionException An error occured during the creation of the duplicate file listing.
	 * @throws ReflectionException An error occured during the creation of a duplicate file entry.
	 * @throws JsonException An error occured during JSON encoding.
	 */
	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$duplicateFileListingFile = $input->getArgument( static::COMMAND_ARGUMENT_DUPLICATE_FILE_LISTING_FILE );
		$targetDirectory          = $input->getArgument( static::COMMAND_ARGUMENT_TARGET_DIRECTORY );
		$this->validateArguments( $duplicateFileListingFile, $targetDirectory );

		$duplicateFileListingData = $this->readDuplicateFileListingFile( $duplicateFileListingFile );
		$duplicateFileListing     = $this->decodeJsonDuplicateFileListingData(
			$this->createDecodeJsonDuplicateFileListingDataProgressBar( $output, $duplicateFileListingFile ),
			$duplicateFileListingData
		);

		$this->moveDuplicateFileEntries(
			$this->createMoveDuplicatesProgressBar(
				$output,
				$duplicateFileListing->getPath()
			),
			$duplicateFileListing,
			$targetDirectory
		);

		return static::SUCCESS;
	}
}
