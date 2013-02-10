<?php
/**
 * @file bootstrap.php
 */

define('BASE_DIR', realpath(__DIR__ . '/../') . '/');
define('LIBRARY_DIR', BASE_DIR . '/library/');

require_once BASE_DIR . 'core/autoloader.inc.php';
require_once BASE_DIR . 'core/common.inc.php';

get_loader()->add('core\modules', __DIR__);
get_loader()->add('core\themes', __DIR__);
get_loader()->add('core\engines', __DIR__);
get_loader()->add('modules', __DIR__ . '/../');

use core\modules\config\config;
use core\modules\session\session;

config::load();

define('BASE_PATH', config::get_value('system.basepath', '/'));
define('BASE_URL',
    strtolower(substr($_SERVER['SERVER_PROTOCOL'], 0, strpos($_SERVER['SERVER_PROTOCOL'], '/'))) .
    '://' .
    $_SERVER['HTTP_HOST'] .
    rtrim(BASE_PATH, '/')
);
define('LIBRARY_PATH', BASE_PATH . 'library/');

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


require_once BASE_DIR . 'core/database.class.php';
// Check if install module is enabled.
if (!file_exists('/core/modules/install/install.ini')) {
  get_dbase()->connect();
}

session::start();

//wd_add('test', 'test');

/**
 * Load and get all core modules
 *
 * @return array
 */
function load_core_modules() {
  static $a = array();

  if ($a) {
    return $a;
  }

  $core_modules = glob(BASE_DIR . 'core/modules/*', GLOB_ONLYDIR);
  foreach ($core_modules as $core_module) {
    $name = basename($core_module);
    if (file_exists($core_module . '/' . $name . '.ini')) {
      $class = "\\core\\modules\\$name\\$name";
      if (class_exists($class)) {
        $a[$name] = new $class();
      }
    }
  }
  return $a;
}

/**
 * Load and get all user modules.
 *
 * @return array
 */
function load_modules() {
  static $a = array();

  if ($a) {
    return $a;
  }

  $modules = glob(BASE_DIR . 'modules/*', GLOB_ONLYDIR);
  foreach ($modules as $module) {
    $name = basename($module);
    if (file_exists($module . '/' . $name . '.ini')) {
      $class = "\\modules\\$name\\$name";
      if (class_exists($class)) {
        $a[$name] = new $class();
      }
    }
  }
  return $a;
}

/**
 * Get a module instance.
 *
 * If no module is given then all module instances will be returned as a
 * associative array keyed with the module name.
 *
 * @param string $module [optional] The module name.
 * @param bool   $add    [optional]
 * @return bool|object|object[] Returns the object, all module instances or FALSE if module is not found.
 */
function get_module($module = NULL, $add = FALSE) {
  /** @var object[] $modules */
  static $modules = array();

  if (!$modules) {
    $modules += load_core_modules();
    $modules += load_modules();
  }

  if ($module == NULL) {
    return $modules;
  }

  if ($add) {
    $modules += $module;
    return TRUE;
  }

  return (isset($modules[$module])) ? $modules[$module] : FALSE;
}

/**
 * @return \core\themes\darkstar\darkstar
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
 * @param string $method  The method name to call.
 * @param ...             [optional] Variable number of arguments.
 * @return array          Return a array with results keyed by module name.
 */
function invoke($method, &$arg = NULL) {

  $results = array();

  $modules = get_module();
  $enabled_modules = get_module_module()->get_enabled_module_names();

  foreach ($modules as $name => $class) {
    // Skip disabled modules.
    if (!in_array($name, $enabled_modules)) {
      continue;
    }
    if (method_exists($class, $method)) {
      $results[get_class_name($class)] = $class->$method($arg);
    }
  }

  return $results;
}

/**
 * Run the application.
 */
function run() {

  // Instantiate all modules.
  get_module();

  // Run hook init().
  invoke('init');

  /**
   * A array of render arrays keyed by region name and other page info.
   */
  $page = array();

  /**
   * Only show the maintenance page if IN maintenance AND the path is NOT the
   * login form AND the current user is NOT the super user AND user is NOT
   * connected with maintainer IP.
   */
  if (variable_get('system_maintenance', TRUE)) {
    if ((request_path() != 'user/login') &&
        (get_user()->uid != 1) &&
        (variable_get('system_maintainer_ip') != $_SERVER['REMOTE_ADDR']) &&
        (!defined('INSTALL'))) {

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

  /**
   * Add content, page title, status code and template from the routed
   * controller if any is available.
   *
   * [content]     => string
   * [page_title]  => string
   * [status_code] => int
   * [template]    => string
   */
  $content = get_module_router()->route();
  $page += $content;

  /**
   * Only build and render the page if NOT in maintenance OR the current user is
   * super admin OR user is connected with the maintainer IP.
   */
  if (!variable_get('system_maintenance', TRUE) ||
      (get_user()->uid == 1) ||
      (variable_get('system_maintainer_ip') == $_SERVER['REMOTE_ADDR']) ||
      (defined('INSTALL'))) {

    /**
     * Run hook page_build().
     *
     * Fill the page array with regions.
     *
     * [region name] => render array or string
     * ...
     * [region name] => render array or string
     */
    invoke('page_build', $page);

    /**
     * Run hook page_alter().
     *
     * Give modules a chance to alter the page.
     */
    invoke('page_alter', $page);

    /**
     * Render the regions if needed.
     */
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
  }

  invoke('page_render', $page);

  // Add site info to the vars.
  $site_vars['site'] = array(
    'name' => variable_get('system_sitename', 'ECMS'),
  );
  $page += $site_vars;

  $page['base_path'] = BASE_PATH;

  // Use the theme layout if no other layout is assigned.
  $layout = isset($page['template']) ? $page['template'] : 'layout';

  // Render and display the page.
  $echo = get_theme()->render($layout, $page);
  echo $echo;

  // Run hook shutdown().
   invoke('shutdown', $page);

}

