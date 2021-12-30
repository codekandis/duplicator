<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Cli\Commands\Directory\Write;

use CodeKandis\Duplicator\Cli\Commands\AbstractCommand;
use CodeKandis\Duplicator\Environment\Entities\DirectoryListingEntity;
use CodeKandis\Duplicator\Environment\Entities\DirectoryListingEntityInterface;
use CodeKandis\Duplicator\Environment\Entities\DuplicateFileEntryEntity;
use CodeKandis\Duplicator\Environment\Entities\DuplicateFileEntryEntityCollection;
use CodeKandis\Duplicator\Environment\Entities\DuplicateFileEntryEntityCollectionInterface;
use CodeKandis\Duplicator\Environment\Entities\FileEntryEntity;
use CodeKandis\Duplicator\Environment\Entities\FileEntryEntityCollection;
use CodeKandis\Duplicator\Environment\Entities\FileEntryEntityInterface;
use CodeKandis\Duplicator\Environment\Io\DirectoryNotFoundException;
use CodeKandis\Duplicator\Environment\Io\DirectoryNotWritableException;
use CodeKandis\Duplicator\Environment\Io\FileNotCreatableException;
use CodeKandis\Duplicator\Environment\Io\FileNotFoundException;
use CodeKandis\Duplicator\Environment\Io\FileNotReadableException;
use CodeKandis\JsonCodec\JsonDecoder;
use CodeKandis\JsonCodec\JsonDecoderOptions;
use CodeKandis\JsonCodec\JsonEncoder;
use CodeKandis\JsonCodec\JsonEncoderOptions;
use JsonException;
use ReflectionException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use function array_keys;
use function array_map;
use function count;
use function fclose;
use function feof;
use function fgets;
use function fopen;
use function fputs;
use function is_dir;
use function is_file;
use function is_readable;
use function is_writable;
use function pathinfo;
use function realpath;
use function sprintf;

/**
 * Represents the command to compare two directory scans for duplicates.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
class CompareScansCommand extends AbstractCommand
{
	/**
	 * Represents the error message if a directory does not exist.
	 * @var string
	 */
	protected const ERROR_DIRECTORY_NOT_FOUND = 'The directory `%s` does not exist.';

	/**
	 * Represents the error message if a directory is not writable.
	 * @var string
	 */
	protected const ERROR_DIRECTORY_NOT_WRITABLE = 'The directory `%s` is not writable.';

	/**
	 * Represents the error message if the output file cannot be created.
	 * @var string
	 */
	protected const ERROR_OUTPUT_FILE_NOT_CREATABLE = 'The output file `%s` cannot be created';

	/**
	 * Represents the error message if a directory scan file does not exist.
	 * @var string
	 */
	protected const ERROR_DIRECTORY_SCAN_FILE_NOT_FOUND = 'The directory scan file `%s` does not exist.';

	/**
	 * Represents the error message if a directory scan file is not readable.
	 * @var string
	 */
	protected const ERROR_DIRECTORY_SCAN_FILE_NOT_READABLE = 'The directory scan file `%s` is not readable.';

	/**
	 * Represents the progress bar format definition of `processed decoded file`.
	 * @var string
	 */
	protected const PROGRESS_BAR_FORMAT_DEFINITION_PROCESSED_DECODED_FILE = "Decode JSON directory scan data: %s\n%%path%%\n%%current%%/%%max%% [%%bar%%] %%percent%%%%\n%%elapsed%%/%%estimated%% %%memory%%\n";

	/**
	 * Represents the progress bar format definition of `processed directory listing file`.
	 * @var string
	 */
	protected const PROGRESS_BAR_FORMAT_DEFINITION_PROCESSED_DIRECTORY_LISTING_FILE = "Determining duplicates: %s\n%%relativePath%%\n%%current%%/%%max%% [%%bar%%] %%percent%%%%\n%%elapsed%%/%%estimated%% %%memory%%\n";

	/**
	 * Represents the QUESTION if an existing output file should be overwritten.
	 * @var string
	 */
	protected const QUESTION_OVERWRIT_OUTPUT_FILE = 'The output file `%s` already exists. Overwrite? [y/N]: ';

	/**
	 * {@inheritDoc}
	 */
	protected const COMMAND_NAME = 'directory:compare-scans';

	/**
	 * {@inheritDoc}
	 */
	protected const COMMAND_DESCRIPTION = 'Compares two directory scans for duplicates.';

	/**
	 * Represents the command option `output-file`.
	 * @var string
	 */
	protected const COMMAND_OPTION_OUTPUT_FILE = 'output-file';

	/**
	 * Represents the command argument `target-directory-scan-file`.
	 * @var string
	 */
	protected const COMMAND_ARGUMENT_TARGET_DIRECTORY_SCAN_FILE = 'target-directory-scan-file';

	/**
	 * Represents the command argument `merge-directory-scan-file`.
	 * @var string
	 */
	protected const COMMAND_ARGUMENT_MERGE_DIRECTORY_SCAN_FILE = 'merge-directory-scan-file';

	/**
	 * {@inheritDoc}
	 */
	protected const COMMAND_OPTIONS = [
		[
			'name'        => self::COMMAND_OPTION_OUTPUT_FILE,
			'shortcut'    => 'o',
			'mode'        => InputOption::VALUE_OPTIONAL,
			'description' => 'The file to output into.'
		]
	];

	/**
	 * {@inheritDoc}
	 */
	protected const COMMAND_ARGUMENTS = [
		[
			'name'        => self::COMMAND_ARGUMENT_TARGET_DIRECTORY_SCAN_FILE,
			'mode'        => InputArgument::REQUIRED,
			'description' => 'The scan file of the target directory.'
		],
		[
			'name'        => self::COMMAND_ARGUMENT_MERGE_DIRECTORY_SCAN_FILE,
			'mode'        => InputArgument::REQUIRED,
			'description' => 'The scan file of the directory to merge into the target directory.'
		]
	];

	/**
	 * Validates the command options.
	 * @param InputInterface $input The input to use for the validation.
	 * @param OutputInterface $output The output to use for the validation.
	 * @param ?string $outputFile The path of the output file.
	 * @return bool True if the options are valid, otherwise false.
	 * @throws DirectoryNotFoundException The output file directory does not exist.
	 * @throws DirectoryNotWritableException The output file directory is not writable.
	 */
	public function validateOptions( InputInterface $input, OutputInterface $output, ?string $outputFile ): bool
	{
		if ( null !== $outputFile )
		{
			$pathinfo  = pathinfo( $outputFile );
			$directory = realpath( $pathinfo[ 'dirname' ] );

			if ( false === $directory || false === is_dir( $directory ) )
			{
				throw new DirectoryNotFoundException(
					sprintf(
						static::ERROR_DIRECTORY_NOT_FOUND,
						$pathinfo[ 'dirname' ]
					)
				);
			}
			if ( false === is_writable( $directory ) )
			{
				throw new DirectoryNotWritableException(
					sprintf(
						static::ERROR_DIRECTORY_NOT_WRITABLE,
						$pathinfo[ 'dirname' ]
					)
				);
			}
			if ( true === is_file( $outputFile ) )
			{
				$answere = $this
					->getHelper( 'question' )
					->ask(
						$input,
						$output,
						new ConfirmationQuestion(
							sprintf(
								static::QUESTION_OVERWRIT_OUTPUT_FILE,
								$outputFile
							),
							false,
						)
					);
				if ( false === $answere )
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Validates the command arguments.
	 * @param string $targetDirectoryScanFile The target directory scan file.
	 * @param string $mergeDirectoryScanFile The merge directory scan file.
	 * @throws FileNotFoundException The target directory scan file does not exist.
	 * @throws FileNotReadableException The target directory scan file is not readable.
	 * @throws FileNotFoundException The merge directory scan file does not exist.
	 * @throws FileNotReadableException The merge directory scan file is not readable.
	 */
	public function validateArguments( string $targetDirectoryScanFile, string $mergeDirectoryScanFile ): void
	{
		if ( false === is_file( $targetDirectoryScanFile ) )
		{
			throw new FileNotFoundException(
				sprintf(
					static::ERROR_DIRECTORY_SCAN_FILE_NOT_FOUND,
					$targetDirectoryScanFile
				)
			);
		}
		if ( false === is_readable( $targetDirectoryScanFile ) )
		{
			throw new FileNotReadableException(
				sprintf(
					static::ERROR_DIRECTORY_SCAN_FILE_NOT_READABLE,
					$targetDirectoryScanFile
				)
			);
		}

		if ( false === is_file( $mergeDirectoryScanFile ) )
		{
			throw new FileNotFoundException(
				sprintf(
					static::ERROR_DIRECTORY_SCAN_FILE_NOT_FOUND,
					$mergeDirectoryScanFile
				)
			);
		}
		if ( false === is_readable( $mergeDirectoryScanFile ) )
		{
			throw new FileNotReadableException(
				sprintf(
					static::ERROR_DIRECTORY_SCAN_FILE_NOT_READABLE,
					$mergeDirectoryScanFile
				)
			);
		}
	}

	/**
	 * Creates a progress bar.
	 * @param OutputInterface $output The output to use for the progress bar.
	 * @param string $directoryScanFile The directory scan file.
	 * @return ProgressBar The created progress bar.
	 */
	private function createDecodeJsonDirectoryScanDataProgressBar( OutputInterface $output, string $directoryScanFile ): ProgressBar
	{
		ProgressBar::setFormatDefinition(
			'processedDecodedFile',
			sprintf(
				static::PROGRESS_BAR_FORMAT_DEFINITION_PROCESSED_DECODED_FILE,
				$directoryScanFile
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
	private function createDeterminingDuplicatesProgressBar( OutputInterface $output, string $path ): ProgressBar
	{
		ProgressBar::setFormatDefinition(
			'processedDirectoryListingFile',
			sprintf(
				static::PROGRESS_BAR_FORMAT_DEFINITION_PROCESSED_DIRECTORY_LISTING_FILE,
				$path
			)
		);

		$progressBar = $output instanceof ConsoleOutputInterface
			? new ProgressBar( $output->section() )
			: new ProgressBar( $output );
		$progressBar->setFormat( 'processedDirectoryListingFile' );
		$progressBar->setMessage( '', 'relativePath' );

		return $progressBar;
	}

	/**
	 * Reads a directory scan file.
	 * @param string $directoryScanFile The directory scan file to read.
	 * @return string The read JSON data.
	 * @throws FileNotReadableException The target directory scan file is not readable.
	 * @throws FileNotReadableException The merge directory scan file is not readable.
	 */
	private function readDirectoryScanFile( string $directoryScanFile ): string
	{
		$fileHandle = fopen( $directoryScanFile, 'rb' );

		if ( false === $fileHandle )
		{
			throw new FileNotReadableException(
				sprintf(
					static::ERROR_DIRECTORY_SCAN_FILE_NOT_READABLE,
					$directoryScanFile
				)
			);
		}

		$directoryScanData = '';
		while ( false === feof( $fileHandle ) )
		{
			$readData = fgets( $fileHandle );
			if ( false === $readData )
			{
				fclose( $fileHandle );

				throw new FileNotReadableException(
					sprintf(
						static::ERROR_DIRECTORY_SCAN_FILE_NOT_READABLE,
						$directoryScanFile
					)
				);
			}

			$directoryScanData .= $readData;
		}

		return $directoryScanData;
	}

	/**
	 * Decodes the JSON directory scan data into a directory listing.
	 * @param ProgressBar $progressBar The progress bar to use for updating the progress.
	 * @param string $jsonDirectoryScanData The JSON directory scan data to decode.
	 * @return DirectoryListingEntityInterface The decoded directory listing.
	 * @throws JsonException An error occured during JSON decoding.
	 * @throws ReflectionException An error occured during the creation of a file entry.
	 * @throws ReflectionException An error occured during the creation of the directory listing.
	 */
	private function decodeJsonDirectoryScanData( ProgressBar $progressBar, string $jsonDirectoryScanData ): DirectoryListingEntityInterface
	{
		$decodedJsonDirectoryScanData = ( new JsonDecoder() )
			->decode(
				$jsonDirectoryScanData,
				new JsonDecoderOptions( JsonDecoderOptions::OBJECT_AS_ARRAY )
			);

		$progressBar->setMaxSteps(
			count( $decodedJsonDirectoryScanData[ 'fileEntries' ] )
		);

		return DirectoryListingEntity::fromArray(
			[
				'path'        => $decodedJsonDirectoryScanData[ 'path' ],
				'fileEntries' => new FileEntryEntityCollection(
					...array_map(
						function ( array $fileEntry, int $fileEntryIndex ) use ( $progressBar ): FileEntryEntityInterface
						{
							$progressBar->setMessage( $fileEntry[ 'path' ], 'path' );
							$progressBar->setProgress( $fileEntryIndex + 1 );

							return FileEntryEntity::fromArray(
								[
									'rootPath'     => $fileEntry[ 'rootPath' ],
									'path'         => $fileEntry[ 'path' ],
									'relativePath' => $fileEntry[ 'relativePath' ],
									'size'         => $fileEntry[ 'size' ],
									'md5Checksum'  => $fileEntry[ 'md5Checksum' ]
								]
							);
						},
						$decodedJsonDirectoryScanData[ 'fileEntries' ],
						array_keys( $decodedJsonDirectoryScanData[ 'fileEntries' ] )
					)
				)
			]
		);
	}

	/**
	 * Determines all duplicate file entries.
	 * @param ProgressBar $progressBar The progress bar to use for updating the progress.
	 * @param DirectoryListingEntityInterface $targetDirectoryListing The target directory listing.
	 * @param DirectoryListingEntityInterface $mergeDirectoryListing The merge directory listing.
	 * @return DuplicateFileEntryEntityCollectionInterface The duplicate file entries.
	 * @throws ReflectionException An error occured during the creation of a duplicate file entry.
	 */
	private function determineDuplicateFileEntries( ProgressBar $progressBar, DirectoryListingEntityInterface $targetDirectoryListing, DirectoryListingEntityInterface $mergeDirectoryListing ): DuplicateFileEntryEntityCollectionInterface
	{
		$progressBar->setMaxSteps(
			count(
				$targetDirectoryListing->getFileEntries()
			)
		);

		$duplicateFileEntries = [];
		foreach ( $targetDirectoryListing->getFileEntries() as $fileEntryIndex => $targetFileEntry )
		{
			$progressBar->setProgress( $fileEntryIndex + 1 );
			$progressBar->setMessage(
				$targetFileEntry->getRelativePath(),
				'relativePath'
			);

			$mergeFileEntry = $mergeDirectoryListing
				->getFileEntries()
				->findByRelativePath(
					$targetFileEntry->getRelativePath()
				);

			if ( null !== $mergeFileEntry && $mergeFileEntry->getMd5Checksum() === $targetFileEntry->getMd5Checksum() )
			{
				$duplicateFileEntries[] = DuplicateFileEntryEntity::fromArray(
					[
						'targetFileEntry' => $targetFileEntry,
						'mergeFileEntry'  => $mergeFileEntry
					]
				);
			}
		}

		return new DuplicateFileEntryEntityCollection( ...$duplicateFileEntries );
	}

	/**
	 * Gets the JSON result data of the duplicate file entries.
	 * @param DuplicateFileEntryEntityCollectionInterface $duplicateFileEntries The duplicate file entries.
	 * @return string The JSON result data.
	 * @throws JsonException An error occured during JSON encoding.
	 */
	private function getJsonResultData( DuplicateFileEntryEntityCollectionInterface $duplicateFileEntries ): string
	{
		return ( new JsonEncoder() )
			->encode(
				$duplicateFileEntries,
				new JsonEncoderOptions( JsonEncoderOptions::PRETTY_PRINT )
			);
	}

	/**
	 * Outputs the resulting JSON data into a file.
	 * @param string $jsonResultData The resulting JSON result data.
	 * @param string $outputFile The file to output into.
	 * @throws FileNotCreatableException The output file is not createable.
	 */
	private function outputToFile( string $jsonResultData, string $outputFile ): void
	{
		$fileHandle = fopen( $outputFile, 'wb' );

		if ( false === $fileHandle )
		{
			throw new FileNotCreatableException(
				sprintf(
					static::ERROR_OUTPUT_FILE_NOT_CREATABLE,
					$outputFile
				)
			);
		}

		fputs( $fileHandle, $jsonResultData );
		fclose( $fileHandle );
	}

	/**
	 * {@inheritDoc}
	 * @throws DirectoryNotFoundException The output file directory does not exist.
	 * @throws DirectoryNotWritableException The output file directory is not writable.
	 * @throws FileNotFoundException The target directory scan file does not exist.
	 * @throws FileNotReadableException The target directory scan file is not readable.
	 * @throws FileNotFoundException The merge directory scan file does not exist.
	 * @throws FileNotReadableException The merge directory scan file is not readable.
	 * @throws FileNotCreatableException The output file is not createable.
	 * @throws ReflectionException An error occured during the creation of a file entry.
	 * @throws ReflectionException An error occured during the creation of the directory listing.
	 * @throws ReflectionException An error occured during the creation of a duplicate file entry.
	 * @throws JsonException An error occured during JSON encoding.
	 * @throws JsonException An error occured during JSON encoding.
	 */
	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$outputFile = $input->getOption( static::COMMAND_OPTION_OUTPUT_FILE );
		if ( false === $this->validateOptions( $input, $output, $outputFile ) )
		{
			return static::FAILURE;
		};

		$targetDirectoryScanFile = $input->getArgument( static::COMMAND_ARGUMENT_TARGET_DIRECTORY_SCAN_FILE );
		$mergeDirectoryScanFile  = $input->getArgument( static::COMMAND_ARGUMENT_MERGE_DIRECTORY_SCAN_FILE );
		$this->validateArguments( $targetDirectoryScanFile, $mergeDirectoryScanFile );

		$targetDirectoryScanData = $this->readDirectoryScanFile( $targetDirectoryScanFile );
		$targetDirectoryListing  = $this->decodeJsonDirectoryScanData(
			$this->createDecodeJsonDirectoryScanDataProgressBar( $output, $targetDirectoryScanFile ),
			$targetDirectoryScanData
		);

		$mergeDirectoryScanData = $this->readDirectoryScanFile( $mergeDirectoryScanFile );
		$mergeDirectoryListing  = $this->decodeJsonDirectoryScanData(
			$this->createDecodeJsonDirectoryScanDataProgressBar( $output, $mergeDirectoryScanFile ),
			$mergeDirectoryScanData
		);

		$duplicateFileEntries = $this->determineDuplicateFileEntries(
			$this->createDeterminingDuplicatesProgressBar(
				$output,
				$targetDirectoryListing->getPath()
			),
			$targetDirectoryListing,
			$mergeDirectoryListing
		);

		$jsonResultData = $this->getJsonResultData( $duplicateFileEntries );

		if ( null !== $outputFile )
		{
			$this->outputToFile( $jsonResultData, $outputFile );
		}
		else
		{
			$output->writeln( $jsonResultData );
		}

		return static::SUCCESS;
	}
}
