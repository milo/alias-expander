<?php

namespace Milo;



/**
 * Tool for a run-time class alias expanding (emulates the ::class from PHP 5.5)
 * and a helper for annotations processing.
 *
 * You can choose one of four licences:
 *
 * @licence  New BSD License
 * @licence  GNU General Public License version 2
 * @licence  GNU General Public License version 3
 * @licence  MIT License
 *
 * @version  $Format:%h$
 * @see  https://github.com/milo/alias-expander
 *
 * @author  Miloslav Hůla (https://github.com/milo)
 */
class AliasExpander
{
	/** @var string  cache dir path */
	private $cacheDir;

	/** @var array  cache of aliases */
	private $cache;

	/** @var bool|int */
	private $checkSeverity = FALSE;

	/** @var bool */
	private $checkAutoload = TRUE;



	/**
	 * Sets cache dir.
	 * @param  string  path to cache directory
	 * @return self
	 */
	public function setCacheDir($dir)
	{
		$dir = $dir . DIRECTORY_SEPARATOR . 'AliasExpander';
		$valid = TRUE;
		if (!is_dir($dir)) {
			set_error_handler(function($severity, $message) use ($dir, &$valid) {
				restore_error_handler();
				return $valid = is_dir($dir);
			});
			mkdir($dir);
			restore_error_handler();
		}

		if ($valid) {
			$this->cacheDir = $dir;
		}
		return $this;
	}



	/**
	 * Check if the class expanded from the alias has been defined.
	 * @param  bool|int  FALSE = off, TRUE = RuntimeException, int = user error level (E_USER_NOTICE, ...)
	 * @param  bool  allow autoload when checking existency
	 * @return self
	 */
	public function setExistsCheck($check, $autoload = TRUE)
	{
		$this->checkSeverity = $check;
		$this->checkAutoload = (bool) $autoload;
		return $this;
	}



	/**
	 * Expands class alias in a context where this method is called.
	 * @param  string  class alias
	 * @param  int  how deep is wrapped this method call
	 * @return string fully qualified class name
	 * @throws \RuntimeException  when origin of call cannot be found in backtrace
	 * @throws \LogicException  when empty alias name passed
	 */
	public function expand($alias, $depth = 0)
	{
		$bt = PHP_VERSION_ID < 50400
			? debug_backtrace(FALSE)
			: debug_backtrace(FALSE, $depth + 1);

		if (!isset($bt[$depth]['file'], $bt[$depth]['line'])) {
			throw new \RuntimeException('Cannot find an origin of call in backtrace.');
		}

		return $this->expandExplicit($alias, $bt[$depth]['file'], $bt[$depth]['line']);
	}



	/**
	 * Expands class alias in a file:line context.
	 * @param  string  class alias
	 * @param  string  file path
	 * @param  int  line number
	 * @return string  fully qualified class name
	 * @throws \LogicException  when empty class alias name passed
	 */
	public function expandExplicit($name, $file, $line = 0)
	{
		if (empty($name)) {
			throw new \LogicException('Alias name must not be empty.');
		}

		if ($name[0] === '\\') { // already fully qualified
			$return = ltrim($name, '\\');

		} else {
			if (($pos = strpos($name, '\\')) === FALSE) {
				$lower = strtolower($name);
				$suffix = '';
			} else {
				$lower = strtolower(substr($name, 0, $pos));
				$suffix = substr($name, $pos);
			}

			if (($namespaces = $this->load($file)) === NULL) {
				$namespaces = $this->parse(file_get_contents($file));
				$this->store($file, $namespaces);
			}

			$next = each($namespaces);
			do {
				list(, $uses) = $next;
				$next = each($namespaces);
			} while ($next && $next[0] < $line);

			if (isset($uses['aliases'][$lower]) && $uses['aliases'][$lower]['line'] < $line) {
				$return = $uses['aliases'][$lower]['class'] . $suffix;
			} else {
				$return = $uses['namespace'] === '' ? $name : $uses['namespace'] . '\\' . $name;
			}
		}

		if (!empty($this->checkSeverity) && !class_exists($return, $this->checkAutoload)) {
			$message = "Class $return not found";
			if (is_int($this->checkSeverity)) {
				trigger_error($message, $this->checkSeverity);
			} else {
				throw new \RuntimeException($message);
			}
		}

		return $return;
	}



	/**
	 * Parses PHP code and searches for namespaces and class aliases.
	 * <code>
	 * Return value:
	 *
	 * array(
	 *     line => array(
	 *         'namespace' => string,
	 *         'aliases' => array(
	 *             alias => array(
	 *                 'line' => int,
	 *                 'class' => string,
	 *             )
	 *         )
	 *     )
	 * )
	 * </code>
	 * @param  string  PHP code
	 * @return array
	 */
	final protected function parse($code)
	{
		$tokens = @token_get_all($code); // @ - suppress useless warnings

		$careTraits = PHP_VERSION_ID >= 50400;

		$namespaces = array(
			0 => array(
				'namespace' => '',
				'aliases' => array(),
			),
		);

		$current = & $namespaces[0];

		$namespace = $line = $class = $alias = $blockCounter = NULL;
		foreach ($tokens as $token) {
			if ($token[0] === T_WHITESPACE) {
				// speed-up

			} elseif ($token[0] === T_NAMESPACE) {
				$namespace = '';
				$line = $token[2];

			} elseif ($namespace !== NULL && ($token[0] === T_STRING || $token[0] === T_NS_SEPARATOR)) {
				$namespace .= $token[1];

			} elseif ($namespace !== NULL && ($token === ';' || $token === '{')) {
				$namespaces[$line] = array(
					'namespace' => $namespace,
					'aliases' => array(),
				);
				$current = & $namespaces[$line];
				$namespace = NULL;

			} elseif ($careTraits && ($token[0] === T_CLASS || $token[0] === T_TRAIT)) {
				$blockCounter = 0;

			} elseif ($blockCounter !== NULL) {
				if ($token === '{') {
					$blockCounter++;

				} elseif ($token === '}') {
					$blockCounter--;
					if ($blockCounter === 0) {
						$blockCounter = NULL;
					}
				}

				// skipping Class/Trait body there

			} elseif ($token[0] === T_USE) {
				$alias = '';
				$line = $token[2];

			} elseif ($alias !== NULL) {
				if ($token === '(' || $token[0] === T_FUNCTION || $token[0] === T_CONST) { // Cases of 'function() use() {}', or 'use function ...', or 'use const ...'
					$class = $alias = NULL;

				} elseif ($token[0] === T_STRING || $token[0] === T_NS_SEPARATOR) {
					$alias .= $token[1];

				} elseif ($token[0] === T_AS) {
					$class = $alias;
					$alias = '';

				} elseif ($token === ';' || $token === ',') {
					if ($class === NULL) {
						$class = $alias;
						$tmp = explode('\\', $alias);
						$alias = end($tmp);
					}
					$current['aliases'][strtolower($alias)] = array('line' => $line, 'class' => ltrim($class, '\\'));
					$class = NULL;
					$alias = $token === ';' ? NULL : '';
				}
			}
		}

		return $namespaces;
	}




	/**
	 * Loads data from cache.
	 * @param  string
	 * @return mixed
	 */
	protected function load($file)
	{
		if (isset($this->cache[$file])) {
			return $this->cache[$file];
		}

		if ($this->cacheDir !== NULL
			&& is_file($cacheFile = $this->cacheFileFor($file))
			&& filemtime($file) < filemtime($cacheFile)
		) {
			if (($fd = fopen($cacheFile, 'r')) === FALSE || flock($fd, LOCK_SH) === FALSE) {
				return NULL;
			}
			$cached = require $cacheFile;
			flock($fd, LOCK_UN);
			fclose($fd);

			return $this->cache[$file] = $cached;
		}

		return NULL;
	}



	/**
	 * Store data to cache.
	 * @param  string  path to parsed PHP file
	 * @param  mixed
	 * @return self
	 */
	protected function store($file, $data)
	{
		$this->cache[$file] = $data;

		if ($this->cacheDir !== NULL) {
			file_put_contents(
				$this->cacheFileFor($file),
				"<?php // AliasExpander cache for $file\n\nreturn " . var_export($data, TRUE) . ";\n",
				LOCK_EX
			);
		}

		return $this;
	}



	/**
	 * @param  string  path to parsed PHP file
	 * @return string
	 */
	private function cacheFileFor($file)
	{
		return $this->cacheDir
			. DIRECTORY_SEPARATOR
			. substr(sha1($file), 0, 5) . '-' . pathinfo($file, PATHINFO_FILENAME) . '.php';
	}

}



/**
 * A wrapper for the Milo\AliasExpander.
 *
 * @author  Miloslav Hůla (https://github.com/milo)
 */
class Alias
{
	/** @var AliasExpander */
	private static $instance;



	/**
	 * @throws \LogicException  when instantized
	 */
	final public function __construct()
	{
		throw new \LogicException('This is a static class and cannot be instantized.');
	}



	/**
	 * @return AliasExpander
	 */
	public static function getExpander()
	{
		if (self::$instance === NULL) {
			self::$instance = new AliasExpander;
		}
		return self::$instance;
	}



	/**
	 * {@link AliasExpander::expand()}
	 */
	public static function expand($alias, $depth = 0)
	{
		return self::getExpander()->expand($alias, $depth + 1);
	}



	/**
	 * {@link AliasExpander::expandExplicit()}
	 */
	public static function expandExplicit($alias, $file, $line = 0)
	{
		return self::getExpander()->expandExplicit($alias, $file, $line);
	}

}
