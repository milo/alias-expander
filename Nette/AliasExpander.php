<?php

namespace Milo\Nette;

use Milo,
	Nette\Caching\Cache,
	Nette\Caching\IStorage;



/**
 * AliasExpander with Nette (http://nette.org) cache implementation.
 *
 * You can choose one of four licences:
 *
 * @licence New BSD License
 * @licence GNU General Public License version 2
 * @licence GNU General Public License version 3
 * @licence MIT
 *
 * @see https://github.com/milo/alias-expander
 *
 * @author  Miloslav Hůla (https://github.com/milo)
 */
class AliasExpander extends Milo\AliasExpander
{
	/** @var Cache */
	private $cache;



	public function __construct(IStorage $storage)
	{
		$this->cache = new Cache($storage, str_replace('\\', '.', get_class($this)));
	}



	/**
	 * Loads data from cache.
	 * @param  string
	 * @return mixed
	 */
	protected function load($file)
	{
		return $this->cache->load($file);
	}



	/**
	 * Store data to cache.
	 * @param  string  path to parsed PHP file
	 * @param  mixed
	 * @return self
	 */
	protected function store($file, $data)
	{
		$this->cache->save($file, $data, array(Cache::FILES => $file));
		return $this;
	}

}
