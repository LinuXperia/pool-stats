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

class PoolSlush implements PoolSource {
	public static $apiUrl = 'https://mining.bitcoin.cz/stats/json/';
	protected $apiKey;
	protected $id;


	public function __construct(array $config) {
		if (empty($config['type']) || $config['type'] != self::type())
			throw new PoolStatException("Type mismatch ($config[type])");

		if (empty($config['apiKey']))
			throw new PoolStatException("Missing apiKey");

		if (empty($config['id']))
			throw new PoolStatException("Missing id");

		$this->id = $config['id'];
		$this->apiKey = $config['apiKey'];
	}


	public static function type() {
		return "mining.bitcoin.cz";
	}


	protected function parseTime($input) {
		if (!preg_match("/^([0-9]{4})-0?([0-9]{1,2})-0?([0-9]{1,2}) 0?([0-9]{1,2}):0?([0-9]{1,2}):0?([0-9]{1,2})$/", $input, $time))
			throw new PoolStatException("Unable to parse time ($input)");

		$time = gmmktime($time[4], $time[5], $time[6], $time[2], $time[3], $time[1]);
		return $time;
	}


	public function fetch($firstRun = false) {
		$file = @file_get_contents(self::$apiUrl . $this->apiKey);
		if (!$file)
			throw new PoolStatException("Http fetch failed", 1);

		$json = @json_decode($file, true);
		if (!$json)
			throw new PoolStatException("Json decode failed", 2);

		$power = isset($json['ghashes_ps']) ? $json['ghashes_ps'] : 0;

		$blocks = array();
		foreach ($json['blocks'] as $blockId => $block) {
			$b = new Block($this->id);
			$b->blockId = $blockId;
			$b->timeStarted = $this->parseTime($block['date_started']);
			$b->timeEnded = $this->parseTime($block['date_found']);
			$b->reward = isset($block['reward']) ? $block['reward'] : 0;
			$b->mature = $block['is_mature'];
			$b->power = $power;

			$blocks[] = $b;
		}

		return $blocks;
	}


	public function getId() {
		return $this->id;
	}
}

$env->addPool('PoolSlush');