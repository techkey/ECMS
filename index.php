<?php
/**
 * @file index.php
 */
ini_set('error_append_string', '');
ini_set('error_reporting', -1);

define('START_TIME', microtime(TRUE));

require_once __DIR__ . '/core/bootstrap.inc.php';

run();
