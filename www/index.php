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
?>
<html>
<head>
	<title>pool-stats</title>
	<script type="text/javascript" src="http://dygraphs.com/1.0.0/dygraph-combined.js"></script>
	<style>
		.text-center {
			text-align: center;
		}
	</style>
</head>

<body>
<?php

require dirname(__FILE__) . "/../inc/common.inc.php";

$pools = array();
try {
	// test for exceptions
	$env->createBlockDao();
	$pools = $env->createPools();
} catch (Exception $e) {
	$env->error($e, 1);
}


function echoDiv($id) {
	echo "<div id='$id' style='margin: 3%;min-height: 40%;'></div>";
}


echoDiv("total");
echoDiv("daily");
$cnt = 1;
foreach ($pools as $pool) {
	/** @var  $pool PoolSource */
	$id = "pool-" . ($cnt++);
	echoDiv($id);
	echoJs($id, $pool->getId());
}

function echoJs($htmlId, $poolId) {
	?>
	<script type="text/javascript">
		new Dygraph(
			// containing div
			document.getElementById("<?php echo $htmlId;?>"),
			// CSV or path to a CSV file.
			"data.php?mode=pool&pool=<?php echo urlencode($poolId);?>",
			{
				'power': {
					axis: {}
				},
				axes: {
					y: {
						valueFormatter: function (d) {
							return d.toFixed(8) + " BTC";
						},
						axisLabelFormatter: function (d) {
							return d.toFixed(5);
						}
					},
					y2: {
						valueFormatter: function (d) {
							return d.toFixed(0) + " Ghash/s";
						},
						axisLabelFormatter: function (d) {
							return d.toFixed(0);
						}
					}
				},
				drawPoints: true
			}
		);
	</script>

<?php } ?>

<script type="text/javascript">
	new Dygraph(
		// containing div
		document.getElementById("daily"),
		// CSV or path to a CSV file.
		"data.php?mode=daily",
		{
			axes: {
				y: {
					valueFormatter: function (d) {
						return d.toFixed(8) + " BTC";
					},
					axisLabelFormatter: function (d) {
						return d.toFixed(5);
					}
				}
			},
			drawPoints: true
		}
	);

	new Dygraph(
		// containing div
		document.getElementById("total"),
		// CSV or path to a CSV file.
		"data.php?mode=total",
		{
			axes: {
				y: {
					valueFormatter: function (d) {
						return d.toFixed(8) + " BTC";
					},
					axisLabelFormatter: function (d) {
						return d.toFixed(5);
					}
				}
			},
			drawPoints: true
		}
	);
</script>

<hr/>

<footer class="text-center">
	<p>&copy; 2013 dzindra | Donate: 14ABuhZP7UnZQjwtyWrL19DUiof2jw5ykt</p>

	<p><a href="https://github.com/dzindra/pool-stats">Source on GitHub</a> | <a
			href="http://www.apache.org/licenses/LICENSE-2.0">Licensed under the Apache License, Version 2.0</a></p>
</footer>

</body>
</html>

