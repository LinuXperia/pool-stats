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

Env::$env = $env = new Env();

class PoolStatException extends Exception {

	public function __construct($message = "", $code = 0) {
		parent::__construct($message, $code);
	}

}

require $env->includePath . '/blocks.inc.php';
require $env->includePath . '/store.slush.inc.php';
require $env->includePath . '/store.btcguild.inc.php';

try {
	$env->init($env->includePath . '/../config.inc.php');
} catch (Exception $e) {
	$env->error($e, 1);
}

class Env {
	public static $env;

	public $includePath = '.';
	public $headless = false;

	public $pdo;
	public $config;

	private $poolTypes = array();


	public function __construct() {
		$this->includePath = dirname(__FILE__);
	}


	public function init($config) {
		$this->headless = $this->detectHeadless();
		$this->loadConfig($config);
		$this->initPdo();

		$tz = $this->config("timezone");
		if ($tz)
			date_default_timezone_set($tz);
	}


	protected function detectHeadless() {
		return PHP_SAPI == 'cli';
	}


	protected function loadConfig($configFile) {
		if (!is_readable($configFile))
			throw new PoolStatException("Config file at '$configFile' not found or not readable.");

		$config = @include $configFile;
		if (!is_array($config))
			throw new PoolStatException("Invalid config file (must return array).");

		if (empty($config['pools']))
			throw new PoolStatException("Invalid config file (must contain at least one pool).");

		if (!is_array($config['pools']))
			throw new PoolStatException("Invalid config file (Pools is not array).");

		if (empty($config['db']['dsn']))
			throw new PoolStatException("Invalid config file (missing dsn info for database).");

		$this->config = $config;
	}


	protected function initPdo() {
		$this->pdo = new PDO(
			$this->config('db.dsn'),
			$this->config('db.user', ''),
			$this->config('db.password', ''),
			$this->config("db.options", array()));
	}


	public function config($names, $default = false) {
		$names = explode('.', $names);
		$value = $this->config;
		foreach ($names as $name) {
			if (is_array($value) && isset($value[$name])) {
				$value = $value[$name];
			} else {
				return $default;
			}
		}
		return $value;
	}


	public function error($exception, $code = 0, $context = "") {
		if ($exception instanceof Exception)
			$message = ($context ? "[".$context."] ":"") . get_class($exception) . ": " . $exception->getMessage();
		else
			$message = ($context ? "[".$context."] ":"") . (string)$exception;

		if ($this->headless) {
			echo date("Y-m-d H:i:s") . "\tError - $message\n";
		} else {
			echo "<div class='error'>$message</div>";

		}
		if ($code > 0)
			exit($code);
	}


	public function addPool($class) {
		$impl = @class_implements($class);
		if ($impl === false)
			throw new PoolStatException("Unable to add pool, class $class failed to load");

		if (!in_array('PoolSource', $impl))
			throw new PoolStatException("Unable to add pool, class $class does not implement PoolSource");

		$callback = array($class, 'type');
		$type = @call_user_func($callback);
		if ($type === NULL || !is_string($type) || empty($type))
			throw new PoolStatException("Unable to add pool $class, method type() does not exists or returns something else than string");


		$this->poolTypes[$type] = (string)$class;
	}


	public function createPools() {
		$pools = array();
		$ids = array();
		$index = 0;
		foreach ($this->config['pools'] as $pool) {
			if (!is_array($pool))
				throw new PoolStatException("Pool entry at $index must be array");

			$id = $this->config("pools.$index.id");
			if (empty($id))
				throw new PoolStatException("Pool entry at $index - missing id (id cannot be zero or empty string)");

			if (in_array($id, $ids))
				throw new PoolStatException("Pool entry at $index - duplicate id $id");
			$ids[] = $id;

			$type = $this->config("pools.$index.type", '');
			if (isset($this->poolTypes[$type])) {
				$poolClass = $this->poolTypes[$type];
				if (class_exists($poolClass)) {
					try {
						$pools[] = new $poolClass($pool);
					} catch (Exception $e) {
						throw new PoolStatException("Pool entry at $index - " . $e->getMessage() . ".", $e->getCode());
					}
				} else {
					throw new PoolStatException("Pool entry at $index - unknown class '$poolClass' for type '$type'.");
				}
			} else {
				throw new PoolStatException("Pool entry at $index - unknown pool type '$type'.");
			}
			$index++;
		}

		return $pools;
	}


	public function createBlockDao() {
		$dao = new BlockDao($this->pdo);
		$dao->createTableIfNeeded();
		return $dao;
	}


}


interface PoolSource {

	/**
	 *
	 * @param bool $firstRun
	 * @throws PoolStatException
	 * @return array of blocks
	 */
	public function fetch($firstRun = false);


	/**
	 * @return string
	 */
	public static function type();


	public function getId();
}
