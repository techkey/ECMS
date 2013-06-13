<?php
// router.class.php

namespace core\modules\router;

use core\modules\config\config;

/**
 * @author Cornelis Brouwers <cornelis_brouwers@hotmail.com>
 */
class router {
  /**
   * Associative array of routes keyed by the route name.
   *
   * <pre>
   *  'admin/blocks' => (                      The path of the route, can have placeholders like 'account/{{id}'.
   *    'access_arguments' => '',              Decides who has access.
   *    'controller'       => 'block::blocks', The controller in the format class:method.
   *    'menu_name'        => 'system',        Name of the menu.
   *    'module'           => 'block',         The name of the module that assigned this route.
   *    'title'            => 'Blocks',        The title.
   *    'type'             => '6',             The type of the route.
   *  ),
   *  ...
   * </pre>
   *
   * @var \ROUTE[]
   */
  private $routes = array();

  public $router_path = '';

  public $current_path = '';

  /**
   * Add a route.
   *
   * If $route is a array then the following format is expected ($path and $controller are not used):
   * <pre>
   *  array(
   *    'access_arguments' => '',              Decides who has access.
   *    'controller'       => 'block::blocks', The controller in the format class:method.
   *    'menu_name'        => 'system',        Name of the menu.
   *    'module'           => 'block',         The name of the module that assigned this route.
   *    'path'             => 'admin/blocks'   The path of the route, can have placeholders like 'account/{{id}'.
   *    'title'            => 'Blocks',        The title.
   *    'type'             => '6',             The type of the route.
   *  );
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
      $path += array('module' => $module);
      $this->routes += array($path['path'] => $path);
      unset($this->routes[$path['path']]['path']);
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
   * Get all routes.
   *
   * @see router::$routes
   *
   * @return \ROUTE[]
   */
  public function get_routes() {
    return $this->routes;
  }

  /**
   * Get all route paths.
   *
   * @return string[] Returns a array with all route paths.
   */
  public function get_route_paths() {
    return array_keys($this->routes);
  }

  /**
   * @return \ROUTE|bool
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
      $req_uri = variable_get('system_front_page', 'home');
    }

    invoke('route_alter', $req_uri);

    $this->current_path = $req_uri;

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
          return array(
            'page_title'    => 'Unauthorized',
            'content_title' => 'Unauthorized',
            'content'       => '<h2 id="unauthorized">Access to this part of the site is restricted.</h2>',
            'status_code'   => 403,
          );
        }

        list($class, $method) = explode('::', $route['controller']);

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
        'page_title'    => 'Welcome',
        'content_title' => 'Welcome',
        'content'       => 'No front page is created and set yet.',
        'status_code'   => 200,
      );
    } else {
      header('HTTP/1.1 404 Not Found');
      $return = array(
        'page_title'    => 'Page Not Found',
        'content_title' => 'Page Not Found',
        'content'       => '<h2 id="not_found">Sorry, we could not find the page you requested.</h2>',
        'status_code'   => 404,
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
      'title'            => 'List Routes',
      'controller'       => 'router::routes',
      'access_arguments' => 'admin',
      'menu_name'        => 'system',
    );

    return $menu;
  }

  /* Private route controllers ************************************************/

  /**
   * List all routers.
   *
   * @return string
   */
  public function routes() {
    $b = library_load('stupidtable');
    if ($b) {
      add_js('$(function(){$(".stupidtable").stupidtable()});', 'inline');
    }

    $header = array(
      array('data' => 'Path',             'data-sort' => 'string'),
      array('data' => 'Title',            'data-sort' => 'string'),
      array('data' => 'Module',           'data-sort' => 'string'),
      array('data' => 'Controller',       'data-sort' => 'string'),
      array('data' => 'Access arguments', 'data-sort' => 'string'),
      array('data' => 'Menu name',        'data-sort' => 'string'),
      array('data' => 'Type',             'data-sort' => 'int'),
    );

    $types = array(
      MENU_CALLBACK => 'MENU_CALLBACK',
      MENU_NORMAL_ITEM => 'MENU_NORMAL_ITEM',
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
        (isset($route['type'])) ? $types[$route['type']] : '?',
      );
    }

    $ra = array(
      'template' => 'table',
      'vars'     => array(
        'caption'    => count($this->routes) . ' routes',
        'header'     => $header,
        'rows'       => $rows,
        'attributes' => array(
          'class' => array('table', 'stupidtable', 'sticky'),
        ),
      ),
    );

    return get_theme()->theme_table($ra);
  }

}
