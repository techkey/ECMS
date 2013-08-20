<?php
/**
 * @file menu.api.php
 *
 * @api
 */

class menu_api {

  /**
   * Hook menu().
   *
   * A typical menu item looks like this:
   * <pre>
   *    array["path"][
   *      "title"             => "The title of the menu item",
   *      "controller"        => "class::method",
   *      "access_arguments"  => "admin",                             optional
   *      "menu_name"         => "The menu where to put this item",   optional, default is "navigation"
   *                                                                  if the menu doesn't exists it will be created
   *      "type"              => MENU_NORMAL_ITEM,                    optional, default is MENU_NORMAL_ITEM
   *                                                                  use MENU_CALLBACK that doesn't appear in the menu
   *    ]
   * </pre>
   *
   * @return array
   * @api
   */
  public function menu() {}

  /**
   * Hook menu_link_presave().
   *
   * Runs before a menu link is saved and only if the user has access to visit
   * this link.
   *
   * @see menu::add_link()
   * @param array $link
   * @api
   */
  public function menu_link_presave(array &$link) {}

}
