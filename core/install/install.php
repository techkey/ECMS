<?php
/**
 * @file install.php
 */

namespace core\install;

require_once '../bootstrap.inc.php';

// Inject the install module.
get_module(array('install' => new install()), TRUE);
// Run the site.
run();

class install {

  public function __construct() {
    define('INSTALL', TRUE);
  }

  public function init() {
    $a = 1;
  }


  public function alter_page(array &$page) {
//    return 'Hello';
  }

}


