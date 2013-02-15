<?php
/**
 * @file router.api.php
 *
 * @api
 */

class router_api {

  /**
   * Hook route_alter().
   *
   * Runs before the request uri is processed.
   *
   * @param string $request_uri
   * @api
   */
  public function route_alter(&$request_uri) {}

}