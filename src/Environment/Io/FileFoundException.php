<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Environment\Io;

use RuntimeException;

/**
 * Represents an exception thrown if a file does exist.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
class FileFoundException extends RuntimeException
{
}
