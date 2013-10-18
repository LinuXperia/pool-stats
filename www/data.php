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


header("Content-Type: text/csv");

require dirname(__FILE__) . "/../inc/common.inc.php";

$dao = $env->createBlockDao();

if (empty($_GET['mode']))
	$env->error("Missing mode parameter", 1);
$mode = $_GET['mode'];


function echoCsv($array, $day) {
	$header = true;

	foreach ($array as $line) {
		if ($header) {
			echo implode(',', array_keys($line)) . "\n";
			$header = false;
		}
		echo date($day ? 'Y-m-d' : 'Y-m-d H:i:s', array_shift($line)) . ",";
		echo implode(",", array_values($line)) . "\n";
	}
}

if ($mode == 'total') {
	$blocks = $dao->getGroupedBlockStubs();
	echoCsv($blocks, true);


} else if ($mode == 'daily') {
	$pools = $env->createPools();
	$poolIds = array();
	foreach ($pools as $pool) {
		/** @var $pool PoolSource */
		$poolIds[$pool->getId()] = 0;
	}

	$result = array();
	foreach ($poolIds as $poolId => $foo) {
		$blocks = $dao->getGroupedBlockStubsForPool($poolId);
		foreach ($blocks as $block) {
			if (!isset($result[$block['dayEnded']])) {
				$result[$block['dayEnded']] = array('dayEnded' => $block['dayEnded']) + $poolIds;
			}
			$result[$block['dayEnded']][$poolId] = $block['total'];
		}
	}
	echoCsv($result, true);


} else {
	if (empty($_GET['pool']))
		$env->error("Missing pool parameter", 1);

	$blocks = $dao->getBlockStubsForPool($_GET['pool']);
	echoCsv($blocks, false);

}


