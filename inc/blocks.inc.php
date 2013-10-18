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

class Block {
	public $blockId;
	public $pool;

	public $timeStarted;
	public $timeEnded;
	public $reward;
	public $mature;
	public $power;


	public function __construct($pool) {
		$this->pool = $pool;
	}

}


class BlockDao {
	/**
	 * @var PDO
	 */
	private $pdo;

	/**
	 * @var PDOStatement
	 */
	private $insertStatement;

	/**
	 * @var PDOStatement
	 */
	private $updateStatement;

	/**
	 * @var PDOStatement
	 */
	private $poolStatement;

	/**
	 * @var PDOStatement
	 */
	private $groupPoolStatement;

	/**
	 * @var PDOStatement
	 */
	private $groupTotalPoolStatement;


	public function __construct($pdo) {
		$this->pdo = $pdo;
	}


	public function createTableIfNeeded() {
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$this->pdo->exec("CREATE TABLE IF NOT EXISTS `blocks` (
			`blockId` int(11) NOT NULL,
			`pool` varchar(50) NOT NULL,
			`timeStarted` int(11) NOT NULL,
			`timeEnded` int(11) NOT NULL,
			`dayEnded` int(11) NOT NULL,
			`reward` double NOT NULL,
			`mature` tinyint(1) NOT NULL,
			`power` double NOT NULL,
			PRIMARY KEY  (`blockId`,`pool`))");

		// this can fail (index can already exist)
		try {
			$this->pdo->exec("CREATE INDEX `pool` ON `blocks` (`pool`);");
			$this->pdo->exec("CREATE INDEX `dayEnded` ON `blocks` (`dayEnded`);");
		} catch (PDOException $e) {
			// just ignore
		}
	}


	protected function stripTime($timestamp) {
		$d = getdate($timestamp);
		return mktime(0, 0, 0, $d['mon'], $d['mday'], $d['year']);
	}


	protected function updateBlock(Block $block) {
		if (!$this->updateStatement) {
			$this->updateStatement = $this->pdo->prepare(
				"UPDATE blocks
				 SET timeEnded = :timeEnded, dayEnded = :dayEnded, reward = :reward, mature = :mature
				 WHERE (blockId = :blockId) AND (pool = :pool)"
			);
			if (!$this->updateStatement)
				throw new PoolStatException("Unable to prepare statement");

		}
		$this->updateStatement->bindValue(":blockId", $block->blockId);
		$this->updateStatement->bindValue(":pool", $block->pool);
		$this->updateStatement->bindValue(":timeEnded", $block->timeEnded);
		$this->updateStatement->bindValue(":dayEnded", $this->stripTime($block->timeEnded));
		$this->updateStatement->bindValue(":reward", $block->reward);
		$this->updateStatement->bindValue(":mature", $block->mature);
		$this->updateStatement->execute();
	}


	protected function insertBlock($block) {
		if (!$this->insertStatement) {
			try {
				// try mysql flavour first
				$this->insertStatement = $this->pdo->prepare(
					"INSERT IGNORE INTO blocks(blockId, pool, timeStarted, timeEnded, dayEnded, reward, mature, power)
					 VALUES (:blockId, :pool, :timeStarted, :timeEnded, :dayEnded, :reward, :mature, :power); "
				);
			} catch (PDOException $e) {
				// ignore
			}

			if (!$this->insertStatement) {
				// if it failed, try sqlite flavour
				$this->insertStatement = $this->pdo->prepare(
					"INSERT OR IGNORE INTO blocks(blockId, pool, timeStarted, timeEnded, dayEnded, reward, mature, power)
					 VALUES (:blockId, :pool, :timeStarted, :timeEnded, :dayEnded, :reward, :mature, :power); "
				);

				if (!$this->insertStatement)
					throw new PoolStatException("Unable to prepare statement");
			}
		}
		$this->insertStatement->bindValue(":blockId", $block->blockId);
		$this->insertStatement->bindValue(":pool", $block->pool);
		$this->insertStatement->bindValue(":timeStarted", $block->timeStarted);
		$this->insertStatement->bindValue(":timeEnded", $block->timeEnded);
		$this->insertStatement->bindValue(":dayEnded", $this->stripTime($block->timeEnded));
		$this->insertStatement->bindValue(":reward", $block->reward);
		$this->insertStatement->bindValue(":mature", $block->mature);
		$this->insertStatement->bindValue(":power", $block->power);
		$this->insertStatement->execute();
	}


	public function addBlocks($blocks) {
		if ($blocks instanceof Block)
			$blocks = array($blocks);

		if (!is_array($blocks))
			throw new PoolStatException("Supplied blocks should be array or Block object");

		$this->pdo->beginTransaction();
		foreach ($blocks as $block) {
			$this->updateBlock($block);
			$this->insertBlock($block);
		}
		$this->pdo->commit();
	}


	public function getBlockStubsForPool($poolId) {
		if (!$this->poolStatement) {
			$this->poolStatement = $this->pdo->prepare(
				"SELECT timeEnded,reward,power
				 FROM blocks
				 WHERE blockId <> 1 AND mature <> -1 AND pool = :pool
				 ORDER BY timeEnded ASC;"
			);
			if (!$this->poolStatement)
				throw new PoolStatException("Unable to prepare statement");
		}

		$this->poolStatement->execute(array(':pool' => $poolId));
		return $this->poolStatement->fetchAll(PDO::FETCH_ASSOC);
	}


	public function getGroupedBlockStubsForPool($poolId) {
		if (!$this->groupPoolStatement) {
			$this->groupPoolStatement = $this->pdo->prepare(
				"SELECT `dayEnded`,sum(reward) AS `total`
				 FROM blocks
				 WHERE blockId <> 1 AND mature <> -1 AND pool = :pool
				 GROUP BY `dayEnded`
				 ORDER BY `dayEnded`"
			);
			if (!$this->groupPoolStatement)
				throw new PoolStatException("Unable to prepare statement");
		}

		$this->groupPoolStatement->execute(array(':pool' => $poolId));
		return $this->groupPoolStatement->fetchAll(PDO::FETCH_ASSOC);
	}


	public function getGroupedBlockStubs() {
		if (!$this->groupTotalPoolStatement) {
			$this->groupTotalPoolStatement = $this->pdo->prepare(
				"SELECT `dayEnded`,sum(reward) AS `total`
				 FROM blocks
				 WHERE blockId <> 1 AND mature <> -1
				 GROUP BY `dayEnded`
				 ORDER BY `dayEnded`"
			);
			if (!$this->groupTotalPoolStatement)
				throw new PoolStatException("Unable to prepare statement");
		}

		$this->groupTotalPoolStatement->execute();
		return $this->groupTotalPoolStatement->fetchAll(PDO::FETCH_ASSOC);
	}


}