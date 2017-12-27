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
	 * @var array
	 */
	private $d = [];

	/**
	 * @var string
	 */
	private $videoUrl;

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
			if (! is_dir(WHATANIME_DIR)) {
				throw new \Exception("Cannot create directory ".WHATANIME_DIR, 1);
			}
			is_dir(WHATANIME_DIR."/cache") or mkdir(WHATANIME_DIR."/cache");
			if (! is_dir(WHATANIME_DIR."/cache")) {
				throw new \Exception("Cannot create directory ".WHATANIME_DIR."/cache", 1);
			}
			is_dir(WHATANIME_DIR."/cookies") or mkdir(WHATANIME_DIR."/cookies");
			if (! is_dir(WHATANIME_DIR."/cookies")) {
				throw new \Exception("Cannot create directory ".WHATANIME_DIR."/cookies", 1);
			}
			$this->cacheMapFile = WHATANIME_DIR."/cache_map.map";
			if (file_exists($this->cacheMapFile)) {
				$this->cacheMap = json_decode(file_get_contents($this->cacheMapFile), true);
				$this->cacheMap = is_array($this->cacheMap) ? $this->cacheMap : [];
			}
		}

		if (! $isEncoded) {
			$this->hash = sha1($file);
			$this->file = base64_encode($file);
		} else {
			$this->hash = sha1(base64_decode($file));
		}

		$this->cacheFile = WHATANIME_DIR."/cache/".$this->hash;
		$this->cookieFile = WHATANIME_DIR."/cookies/".sha1(date("M"));
	}

	private function isCached()
	{
		if (isset($this->cacheMap[$this->hash]) && ((int)$this->cacheMap[$this->hash]) > time() && file_exists($this->cacheFile)) {
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
				],
				CURLOPT_CONNECTTIMEOUT	=> 600,
				CURLOPT_TIMEOUT			=> 600
			]
		);
		$out = $ch->exec();
		if ($errno = $ch->errno()) {
			throw new \Exception("Curl Error ({$errno}): ".$ch->error(), 1);
		}
		file_put_contents($this->cacheFile, $out);
		$this->cacheMap[$this->hash] = time() + (3600 * 24 * 14);
		file_put_contents($this->cacheMapFile, json_encode($this->cacheMap, JSON_UNESCAPED_SLASHES));
	}

	public function getCache()
	{
		return json_decode(file_get_contents($this->cacheFile), true);
	}

	public function getFirst()
	{
		if ($this->isCached()) {
			$cache = $this->getCache();
			if (isset($cache['docs'][0])) {
				$this->generateVideoUrl($cache['docs'][0]);
				return $cache['docs'][0];
			}
		} else {
			$this->onlineSearch();
			$cache = $this->getCache();
			if (isset($cache['docs'][0])) {
				$this->generateVideoUrl($cache['docs'][0]);
				return $cache['docs'][0];
			}
		}
		return false;
	}

	private function generateVideoUrl($d)
	{
		// https://whatanime.ga/2014-07/PSYCHO-PASS 新編集版/[KTXP][PSYCHO-PASS Extended Edition][02][BIG5][720p][MP4].mp4?start=1061.33&end=1082.58&token=sb2EYw2FCOegH-DBSoSSww
		$this->videoUrl = "https://whatanime.ga/{$d['season']}/{$d['anime']}/{$d['file']}?start={$d['start']}&end={$d['end']}&token={$d['token']}";
		$this->d = $d;
	}

	private function getVideo()
	{
		if (! defined("WHATANIME_VIDEO_URL")) {
			throw new \Exception("WHATANIME_VIDEO_URL must be defined when invoked getVideo method.", 1);
		}
		is_dir(WHATANIME_DIR."/video") or mkdir(WHATANIME_DIR."/video");
		if (! is_dir(WHATANIME_DIR."/video")) {
			throw new \Exception("Cannot create directory ".WHATANIME_DIR."/video", 1);
		}
		$extension = explode(".", $this->d['file']);
		$this->videoFile = WHATANIME_DIR."/video/".($videoFile = $this->hash.strtolower($extension[count($extension) - 1]));
		unset($this->file);
		$ch = new Curl($this->videoUrl);
		$ch->setOpt(
			[
				CURLOPT_COOKIEJAR	=> $this->cookieFile,
				CURLOPT_COOKIEFILE	=> $this->cookieFile,
				CURLOPT_USERAGENT	=> "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:56.0) Gecko/20100101 Firefox/56.0",
				CURLOPT_HTTPHEADER	=> [
					"Accept: video/webm,video/ogg,video/*;q=0.9,application/ogg;q=0.7,audio/*;q=0.6,*/*;q=0.5"
				]
			]
		);
		$handle = fopen($this->videoFile, "w");
		flock($handle, LOCK_EX);
		$data = fwrite($handle, $ch->exec());
		fflush($handle);
		fclose($handle);
		if ($data > 100) {
			return WHATANIME_VIDEO_URL."/".$videoFile;
		} else {
			return false;
		}
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
