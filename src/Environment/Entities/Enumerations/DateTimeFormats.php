<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Environment\Entities\Enumerations;

/**
 * Represents an enumeration of date time formats.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
abstract class DateTimeFormats
{
	/**
	 * Represents the long date time format.
	 * @var string
	 */
	public const DATETIME_LONG = 'Y-m-d H:i:s.u';

	/**
	 * Represents the short date time format.
	 * @var string
	 */
	public const DATETIME_SHORT = 'Y-m-d H:i:s';

	/**
	 * Represents the long filename date time format.
	 * @var string
	 */
	public const DATETIME_LONG_FILENAME = 'Y-m-d-H-i-s-u';

	/**
	 * Represents the long date format.
	 * @var string
	 */
	public const DATE_LONG = 'Y-m-d';

	/**
	 * Represents the german long date format.
	 * @var string
	 */
	public const DATE_LONG_GERMAN = 'd.m.Y';
}
