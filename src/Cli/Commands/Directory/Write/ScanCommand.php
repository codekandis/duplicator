<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Cli\Commands\Directory\Write;

use CodeKandis\Duplicator\Cli\Commands\AbstractCommand;
use CodeKandis\Duplicator\Environment\Entities\DirectoryListingEntityInterface;
use CodeKandis\Duplicator\Environment\Entities\FileEntryEntityInterface;
use CodeKandis\Duplicator\Environment\Io\DirectoryNotFoundException;
use CodeKandis\Duplicator\Environment\Io\DirectoryNotReadableException;
use CodeKandis\Duplicator\Environment\Io\DirectoryNotWritableException;
use CodeKandis\Duplicator\Environment\Io\DirectoryScanner;
use CodeKandis\Duplicator\Environment\Io\FileNotCreatableException;
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
use function fclose;
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
 * Represents the command to scan a directory.
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
	 * Represents the progress bar format definition of `processed file`.
	 * @var string
	 */
	protected const PROGRESS_BAR_FORMAT_DEFINITION_PROCESSED_FILE = "Scanning %s:\n%%filename%%\n%%current%%/%%max%% [%%bar%%] %%percent%%%%\n%%elapsed%%/%%estimated%% %%memory%%\n";

	/**
	 * Represents the QUESTION if an existing output file should be overwritten.
	 * @var string
	 */
	protected const QUESTION_OVERWRITE_OUTPUT_FILE = 'The output file `%s` already exists. Overwrite? [y/N]: ';

	/**
	 * {@inheritDoc}
	 */
	protected const COMMAND_NAME = 'directory:scan';

	/**
	 * {@inheritDoc}
	 */
	protected const COMMAND_DESCRIPTION = 'Scans a directory for all files.';

	/**
	 * Represents the command option `output-file`.
	 * @var string
	 */
	protected const COMMAND_OPTION_OUTPUT_FILE = 'output-file';

	/**
	 * Represents the command argument `directory`.
	 * @var string
	 */
	protected const COMMAND_ARGUMENT_DIRECTORY = 'directory';

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
			'name'        => self::COMMAND_ARGUMENT_DIRECTORY,
			'mode'        => InputArgument::REQUIRED,
			'description' => 'The directory to scan.'
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
								static::QUESTION_OVERWRITE_OUTPUT_FILE,
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
	 * @param string $directory The directory to scan.
	 * @throws DirectoryNotFoundException The directory to scan does not exist.
	 * @throws DirectoryNotReadableException The directory to scan is not readable.
	 */
	public function validateArguments( string $directory ): void
	{
		if ( false === is_dir( $directory ) )
		{
			throw new DirectoryNotFoundException(
				sprintf(
					static::ERROR_DIRECTORY_NOT_FOUND,
					$directory
				)
			);
		}
		if ( false === is_readable( $directory ) )
		{
			throw new DirectoryNotReadableException(
				sprintf(
					static::ERROR_DIRECTORY_NOT_READABLE,
					$directory
				)
			);
		}
	}

	/**
	 * Updates the progress maximum.
	 * @param ProgressBar $progressBar The progress bar to update.
	 * @param int $progressMaximum The progress maximum.
	 */
	private function updateProgressMaximum( ProgressBar $progressBar, int $progressMaximum ): void
	{
		$progressBar->setMaxSteps( $progressMaximum );
	}

	/**
	 * Updates the current progress.
	 * @param ProgressBar $progressBar The progress bar to update.
	 * @param ?FileEntryEntityInterface $currentFileEntry The current processed file.
	 * @param int $currentProgress The current progress.
	 */
	private function updateCurrentProgress( ProgressBar $progressBar, ?FileEntryEntityInterface $currentFileEntry, int $currentProgress ): void
	{
		$progressBar->setMessage(
			null === $currentFileEntry
				? ''
				: $currentFileEntry->getRelativePath(),
			'filename'
		);
		$progressBar->setProgress( $currentProgress );
	}

	/**
	 * Creates a progress bar.
	 * @param OutputInterface $output The output to use for the progress bar.
	 * @param string $directory The directory to scan.
	 * @return ProgressBar The created progress bar.
	 */
	private function createProgressBar( OutputInterface $output, string $directory ): ProgressBar
	{
		ProgressBar::setFormatDefinition(
			'processedFile',
			sprintf(
				static::PROGRESS_BAR_FORMAT_DEFINITION_PROCESSED_FILE,
				$directory
			)
		);

		$progressBar = $output instanceof ConsoleOutputInterface
			? new ProgressBar( $output->section() )
			: new ProgressBar( $output );
		$progressBar->setFormat( 'processedFile' );
		$progressBar->setMessage( '', 'filename' );

		return $progressBar;
	}

	/**
	 * Scans a directory.
	 * @param ProgressBar $progressBar The progress bar to display the progress.
	 * @param string $path The path of the directory.
	 * @return DirectoryListingEntityInterface The directory listing.
	 * @throws ReflectionException An error occured during the creation of a file entry.
	 * @throws ReflectionException An error occured during the creation of a directory listing.
	 */
	private function scanDirectory( ProgressBar $progressBar, string $path ): DirectoryListingEntityInterface
	{
		$directoryScanner = new DirectoryScanner( $path );
		$directoryScanner->addProgressMaximumCountedEventHandler(
			function ( int $progressMaximum ) use ( $progressBar ): void
			{
				$this->updateProgressMaximum( $progressBar, $progressMaximum );
			}
		);
		$directoryScanner->addProgressChangedEventHandler(
			function ( ?FileEntryEntityInterface $currentFileEntry, int $currentProgress ) use ( $progressBar ): void
			{
				$this->updateCurrentProgress( $progressBar, $currentFileEntry, $currentProgress );
			}
		);

		$directoryListing = $directoryScanner->scan();
		$progressBar->setMessage( 'Done.', 'filename' );
		$progressBar->setProgress( $progressBar->getProgress() );

		return $directoryListing;
	}

	/**
	 * Gets the JSON result data of the scanned file entries.
	 * @param DirectoryListingEntityInterface $directdoryListing The directory listing.
	 * @return string The JSON result data.
	 * @throws JsonException An error occured during JSON encoding.
	 */
	private function getJsonResultData( DirectoryListingEntityInterface $directdoryListing ): string
	{
		return ( new JsonEncoder() )
			->encode(
				$directdoryListing,
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
	 * @throws DirectoryNotFoundException The directory to scan does not exist.
	 * @throws DirectoryNotReadableException The directory to scan is not readable.
	 * @throws FileNotCreatableException The output file is not createable.
	 * @throws ReflectionException An error occured during the creation of a file entry.
	 * @throws ReflectionException An error occured during the creation of a directory listing.
	 * @throws JsonException An error occured during JSON encoding.
	 */
	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$outputFile = $input->getOption( static::COMMAND_OPTION_OUTPUT_FILE );
		if ( false === $this->validateOptions( $input, $output, $outputFile ) )
		{
			return static::FAILURE;
		}

		$directory = $input->getArgument( static::COMMAND_ARGUMENT_DIRECTORY );
		$this->validateArguments( $directory );

		$fileEntries = $this->scanDirectory(
			$this->createProgressBar( $output, $directory ),
			$directory
		);

		$jsonResultData = $this->getJsonResultData( $fileEntries );

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
