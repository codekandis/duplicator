<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Cli\Commands\Directories\Write;

use CodeKandis\Duplicator\Cli\Commands\AbstractCommand;
use CodeKandis\Duplicator\Environment\Entities\DuplicateFileEntryEntity;
use CodeKandis\Duplicator\Environment\Entities\DuplicateFileEntryEntityCollection;
use CodeKandis\Duplicator\Environment\Entities\DuplicateFileEntryEntityCollectionInterface;
use CodeKandis\Duplicator\Environment\Entities\FileEntryEntityCollectionInterface;
use CodeKandis\Duplicator\Environment\Io\DirectoryNotFoundException;
use CodeKandis\Duplicator\Environment\Io\DirectoryNotReadableException;
use CodeKandis\Duplicator\Environment\Io\DirectoryNotWritableException;
use CodeKandis\Duplicator\Environment\Io\DirectoryScanner;
use CodeKandis\Duplicator\Environment\Io\FileNotCreatableException;
use CodeKandis\JsonCodec\JsonEncoder;
use CodeKandis\JsonCodec\JsonEncoderOptions;
use JsonException;
use ReflectionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function fclose;
use function fopen;
use function fputs;
use function is_dir;
use function is_readable;
use function is_writable;
use function pathinfo;
use function realpath;
use function sprintf;

/**
 * Represents the command to scan directories for duplicates.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
class ScanCommand extends AbstractCommand
{
	/**
	 * Represents the error message if a directory does not exist.
	 * @var string
	 */
	protected const ERROR_DIRECTORY_NOT_FOUND = 'The directory `%s` does not exist.';

	/**
	 * Represents the error message if a directory is not readable.
	 * @var string
	 */
	protected const ERROR_DIRECTORY_NOT_READABLE = 'The directory `%s` is not readable.';

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
	 * {@inheritDoc}
	 */
	protected const COMMAND_NAME = 'directories:scan';

	/**
	 * {@inheritDoc}
	 */
	protected const COMMAND_DESCRIPTION = 'Scans two directories for duplicates.';

	/**
	 * Represents the command option `output-file`.
	 * @var string
	 */
	protected const COMMAND_OPTION_OUTPUT_FILE = 'output-file';

	/**
	 * Represents the command argument `target-directory`.
	 * @var string
	 */
	protected const COMMAND_ARGUMENT_TARGET_DIRECTORY = 'target-directory';

	/**
	 * Represents the command argument `merge-directory`.
	 * @var string
	 */
	protected const COMMAND_ARGUMENT_MERGE_DIRECTORY = 'merge-directory';

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
			'name'        => self::COMMAND_ARGUMENT_TARGET_DIRECTORY,
			'mode'        => InputArgument::REQUIRED,
			'description' => 'The origin directory to merge into.'
		],
		[
			'name'        => self::COMMAND_ARGUMENT_MERGE_DIRECTORY,
			'mode'        => InputArgument::REQUIRED,
			'description' => 'The directory to get merged into the target directory.'
		]
	];

	/**
	 * Validates the command options.
	 * @param ?string $outputFile The path of the output file.
	 * @throws DirectoryNotFoundException The output file directory does not exist.
	 * @throws DirectoryNotWritableException The output file directory is not writable.
	 */
	public function validateOptions( ?string $outputFile ): void
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
		}
	}

	/**
	 * Validates the command arguments.
	 * @param string $targetDirectory The target directory.
	 * @param string $mergeDirectory The merge directory.
	 * @throws DirectoryNotFoundException The target directory does not exist.
	 * @throws DirectoryNotReadableException The target directory is not readable.
	 * @throws DirectoryNotFoundException The merge directory does not exist.
	 * @throws DirectoryNotReadableException The merge directory is not readable.
	 */
	public function validateArguments( string $targetDirectory, string $mergeDirectory ): void
	{
		if ( false === is_dir( $targetDirectory ) )
		{
			throw new DirectoryNotFoundException(
				sprintf(
					static::ERROR_DIRECTORY_NOT_FOUND,
					$targetDirectory
				)
			);
		}
		if ( false === is_readable( $targetDirectory ) )
		{
			throw new DirectoryNotReadableException(
				sprintf(
					static::ERROR_DIRECTORY_NOT_READABLE,
					$targetDirectory
				)
			);
		}

		if ( false === is_dir( $mergeDirectory ) )
		{
			throw new DirectoryNotFoundException(
				sprintf(
					static::ERROR_DIRECTORY_NOT_FOUND,
					$mergeDirectory
				)
			);
		}
		if ( false === is_readable( $mergeDirectory ) )
		{
			throw new DirectoryNotReadableException(
				sprintf(
					static::ERROR_DIRECTORY_NOT_READABLE,
					$mergeDirectory
				)
			);
		}
	}

	/**
	 * Scans a directory.
	 * @param string $path The path of the directory.
	 * @return FileEntryEntityCollectionInterface The scanned file entries.
	 * @throws ReflectionException An error occured during the creation of a file entry.
	 */
	private function scanDirectory( string $path ): FileEntryEntityCollectionInterface
	{
		return ( new DirectoryScanner( $path ) )
			->scan();
	}

	/**
	 * Determines all duplicate file entries.
	 * @param FileEntryEntityCollectionInterface $targetFileEntries The target file entries.
	 * @param FileEntryEntityCollectionInterface $mergeFileEntries The merge file entries.
	 * @return DuplicateFileEntryEntityCollectionInterface The duplicate file entries.
	 * @throws ReflectionException An error occured during the creation of a duplicate file entry.
	 */
	private function determineDuplicateFileEntries( FileEntryEntityCollectionInterface $targetFileEntries, FileEntryEntityCollectionInterface $mergeFileEntries ): DuplicateFileEntryEntityCollectionInterface
	{
		$duplicateFileEntries = [];
		foreach ( $targetFileEntries as $targetFileEntry )
		{
			$mergeFileEntry = $mergeFileEntries->findByRelativePath(
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
	 * Gets the JSON result data.
	 * @param DuplicateFileEntryEntityCollectionInterface $duplicateFileEntries
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
	 * @throws DirectoryNotFoundException The target directory does not exist.
	 * @throws DirectoryNotReadableException The target directory is not readable.
	 * @throws DirectoryNotFoundException The merge directory does not exist.
	 * @throws DirectoryNotReadableException The merge directory is not readable.
	 * @throws FileNotCreatableException The output file is not createable.
	 * @throws ReflectionException An error occured during the creation of a file entry.
	 * @throws ReflectionException An error occured during the creation of a duplicate file entry.
	 * @throws JsonException An error occured during JSON encoding.
	 */
	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$outputFile = $input->getOption( static::COMMAND_OPTION_OUTPUT_FILE );
		$this->validateOptions( $outputFile );

		$targetDirectory = $input->getArgument( static::COMMAND_ARGUMENT_TARGET_DIRECTORY );
		$mergeDirectory  = $input->getArgument( static::COMMAND_ARGUMENT_MERGE_DIRECTORY );
		$this->validateArguments( $targetDirectory, $mergeDirectory );

		$targetFileEntries = $this->scanDirectory( $targetDirectory );
		$mergeFileEntries  = $this->scanDirectory( $mergeDirectory );

		$duplicateFileEntries = $this->determineDuplicateFileEntries( $targetFileEntries, $mergeFileEntries );
		$jsonResultData       = $this->getJsonResultData( $duplicateFileEntries );

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
