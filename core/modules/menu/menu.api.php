<?php
/**
 * @file menu.api.php
 *
 * @api
 */

class menu_api {

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