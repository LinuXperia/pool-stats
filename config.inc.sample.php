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

// Sample configuration file. Copy it to config.inc.php and modify to suit your needs
$id = 1;
$config = array(

	// List of pool accounts. You can have multiple accounts on the same pools as long as they have different ids.
	'pools' => array(
		array(
			'type' => 'mining.bitcoin.cz',
			// use this to automatically assign ids. If you do not change order of pools this should be enough
			'id' => $id++,
			//'apiKey' => 'your api key',
		),
		array(
			'type' => 'btcguild.com',
			// use this to automatically assign ids. If you do not change order of pools this should be enough
			'id' => $id++,
			//'apiKey' => 'your api key',
			//'user' => 'your username',
			//'password' => 'your password',
		),
	),

	// use sqlite by default
	'db' => array(
	    'dsn' => 'sqlite:' . dirname(__FILE__) . '/data/blocks.sqlite',
	),

	// uncomment following block if you want to use mysql or other db driver which is supported by PDO
	/*
	'db' => array(
		'dsn' => 'mysql:dbname=bitcoin;host=127.0.0.1',
		'user' => 'root',
		'password' => '',
		'options' => array(),
	),
	*/

	// your timezone
	'timezone' => 'Europe/Prague',
);
