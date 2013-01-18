<?php
/**
 * @file ajax.php
 */

/** @noinspection PhpIncludeInspection */
require_once __DIR__ . '/../../core/bootstrap.inc.php';

if (get_user()->uid == 1) {
  $path = BASE_DIR . ltrim($_POST['file_url'], '/');
  $size = getimagesize($path);
  exit($size[3]);
} else {
  exit('Unauthorized!');
}