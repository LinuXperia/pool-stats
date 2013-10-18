<?php
/*

	Copyright 2013 Jindrich Dolezy (dzindra)

	Licensed under the Apache License, Version 2.0 (the "License");
	you may not use this file except in compliance with the License.
	You may obtain a copy of the License at

		http://www.apache.org/licenses/LICENSE-2.0

	Unless required by applicable law or agreed to in writing, software
	distributed under the License is distributed on an "AS IS" BASIS,
	WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
	See the License for the specific language governing permissions and
	limitations under the License.

*/

class PoolBtcGuild implements PoolSource {
	public static $loginUrl = "https://www.btcguild.com/index.php?page=login2";
	public static $pplnsUrl = "https://www.btcguild.com/btcguild_pplns_earnings.csv";
	public static $noLoginMessage = "CSV export not available unless you are logged in.";
	public static $powerUrl = "https://www.btcguild.com/api.php?api_key=";
	public static $pplnsMatureBlockCount = 10;
	public static $pplnsBlockLimit = 30;

	protected $apiKey;
	protected $username;
	protected $password;

	public $cookieJar;


	public function __construct(array $config) {
		if (empty($config['type']) || $config['type'] != self::type())
			throw new PoolStatException("Type mismatch ($config[type])");

		if (empty($config['apiKey']))
			throw new PoolStatException("Missing apiKey");

		if (empty($config['user']))
			throw new PoolStatException("Missing user name");

		if (empty($config['password']))
			throw new PoolStatException("Missing password");

		if (empty($config['id']))
			throw new PoolStatException("Missing id");

		$this->id = $config['id'];
		$this->apiKey = $config['apiKey'];
		$this->username = $config['user'];
		$this->password = $config['password'];

		$this->cookieJar = dirname(__FILE__) . "/../data/.htcookie." . $this->username;
	}


	protected function login() {
		$ch = curl_init(self::$loginUrl);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array('username' => $this->username, 'password' => $this->password));
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieJar);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result !== false;
	}


	protected function loadPplns() {
		$ch = curl_init(self::$pplnsUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieJar);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}


	protected function loadPower() {
		$file = @file_get_contents(self::$powerUrl . $this->apiKey);
		if (!$file)
			throw new PoolStatException("Http fetch failed", 1);

		$json = @json_decode($file, true);
		if (!$json)
			throw new PoolStatException("Json decode failed", 2);

		return isset($json['pool']['pool_speed']) ? $json['pool']['pool_speed'] : 0;
	}


	function parseTime($input) {
		if (!preg_match("/^\"([0-9]{4})-0?([0-9]{1,2})-0?([0-9]{1,2}) 0?([0-9]{1,2}):0?([0-9]{1,2}):0?([0-9]{1,2})\"$/", $input, $time))
			throw new PoolStatException("Unable to parse time ($input)");

		$time = mktime($time[4], $time[5], $time[6], $time[2], $time[3], $time[1]);
		return $time;
	}


	protected function fetchLines() {
		$csv = $this->loadPplns();
		if ($csv == self::$noLoginMessage) {
			$this->login();
			$csv = $this->loadPplns();
		}

		if ($csv === false)
			throw new PoolStatException("Unable to fetch data", 1);

		if ($csv == self::$noLoginMessage)
			throw new PoolStatException("Not logged in", 2);

		$lines = explode("\n", $csv);
		array_shift($lines);
		return array_reverse($lines);
	}


	/**
	 *
	 * @param bool $firstRun
	 * @throws PoolStatException
	 * @return array of blocks
	 */
	public function fetch($firstRun = false) {
		$power = $this->loadPower();
		$lines = $this->fetchLines();

		$current = 0;

		$blocks = array();
		foreach ($lines as $line) {
			$items = explode(',', trim($line));

			$block = new Block($this->id);
			$block->blockId = $items[1];
			$block->timeStarted = $this->parseTime($items[0]);
			$block->timeEnded = $this->parseTime($items[0]);
			$block->reward = $items[3];
			$block->mature = $current >= self::$pplnsMatureBlockCount ? 1 : 0;
			$block->power = $power;
			$blocks[] = $block;

			$current++;
			if ($current >= self::$pplnsBlockLimit && !$firstRun)
				break;
		}
		return $blocks;
	}


	/**
	 * @return string
	 */
	public static function type() {
		return "btcguild.com";
	}


	public function getId() {
		return $this->id;
	}


}

$env->addPool('PoolBtcGuild');