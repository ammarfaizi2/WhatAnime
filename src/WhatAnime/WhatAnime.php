<?php

namespace WhatAnime;

use Curl\Curl;

class WhatAnime
{
	/**
	 * @var string
	 */
	private $file;

	/**
	 * @var string
	 */
	private $cache;

	/**
	 * @var string
	 */
	private $cacheMapFile;

	/**
	 * @var array
	 */
	private $cacheMap = [];

	/**
	 * @var string
	 */
	private $cacheFile;

	/**
	 * @var string
	 */
	private $hash;

	/**
	 * @param string $file
	 * @param bool	 $isEncoded
	 */
	public function __construct($file, $isEncoded = false)
	{
		if (! defined("WHATANIME_DIR")) {
			throw new \Exception("WHATANIME_DIR is not defined!", 1);
		} else {
			is_dir(WHATANIME_DIR) or mkdir(WHATANIME_DIR);
			is_dir(WHATANIME_DIR."/cache") or mkdir(WHATANIME_DIR."/cache");
			is_dir(WHATANIME_DIR."/cookies") or mkdir(WHATANIME_DIR."/cookies");
			$this->cacheMapFile = WHATANIME_DIR."/cache_map.map";
			if (file_exists($this->cacheMapFile)) {
				$this->cacheMap = json_decode(file_get_contents($this->cacheMapFile), true);
				$this->cacheMap = is_array($this->cacheMapFile) ? $this->cacheMapFile : [];
			}
		}

		if (! $isEncoded) {
			$this->hash = sha1($file);
			$this->file = base64_encode($file);
		} else {
			$this->hash = sha1(base64_decode($file));
		}

		$this->cacheFile = WHATANIME_DIR."/cache/".$this->hash;
		$this->cookieFile = WHATANIME_DIR."/cookies/".date("M");
	}

	private function isCached()
	{
		if (isset($this->cacheMap[$this->hash]) && $this->cacheMap[$this->hash] > time() && file_exists($this->cacheFile)) {
			return true;
		} else {
			return false;
		}
	}

	private function onlineSearch()
	{
		$ch = new Curl("https://whatanime.ga/search");
		$context = http_build_query(["data" => "data:image/jpeg;base64,".$this->file]);
		$ch->setOpt(
			[
				CURLOPT_COOKIEJAR	=> $this->cookieFile,
				CURLOPT_COOKIEFILE	=> $this->cookieFile,
				CURLOPT_POST		=> true,
				CURLOPT_POSTFIELDS	=> $context,
				CURLOPT_REFERER		=> "https://whatanime.ga/",
				CURLOPT_USERAGENT	=> "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:56.0) Gecko/20100101 Firefox/56.0",
				CURLOPT_HTTPHEADER	=> [
					"Host: whatanime.ga",
					"X-Requested-With: XMLHttpRequest",
					"Content-Length: ".strlen($context),
					"Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
					"Accept: application/json, text/javascript, */*; q=0.01"
				]
			]
		);
		$out = $ch->exec();
		if ($errno = $ch->errno()) {
			throw new \Exception("Curl Error ({$errno}): ".$ch->error(), 1);
		}
		file_put_contents($this->cacheFile, $out);
	}

	public function getCache()
	{
		return json_decode(file_get_contents($this->cacheFile), true);
	}

	public function exec()
	{
		if ($this->isCached()) {
			return $this->getCache();
		} else {
			$this->onlineSearch();
			return $this->getCache();
		}
	}
}