<?php
// index.php
ini_set('error_append_string', '');
ini_set('error_reporting', -1);
//ini_set('error_log', '/home/vhosts/www.3dflat.tk/core/log/php_error.log');
//ini_set('error_log', '/home/a3dfla/domains/3dflat.tk/public_html/core/log/php_error.log');

define('START_TIME', microtime(TRUE));

//exit('Maintenance. We will be back soon!');

require_once __DIR__ . '/core/bootstrap.inc.php';

run();
