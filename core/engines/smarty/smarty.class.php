<?php
// smarty.class.php

namespace core\engines\smarty;

use core\modules\config\config;

/**
 *
 */
class smarty {
  private $smarty;

  /**
   *
   */
  public function __construct() {
    require_once __DIR__ . '/Smarty/Smarty.class.php';
    $this->smarty = new \Smarty();
    $this->smarty->setCacheDir(__DIR__ . '/cache');
    $this->smarty->setConfigDir(__DIR__ . '/config');
    $this->smarty->setCompileDir(__DIR__ . '/compile');
    $this->smarty->setTemplateDir(BASE_DIR . 'core/themes/darkstar/templates');

    $this->smarty->debugging = variable_get('system_debug', FALSE);

    $this->smarty->registerPlugin('modifier', 'number_format', 'number_format');
  }

  /**
   * @return \Smarty
   */
  public function get_smarty() {
    return $this->smarty;
  }

  /**
   * @param string $name
   * @param array $context
   * @param bool $nocache
   * @return string
   */
  public function render($name, $context = array(), $nocache = FALSE) {

    $this->smarty->clearAllAssign();
    $this->smarty->assign($context, $nocache);

    $xdebug_scream = NULL;
    if (extension_loaded('xdebug')) {
      $xdebug_scream = ini_set('xdebug.scream', 0);
    }

    $return = $this->smarty->fetch($name . '.tpl');

    if (variable_get('system_debug', FALSE)) {
      \Smarty_Internal_Debug::display_debug($this->smarty);
    }

    if (extension_loaded('xdebug')) {
      ini_set('xdebug.scream', $xdebug_scream);
    }

    return $return;
  }


  public function clear_cache() {
    $this->smarty->clearAllCache();
  }

  /* Hooks ********************************************************************/



}
