<?php
// menu.class.php

namespace core\modules\menu;

use core\modules\config\config;
use core\modules\core_module;

/**
 * @author Cornelis Brouwers <cornelis_brouwers@hotmail.com>
 */
class menu extends core_module {
  /**
   * Associative array of menus keyed by the menu name.
   *
   * The menu structure:
   * <pre>
   *    'item1' = array(                        The path (1).
   *      '#link' = array(                      The link of the path.
   *        'access_arguments' => '',           Decides who has access.
   *        'menu_name'        => 'navigation', The menu name.
   *        'module'           => '',           The module that assigned this link.
   *        'path'             => 'item1',      The path, same as above (1), used for inserting a link.
   *        'title'            => 'Item 1',     The title of the menu.
   *      ),
   *      'item1a' = array(                     A sub path.
   *        '#link' = array(                    The link of the sub path.
   *          ...
   *        ),
   *        ...
   *      )
   * </pre>
   *
   * @var array
   */
  private $menus = array();

  /**
   * @internal
   *
   * @param array $menu
   * @param array $link
   * @return bool
   */
  private function _insert_link(array &$menu, array $link) {
    $path = $link['path'];
    $parent = $link['parent'];
    if (isset($menu[$parent])) {
//      $menu[$parent][] = array($path => array('#link' => $link));
      $menu[$parent][$path] = array('#link' => $link);
      return TRUE;
    }
    foreach ($menu as $key => &$value) {
      if ($key[0] == '#') continue;
//      set_message('Searching for <b>' . $parent . '</b> in <b>' . $key . '</b>');
      $found = $this->_insert_link($value, $link);
      if ($found) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Add a menu.
   *
   * @see menu::add_link()
   *
   * @param array $menu A associative array of links keyed by the link path.
   */
  public function add_menu(array $menu) {
    foreach ($menu as $path => $link) {
      $link += array(
        'path' => $path,
        'menu_name' => 'navigation',
        'access_arguments' => '',
        'module' => '',
        'title' => '',
      );
      if (isset($link['parent'])) {
//        set_message('Searching for <b>' . $link['parent'] . '</b>');
        $found = $this->_insert_link($this->menus[$link['menu_name']], $link);
        if (!$found) {
          //
          set_message('Could not find ' . $path);
          //
        }
      } else {
//        $this->menus[$link['menu_name']][$path][] = array('#link' => $link);
        $this->menus[$link['menu_name']][$path]['#link'] = $link;
      }
    }
  }

  /**
   * Add a menu link.
   * <br /><br />
   * The link structure:
   * <pre>
   * 'path' = array(
   *   'access_arguments' => '',
   *   'menu_name'        => 'navigation',
   *   'module'           => '',
   *   'parent'           => '',
   *   'path'             => 'item1',
   *   'title'            => 'Item 1',
   * )
   * </pre>
   *
   * If menu exists the link will be added otherwise a new menu will be created for the link.
   *
   * @param mixed  $menu             The menu name or a link array.
   * @param string $module           [optional] The module name the link belongs to.
   * @param string $title            [optional] The title of the link.
   * @param string $path             [optional] The path of the link.
   * @param string $access_arguments [optional] Who has access to visit the link.
   */
  public function add_link($menu, $module = '', $title = '', $path = '', $access_arguments = '') {
    if (is_string($menu)) {
      $m = array(
        'menu_name'        => $menu,
        'module'           => $module,
        'title'            => $title,
        'path'             => $path,
        'access_arguments' => $access_arguments,
      );
    } else {
      $menu += array(
        'menu_name' => 'navigation',
      );
      $m = $menu;
    }

    if (user_has_access($m['access_arguments'])) {
      invoke('menu_link_presave', $m);

      $this->add_menu(array($m['path'] => $m));
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
      foreach ($this->menus[$name] as $path => $item) {
      $menu[] = $item['#link'];
      }
      usort($menu, function ($a, $b) { return strcasecmp($a['title'], $b['title']); });
    }

    return $menu;
  }

  /**
   * Get menu links, sorted.
   *
   * @param string $menu_name
   * @return array Returns the sorted links.
   */
  public function get_menu_links($menu_name) {
    $links = array();
    if (isset($this->menus[$menu_name])) {
      $menu = $this->get_menu($menu_name);
      foreach ($menu as $data) {
        $links[] = l($data['title'], $data['path']);
      }
    }
    return $links;
  }

/* Hooks **********************************************************************/

  /**
   * Implements hook init().
   *
   * Initialize.
   */
  public function init() {

  }

  /**
   * Implements hook menu().
   *
   * @return array
   */
  public function menu() {
    $menu['admin/menus'] = array(
      'title'            => 'List Menus',
      'controller'       => 'menu::menus',
      'access_arguments' => 'admin',
      'menu_name'        => 'system',
    );

    return $menu;
  }

  /**
   * Implements hook block().
   *
   * @return array
   */
  public function block() {
    $block = array();

    $system_menus = array('main', 'navigation', 'system');
    foreach ($this->menus as $menu_name => $menu) {
      if (in_array($menu_name, $system_menus)) {
        continue;
      }

      $vars['menu'] = array(
        'attributes' => build_attribute_string(array('class' => array('menu', 'vertical'))),
        'links' => $this->get_menu_links($menu_name)
      );

      $block[$menu_name] = array(
        'title' => ucfirst($menu_name),
        'template' => 'menu',
        'vars' => $vars,
        'region' => 'sidebar_first',
      );
    }

    ksort($block);

    return $block;
  }

/* Private route controllers **************************************************/

  /**
   * @return string
   */
  public function menus() {
    library_load('stupidtable');

    $header = array(
      array('data' => 'Title',  'data-sort' => 'string'),
      array('data' => 'Path',   'data-sort' => 'string'),
      array('data' => 'Module', 'data-sort' => 'string'),
    );

    $out = '';

    foreach ($this->menus as $name => $menu) {
      $rows = array();
      foreach ($menu as $entry) {
        $link = $entry['#link'];
        $rows[] = array(
          $link['title'],
          $link['path'],
          $link['module'],
        );
      }
      $ra = array(
        'template' => 'table',
        'vars'     => array(
          'attributes' => array('class' => array('table', 'stupidtable', 'sticky')),
          'caption'    => count($menu) . " links in menu <em>$name</em>",
          'header'     => $header,
          'rows'       => $rows,
        ),
      );
      $out .= get_theme()->theme_table($ra);
    }


    return $out;
  }

}
