<?php

namespace Language\Singleton;

use Exception;

trait TSingleton
{
	private static array $instances = [];

	/**
	 *
	 */
	protected function __construct() { }

	/**
	 * @return void
	 */
	protected function __clone() { }

	/**
	 * @throws Exception
	 */
	public function __wakeup()
	{
		throw new Exception("Cannot unserialize a singleton.");
	}

	/**
	 * @return ISingleton
	 */
	public static function getInstance(): ISingleton
	{
		$cls = static::class;
		if (!isset(self::$instances[$cls])) {
			self::$instances[$cls] = new static();
		}

		return self::$instances[$cls];
	}
}