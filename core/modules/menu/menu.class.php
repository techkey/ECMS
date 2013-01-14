<?php
// menu.class.php

namespace core\modules\menu;

use core\modules\config\config;
use core\modules\core_module;

/**
 * @author Cornelis Brouwers <cornelis_brouwers@hotmail.com>
 */
class menu extends core_module
{
  /**
   * Associative array keyed by the menu name.
   * <pre>
   *  'user' => array(                     The name of the menu.
   *    0 => array(
   *      'module' => 'config',       The module that assigned this link.
   *      'title' => 'Home',          The title of the menu.
   *      'path' => '',               The path of the menu, can <b>NOT</b> have placeholders like 'account/{{id}'.
   *      'access_arguments' => '',   Decides who has access.
   *    ]
   *    ...
   *  ]
   * </pre>
   *
   * @var array
   */
  private $menus = array();

  /**
   * Add a menu link.
   * If menu exists the link will be added otherewise a new menu will be created for the link.
   *
   * @param $menu
   * @param $title
   * @param $path
   * @param string $access_arguments
   */
  public function add_link($menu, $title = '', $path = '', $access_arguments = '') {
    if (is_string($menu)) {
      if ((substr($path, 0, 7) != 'http://') && (substr($path, 0, 8) != 'https://')) {
        $path = BASE_PATH . $path;
      }
      $m = array(
        'menu_name'        => $menu,
        'module'           => '',
        'title'            => $title,
        'path'             => $path,
        'access_arguments' => $access_arguments,
      );
    } else {
      if ((substr($menu['path'], 0, 7) != 'http://') && (substr($menu['path'], 0, 8) != 'https://')) {
        $menu['path'] = BASE_PATH . $menu['path'];
      }
      $menu += array(
        'menu_name' => 'navigation',
      );
      $m = $menu;
    }

    if (user_has_access($m['access_arguments'])) {
      $this->menus[$m['menu_name']][] = $m;
    }
  }

  /**
   * Get all menus, sorted.
   *
   * @return array Returns a associative array (sorted) keyed with the menu name.
   */
  public function get_menus() {
    $menus = array();
    foreach ($this->menus as $name => $menu) {
      $menus[$name] = $this->get_menu($name);
    }
    return $menus;
  }

  /**
   * Get menu, sorted.
   *
   * @param string $name
   * @return array Returns the sorted menu.
   */
  public function get_menu($name) {
    $menu = array();
    if (isset($this->menus[$name])) {
      $menu = $this->menus[$name];
      usort($menu, function ($a, $b) { return strcasecmp($a['title'], $b['title']); });
    }
    return $menu;
  }

/* Hooks **********************************************************************/

  /**
   * Hook init().
   *
   * Initialize.
   */
  public function init() {
    // Add menus from the config.
    $config = config::get_all_values();
    foreach ($config as $key => $route) {
      if ($key[0] != '#') {
        continue;
      }
      $route += array(
        'module' => 'config',
      );
      if (isset($route['menu'])) {
        $route['menu'] += array(
          'access_arguments' => '',
  //        'menu_name' => 'navigation',
  //        'type' => MENU_NORMAL_ITEM,
        );
        if (user_has_access($route['menu']['access_arguments'])) {
          $menu_name = (isset($route['menu']['name'])) ? $route['menu']['name'] : 'navigation';
          if ((substr($route['path'], 0, 7) != 'http://') && (substr($route['path'], 0, 8) != 'https://')) {
            $route['path'] = BASE_PATH . $route['path'];
          }
          $this->menus[$menu_name][] = array(
            'title'            => $route['menu']['title'],
            'path'             => $route['path'],
            'module'           => $route['module'],
            'access_arguments' => $route['menu']['access_arguments'],
          );
        }
      }
    }
//    var_dump($this->menus);
  }

  /**
   * Hook menu().
   *
   * @return array
   */
  public function menu() {
    $menu['admin/menus'] = array(
      'title'            => 'Menus',
      'controller'       => 'menu:menus',
      'access_arguments' => 'admin',
      'menu_name'        => 'system',
    );

    return $menu;
  }

  /**
   * Hook block().
   *
   * @return array
   */
  public function block333() {
    $block = array();
    foreach ($this->menus as $name => $menu) {
      usort($menu, function ($a, $b) { return strcasecmp($a['title'], $b['title']); });
      $context['menu']['title'] = $name;
      $context['menu']['links'] = array();
      $context['menu']['attributes'] = build_attribute_string(array('class' => array('menu', 'vertical')));
      foreach ($menu as $link) {
        $context['menu']['links'][] = array(
          'title'      => $link['title'],
          'path'       => $link['path'],
        );
      }
      $block[$name] = array(
        'title'    => ucwords($name),
        'region'   => 'sidebar_first',
        'template' => 'menu',
        'vars'     => $context,
      );
    }

    return $block;
  }

/* Private route controllers **************************************************/

  /**
   * @return string
   */
  public function menus() {
    library_load('stupidtable');
    add_js('$(function(){$(".stupidtable").stupidtable()});', 'inline');

    $header = array(
      array('data' => 'Name',   'data-sort' => 'string'),
      array('data' => 'Module', 'data-sort' => 'string'),
      array('data' => 'Title',  'data-sort' => 'string'),
      array('data' => 'Path',   'data-sort' => 'string'),
    );

    $rows = array();
    foreach ($this->menus as $name => $menu) {
      foreach ($menu as $entry) {
        $rows[] = array(
          $name,
          $entry['module'],
          $entry['title'],
          str_replace(BASE_PATH, '', $entry['path']),
        );
      }
    }

    $ra = array(
      'template' => 'table',
      'vars'     => array(
        'caption' => count($this->menus) . ' menus',
        'header'  => $header,
        'rows'    => $rows,
        'attributes' => array('class' => array('stupidtable', 'sticky')),
      ),
    );

    return get_theme()->theme_table($ra);
  }

}
