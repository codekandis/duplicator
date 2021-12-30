<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Cli\Loggers;

use CodeKandis\Duplicator\Environment\Entities\Enumerations\DateTimeFormats;
use CodeKandis\Logging\ConsoleLogger;
use CodeKandis\Logging\LogFileLogger;
use CodeKandis\Logging\LoggerCollection;
use DateTimeImmutable;
use Symfony\Component\Console\Output\ConsoleOutput;
use function dirname;
use function sprintf;

/**
 * Represents the collection of loggers of the application.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
class ApplicationLoggerCollection extends LoggerCollection
{
	/**
	 * Constructor method.
	 */
	public function __construct()
	{
		parent::__construct(
			new ConsoleLogger( new ConsoleOutput() ),
			new LogFileLogger(
				sprintf(
					'%s/logs/%s.log',
					dirname( __DIR__, 3 ),
					( new DateTimeImmutable() )
						->format( DateTimeFormats::DATE_LONG )

				)
			)
		);
	}
}
