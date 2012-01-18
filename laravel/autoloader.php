<?php namespace Laravel; defined('APP_PATH') or die('No direct script access.');

class Autoloader {

	/**
	 * The mappings from class names to file paths.
	 *
	 * @var array
	 */
	public static $mappings = array();

	/**
	 * All of the class aliases registered with the auto-loader.
	 *
	 * @var array
	 */
	public static $aliases = array();

	/**
	 * The directories that use the PSR-0 naming convention.
	 *
	 * @var array
	 */
	public static $psr = array();

	/**
	 * The mappings for namespaces to directories.
	 *
	 * @var array
	 */
	public static $namespaces = array();

	/**
	 * Load the file corresponding to a given class.
	 *
	 * This method is registerd in the bootstrap file as an SPL auto-loader.
	 *
	 * @param  string  $class
	 * @return void
	 */
	public static function load($class)
	{
		// First, we will check to see if the class has been aliased. If it has,
		// we will register the alias, which may cause the auto-loader to be
		// called again for the "real" class name.
		if (isset(static::$aliases[$class]))
		{
			class_alias(static::$aliases[$class], $class);
		}

		// All classes in Laravel are staticly mapped. There is no crazy search
		// routine that digs through directories. It's just a simple array of
		// class to file path maps for ultra-fast file loading.
		elseif (isset(static::$mappings[$class]))
		{
			require static::$mappings[$class];
		}

		// If the class is namespaced to an existing bundle and the bundle has
		// not been started, we will start the bundle and attempt to load the
		// class file again. If that fails, an error will be thrown by PHP.
		//
		// This allows bundle classes to be loaded by the auto-loader before
		// their class mappings have actually been registered; however, it
		// is up to the bundle developer to namespace their classes to
		// match the name of their bundle.
		elseif (($slash = strpos($class, '\\')) !== false)
		{
			$namespace = substr($class, 0, $slash);

			// If the class namespace is mapped to a directory, we will load
			// the class using the PSR-0 standards from that directory by
			// passing the directory into the "load_psr" method.
			if (isset(static::$namespaces[$namespace]))
			{
				$directory = static::$namespaces[$namespace];

				return static::load_psr(substr($class, $slash + 1), $directory);
			}

			// It's very important that we make sure the bundle has not been
			// started here. If we don't, we'll end up in an infinite loop
			// attempting to load a bundle's class.
			if (Bundle::exists($namespace) and ! Bundle::started($namespace))
			{
				Bundle::start($namespace);

				static::load($class);
			}
		}

		static::load_psr($class);
	}

	/**
	 * Attempt to resolve a class using the PSR-0 standard.
	 *
	 * @param  string  $class
	 * @param  string  $directory
	 * @return void
	 */
	protected static function load_psr($class, $directory = null)
	{
		// The PSR-0 standard indicates that class namespace slashes or
		// underscores should be used to indicate the directory tree in
		// which the class resides.
		$file = str_replace(array('\\', '_'), '/', $class);

		$directories = (is_nulL($directory)) ? static::$psr : array($directory);

		// Once we have formatted the class name, we will simply spin
		// through the registered PSR-0 directories and attempt to
		// locate and load the class into the script.
		foreach ($directories as $directory)
		{
			if (file_exists($path = $directory.strtolower($file).EXT))
			{
				return require $path;
			}
			elseif (file_exists($path = $directory.$file.EXT))
			{
				return require $path;
			}
		}
	}

	/**
	 * Register an array of class to path mappings.
	 *
	 * <code>
	 *		// Register a class mapping with the Autoloader
	 *		Autoloader::map(array('User' => APP_PATH.'models/user.php'));
	 * </code>
	 *
	 * @param  array  $mappings
	 * @return void
	 */
	public static function map($mappings)
	{
		static::$mappings = array_merge(static::$mappings, $mappings);
	}

	/**
	 * Register a class alias with the auto-loader.
	 *
	 * @param  string  $class
	 * @param  string  $alias
	 * @return void
	 */
	public static function alias($class, $alias)
	{
		static::$aliases[$alias] = $class;
	}

	/**
	 * Register directories to be searched as a PSR-0 library.
	 *
	 * @param  string|array  $directory
	 * @return void
	 */
	public static function psr($directory)
	{
		$directories = array_map(function($directory)
		{
			return rtrim($directory, '/').'/';

		}, (array) $directory);

		static::$psr = array_unique(array_merge(static::$psr, $directories));
	}

	/**
	 * Map namespaces to directories.
	 *
	 * @param  string  $namespace
	 * @param  string  $path
	 */
	public static function namespaces($mappings)
	{
		static::$namespaces = array_merge(static::$namespaces, $mappings);
	}

}