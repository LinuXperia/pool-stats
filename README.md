pool-stats
==========

Simple tool for collecting statistics from various BitCoin mining pools. It will collect rewards per block and
pool hashrate.

## Supported pools

* mining.bitcoin.cz (slush's pool)
* btcguild.com (PPLNS payments only)

## Requirements

* Any HTTP server
* PHP 5.2+ with PDO and curl extensions
* Up-to-date browser
* Entry in `/etc/crontab` (or another way of launching something periodically)

## How to install

* Download or clone repository
* Make `data` directory writable
* Copy `config.inc.sample.php` to `config.inc.php` and modify your configuration as needed
* Make new vhost and set its document root to `www` directory
* Run `store.php` hourly to fetch new data (crontab entry `0    * * * * www-data /path/to/store.php`)
* Run `store.php` from command-line to check your config and fetch first data
* Check index.php in your browser and rejoice


## License
&copy; 2013 dzindra. [Licensed under the Apache License, Version 2.0](http://www.apache.org/licenses/LICENSE-2.0)
