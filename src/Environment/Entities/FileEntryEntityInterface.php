<?php declare( strict_types = 1 );
namespace CodeKandis\Duplicator\Environment\Entities;

use CodeKandis\Entities\EntityInterface;

/**
 * Represents the interface of any file entry entity.
 * @package codekandis/duplicator
 * @author Christian Ramelow <info@codekandis.net>
 */
interface FileEntryEntityInterface extends EntityInterface
{
	/**
	 * Gets the root path of the file.
	 * @return string The root path of the file.
	 */
	public function getRootPath(): string;

	/**
	 * Sets the root path of the file.
	 * @param string $rootPath The root path of the file.
	 */
	public function setRootPath( string $rootPath ): void;

	/**
	 * Gets the path of the file.
	 * @return string The path of the file.
	 */
	public function getPath(): string;

	/**
	 * Sets the path of the file.
	 * @param string $path The path of the file.
	 */
	public function setPath( string $path ): void;

	/**
	 * Gets the relative path of the file.
	 * @return string The relative path of the file.
	 */
	public function getRelativePath(): string;

	/**
	 * sets the relative path of the file.
	 * @param string $relativePath The relative path of the file.
	 */
	public function setRelativePath( string $relativePath ): void;

	/**
	 * Gets the size of the file.
	 * @return int The size of the file.
	 */
	public function getSize(): int;

	/**
	 * Sets the size of the file.
	 * @param int $size The size of the file.
	 */
	public function setSize( int $size ): void;

	/**
	 * Gets the MD5 checksum of the file.
	 * @return string The MD5 checksum of the file.
	 */
	public function getMd5Checksum(): string;

	/**
	 * Sets the MD5 checksum of the file.
	 * @param string $md5Checksum The MD5 checksum of the file.
	 */
	public function setMd5Checksum( string $md5Checksum ): void;
}
