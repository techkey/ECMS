<?php
// router.class.php

namespace core\modules\router;

use core\modules\config\config;

/**
 * @author Cornelis Brouwers <cornelis_brouwers@hotmail.com>
 */
class router
{
  /**
   * Associative array keyed by the route name.
   * <pre>
   *  'home' => [                     The name of the route.
   *    'path' => '',                 The path of the route, can have placeholders like 'account/{{id}'.
   *    'controller' => 'test:home',  The controller in the format class:method.
   *    'menu' => [                   [optional] Menu section is only valid if the path contains <b>NO</b> placeholders.
   *        'name' => 'user',         Name of the menu.
   *        'title' => 'Home',        Title of the menu.
   *    ]
   *  ]
   * </pre>
   *
   * @var array
   */
  private $routes = array();

  public $router_path = '';

  /**
   * Add a route.
   *
   * If $route is a array then the following format is expected ($path and $controller are not used):
   * <pre>
   *  'Home' => [                     The title of the route.
   *    'module' => '',               The module that assigned the route.
   *    'path' => '',                 The path of the route, can have placeholders like 'account/{{id}'.
   *    'controller' => 'test:home',  The controller in the format class:method.
   *    'access_arguments' => ''      The roles that have access.
   *  ]
   * </pre>
   *
   * @param string|array $path
   * @param string       $title
   * @param string       $controller
   * @param string       $access_arguments
   * @param int          $type
   * @param bool         $comments
   */
  public function add_route($path, $title = NULL, $controller = NULL, $access_arguments = NULL, $type = NULL, $comments = NULL) {
    $module = get_called_class();
    if (is_array($path)) {
      $this->routes += $path;
      $this->routes += array('module' => $module);
    }
    else {
      $this->routes[$path] = array(
        'title'            => $title,
        'module'           => $module,
        'controller'       => $controller,
        'access_arguments' => $access_arguments,
        'type'             => $type,
        'comments'         => $comments,
      );
    }
  }

  /**
   * @return array
   */
  public function get_routes() {
    return $this->routes;
  }

  /**
   * @return array|bool
   */
  public function get_current_route() {
    $req_uri = $_SERVER['REQUEST_URI'];
    $bp = config::get_value('system.basepath', '/');
    $len = strlen($bp);
    if ($len) {
      if (substr($req_uri, 0, $len) == $bp) {
        $req_uri = substr($req_uri, $len);
      }
    }
    foreach ($this->routes as $path => $data) {
      if ($path == $req_uri) {
        return $this->routes[$path];
      }
    }

    return FALSE;
  }

  /**
   * @internal method to get the number of parts of a request URI.
   *
   * @param string $req_uri
   *
   * @return int The number of parts of the request URI.
   */
  private function get_part_count($req_uri) {
    $count = 0;
    if (strlen($req_uri) > 1) {
      $count = count(explode('/', ltrim($req_uri, '/')));
    }
//    var_dump($count);
    return $count;
  }

  /**
   * Runs the controller for the URL if exists.
   *
   * @return array Return a associative array with the content and the page title:
   * <ul>
   * <li>page_title: the page title</li>
   * <li>content: the content</li>
   * </ul>
   */
  public function route() {
    $req_uri = request_path();
    if ($req_uri == '') {
      $req_uri = 'home';
    }

    // todo Move aliasing node routes to the node module
    $obj = get_module_node();
    if ($obj) {
      /** @noinspection PhpUndefinedMethodInspection */
      $nid = $obj->get_node_id_by_route($req_uri);
      if ($nid) {
        $req_uri = 'node/' . $nid;
      }
    }

    /** @noinspection PhpUnusedLocalVariableInspection */
    foreach ($this->routes as $path => $route) {
      if ($this->get_part_count($req_uri) != $this->get_part_count($path)) {
        continue;
      }
      $pattern = preg_replace('#{.+?}#', '(.+)', $path);
      $match = preg_match_all("#^$pattern$#", $req_uri, $matches, PREG_SET_ORDER);
      if ($match) {

        // Check access.
        if (!user_has_access($route['access_arguments'])) {
          header('HTTP/1.1 403 Forbidden');
//          set_message('Unauthorized!', 'error');
          return array(
            'page_title'    => 'Unauthorized',
            'content_title' => 'Unauthorized',
            'content'       => '<h2 id="unauthorized">Access to this part of the site is restricted.</h2>',
            'status_code'   => 403,
          );
        }

        list($class, $method) = explode(':', $route['controller']);

        $fcn = "\\core\\modules\\$class\\$class";
        if (!class_exists($fcn, FALSE)) {
          $fcn = "\\modules\\$class\\$class";
          if (!class_exists($fcn, FALSE)) {
            exit("Controller '$class' not found.");
          }
        }

        $instance = get_module($class);

        if (!method_exists($instance, $method)) {
          exit("Method '$method' not found in controller '$class'.");
        }

        $this->router_path = rtrim(str_replace('(.+)', '', $pattern), '/');

        $args = $matches[0];
        array_shift($args);
        $return = call_user_func_array(array($instance, $method), $args);

        if (is_string($return) || is_null($return)) {
          return array(
            'page_title'    => $route['title'],
            'content_title' => $route['title'],
            'content'       => $return,
            'status_code'   => 200,
          );
        } else {
          $return += array(
            'page_title'    => $route['title'],
            'content_title' => $route['title'],
            'status_code'   => 200,
          );

          return $return;
        }
      }
    }

    if ($req_uri == 'home') {
      $return = array(
        'page_title' => 'Welcome',
        'content' => 'No home page is created yet.',
        'status_code' => 200,
      );
    } else {
      header('HTTP/1.1 404 Not Found');
//      set_message("Route '$req_uri' not found.", 'error');
      $return = array(
        'page_title' => 'Not Found',
        'content' => '<h2 id="not_found">Sorry, we could not find the page you requested.</h2>',
        'status_code' => 404,
      );
    }

    return $return;
  }

/* Hooks **********************************************************************/

  /**
   * Hook init();
   */
  public function init() {

    $results = invoke('menu');

    foreach ($results as $module => $menu) {
      if (!is_array($menu)) {
        set_message($module . '->menu returned not a array!', 'warning');
        continue;
      }
      foreach ($menu as $path => $route) {
        $route += array(
          'title'            => $route['title'],
          'module'           => $module,
          'access_arguments' => '',
          'menu_name'        => 'navigation',
          'type'             => MENU_NORMAL_ITEM,
          'comments'         => FALSE,
        );
        $a = array();
        switch ($route['type']) {
          /** @noinspection PhpMissingBreakStatementInspection */
          case MENU_NORMAL_ITEM:
            get_module_menu()->add_link(
              $route['menu_name'],
              $route['module'],
              $route['title'],
              $path, $route['access_arguments']
            );
            // Fall through
          case MENU_CALLBACK:
            $a['title']            = $route['title'];
            $a['module']           = $route['module'];
            $a['controller']       = $route['controller'];
            $a['access_arguments'] = $route['access_arguments'];
            $a['type']             = $route['type'];
            $a['menu_name']        = $route['menu_name'];
            $a['comments']         = $route['comments'];
            break;
        }
        $this->routes += array($path => $a);
      }
    }
  }

  /**
   * Hook menu().
   *
   * @return array
   */
  public function menu() {
    $menu['admin/routes'] = array(
      'title'            => 'Routes',
      'controller'       => 'router:routes',
      'access_arguments' => 'admin',
      'menu_name'        => 'system',
    );

    return $menu;
  }

/* Private route controllers **************************************************/

  /**
   * List all routers.
   *
   * @return string
   */
  public function routes() {
    library_load('stupidtable');
    add_js('$(function(){$(".stupidtable").stupidtable()});', 'inline');

    $header = array(
      array('data' => 'Path',             'data-sort' => 'string'),
      array('data' => 'Title',            'data-sort' => 'string'),
      array('data' => 'Module',           'data-sort' => 'string'),
      array('data' => 'Controller',       'data-sort' => 'string'),
      array('data' => 'Access arguments', 'data-sort' => 'string'),
      array('data' => 'Menu name',        'data-sort' => 'string'),
      array('data' => 'Type',             'data-sort' => 'int'),
      array('data' => 'Comments',         'data-sort' => 'string'),
    );

    $rows = array();
    foreach ($this->routes as $path => $route) {
      $rows[] = array(
        $path,
        $route['title'],
        $route['module'],
        $route['controller'],
        $route['access_arguments'],
        (isset($route['menu_name'])) ? $route['menu_name'] : '?',
        (isset($route['type'])) ? $route['type'] : '?',
        ($route['comments']) ? 'Yes' : '',
      );
    }

    $ra = array(
      'template' => 'table',
      'vars'     => array(
        'caption' => count($this->routes) . ' routes',
        'header' => $header,
        'rows'   => $rows,
        'attributes' => array('class' => array('stupidtable', 'sticky')),
      ),
    );

    return get_theme()->theme_table($ra);
  }

}
