<?php
/*

	Copyright 2014 Jindrich Dolezy (dzindra)

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

// this class is currently work in progress - ghash.io does not have proper api access to mining data!

class PoolGHashIO implements PoolSource {
	protected $id;
	protected $type;
	protected $file;

	public function __construct(array $config) {
		if (empty($config['type']) || $config['type'] != self::type())
			throw new PoolStatException("Type mismatch ($config[type])");

		if (empty($config['id']))
			throw new PoolStatException("Missing id");

		$this->id = $config['id'];
		$this->file = dirname(__FILE__) . "/../data/" . $config['file'];
	}


	function parseTime($input) {
		if (!preg_match("/^([0-9]{4})-0?([0-9]{1,2})-0?([0-9]{1,2}) 0?([0-9]{1,2}):0?([0-9]{1,2}):0?([0-9]{1,2})$/", $input, $time))
			throw new PoolStatException("Unable to parse time ($input)");

		$time = mktime($time[4], $time[5], $time[6], $time[2], $time[3], $time[1]);
		return $time;
	}


	protected function fetchLines() {
		$csv = file_get_contents($this->file);
		if ($csv === false)
			throw new PoolStatException("Unable to read file", 1);

		$lines = explode("\n", $csv);
		array_shift($lines);
		return $lines;
	}


	/**
	 *
	 * @param bool $firstRun
	 * @throws PoolStatException
	 * @return array of blocks
	 */
	public function fetch($firstRun = false) {
		$lines = $this->fetchLines();

		$blocks = array();
		foreach ($lines as $line) {
			$line = trim($line);
			if ($line == '')
				continue;

			$items = explode("\t", $line);

			$block = new Block($this->id);
			$block->blockId = $items[0];
			$block->timeStarted = $this->parseTime($items[1]);
			$block->timeEnded = $this->parseTime($items[1]);

			if ($items[10] == '-') {
				$block->reward = 0;
				$block->mature = -1;
			} else {
				$block->reward = $items[10];
				$block->mature = $items[4] == '120/120' ? 1 : 0;
			}
			$block->power = $items[6] * 1000000.0;
			$blocks[] = $block;
		}
		return $blocks;
	}


	/**
	 * @return string
	 */
	public static function type() {
		return "ghash.io";
	}


	public function getId() {
		return $this->id;
	}


}

$env->addPool('PoolGHashIO');
