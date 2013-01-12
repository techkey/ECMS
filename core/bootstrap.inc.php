<?php
// bootstrap.php

define('BASE_DIR', realpath(__DIR__ . '/../'));
define('LIBRARY_DIR', BASE_DIR . '/library');

require_once BASE_DIR . '/core/autoloader.inc.php';
require_once BASE_DIR . '/core/common.inc.php';

get_loader()->add('core\modules', __DIR__);
get_loader()->add('core\themes', __DIR__);
get_loader()->add('core\engines', __DIR__);
get_loader()->add('modules', __DIR__ . '/../');
//var_dump(get_loader());

use core\modules\config\config;

config::load();

//define('BASE_PATH', config::get_value('basepath', '/'));
define('BASE_PATH',
  rtrim(
    strtolower(substr($_SERVER['SERVER_PROTOCOL'], 0, strpos($_SERVER['SERVER_PROTOCOL'], '/'))) .
    '://' .
    $_SERVER['HTTP_HOST'] .
    config::get_value('system.basepath', '/'), '/')
);
define('MENU_CALLBACK',               0x0000);
define('MENU_VISIBLE_IN_TREE',        0x0002);
define('MENU_VISIBLE_IN_BREADCRUMB',  0x0004);
//define('MENU_LINKS_TO_PARENT',        0x0008);
//define('MENU_IS_LOCAL_TASK',          0x0080);
//define('MENU_IS_LOCAL_ACTION',        0x0100);

define('MENU_NORMAL_ITEM', MENU_VISIBLE_IN_TREE | MENU_VISIBLE_IN_BREADCRUMB);
//define('MENU_SUGGESTED_ITEM', MENU_VISIBLE_IN_BREADCRUMB | 0x0010);
//define('MENU_LOCAL_TASK', MENU_IS_LOCAL_TASK | MENU_VISIBLE_IN_BREADCRUMB);
//define('MENU_LOCAL_ACTION', MENU_IS_LOCAL_TASK | MENU_IS_LOCAL_ACTION | MENU_VISIBLE_IN_BREADCRUMB);
//define('MENU_DEFAULT_LOCAL_TASK', MENU_IS_LOCAL_TASK | MENU_LINKS_TO_PARENT | MENU_VISIBLE_IN_BREADCRUMB);

//define('MENU_CONTEXT_PAGE',           0x0001);
//define('MENU_CONTEXT_INLINE',         0x0002);


require_once BASE_DIR . '/core/database.class.php';
get_module_session()->start();

//wd_add('test', 'test');

/**
 * Load all core modules
 *
 * @return array
 */
function load_core_modules() {
  $a = array();
  $core_modules = glob(BASE_DIR . '/core/modules/*', GLOB_ONLYDIR);
  foreach ($core_modules as $core_module) {
    $core_module = basename($core_module);
    if ($core_module == 'config') {
      continue;
    }
    $class = "\\core\\modules\\$core_module\\$core_module";
    $a[$core_module] = new $class();
  }
  return $a;
}

/**
 * Load all user modules.
 *
 * @return array
 */
function load_modules() {
  $a = array();
  $modules = glob(BASE_DIR . '/modules/*', GLOB_ONLYDIR);
  foreach ($modules as $module) {
    $module = basename($module);
    $class = "\\modules\\$module\\$module";
    $a[$module] = new $class();
  }
  return $a;
}

/**
 * @param string $module
 * @return bool|object Returns the object or FALSE.
 */
function get_module($module = NULL) {
  static $modules = array();

  if ($module == NULL) {
    return $modules;
  }

  if (!$modules) {
    $modules += load_core_modules();
    $modules += load_modules();
  }

  return (isset($modules[$module])) ? $modules[$module] : FALSE;
}

/**
 * @return \core\themes\sunshine\sunshine
 */
function get_theme() {
  static $theme = NULL;

  if (!$theme) {
    $theme = variable_get('system_theme', 'darkstar');
    $theme = "core\\themes\\$theme\\$theme";
    $theme = new $theme();
  }

  return $theme;
}

/**
 * @api invoke().
 *
 * @param string $method
 * @param mixed $args
 * @return array
 */
function invoke($method, &$args = NULL) {
  $results = array();

  $modules = get_module();

  foreach ($modules as $module) {
    if (method_exists($module, $method)) {
      $results[get_class_name($module)] = $module->$method($args);
    }
  }

  return $results;
}

/**
 *
 */
function run() {

  // Run hook init().
  invoke('init');

  if (variable_get('system_maintenance', TRUE)) {
    $bp = config::get_value('system.basepath', '/');
    $bp = rtrim($bp, '/');
    if (($_SERVER['REQUEST_URI'] != ($bp . '/user/login')) && (get_user()->uid != 1)) {
      $page = array();
//      invoke_pre_render($vars);
      $page['site']['name'] = variable_get('system_sitename', 'ECMS');
      $page['base_path'] = BASE_PATH;
      $page['content'] = get_theme()->fetch('maintenance');
      $echo = get_theme()->render('layout', $page);
      echo $echo;
      // Run hook shutdown().
      $array = array(
        'status_code' => 200,
        'content_length' => strlen($echo),
      );
      invoke('shutdown', $array);
      exit;
    }
  }

  // A array of render arrays keyed by region name.
  $page = array();

  // Add content and the page title.
  $content = get_module_router()->route();
  $page += $content;

  if (!variable_get('system_maintenance', TRUE) || (get_user()->uid == 1)) {
    // Run hook page_build().
    invoke('page_build', $page);
    // Run hook page_alter().
    invoke('page_alter', $page);

    // Render the regions.
    foreach ($page as $region => $ras) {
      if (is_array($ras)) {
        $page[$region] = '';
        foreach ($ras as $ra) {
          if (isset($ra['template'])) {
            $page[$region] .= get_theme()->fetch($ra['template'], $ra['vars']);
          }
          if (isset($ra['content'])) {
            $page[$region] .= $ra['content'];
          }
        }
      }
    }

//    invoke('pre_render', $vars);
  }

  // Add site info to the vars.
  $site_vars['site'] = array(
    'name' => variable_get('system_sitename', 'ECMS'),
  );
  $page += $site_vars;

  $page['base_path'] = BASE_PATH;

  // Render and display the page.
  $echo = get_theme()->render('layout', $page);
  echo $echo;

  // Run hook shutdown().
  $array = array(
     'status_code' => $page['status_code'],
     'content_length' => strlen($echo),
   );
   invoke('shutdown', $array);

}

