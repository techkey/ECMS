<?php
/**
 * @file ecms.api.php
 *
 * @api
 */

class ecms_api {

  /**
   * Hook init().
   *
   * Runs after all modules are loaded and the database is connected.
   *
   * @api
   */
  public function init() {}


  /**
   * Hook page_build().
   *
   * Runs after hook init and content of the routing is in the render array
   * ($page['content']). This hook is used to let modules populate the page
   * array. E.g. filling regions with blocks or set a different content.
   *
   * @param array $page A reference to the page array that may be filled.
   * @api
   */
  public function page_build(array &$page) {}


  /**
   * Hook page_alter().
   *
   * Runs after hook page_build. This hook is used to give modules a opportunity
   * to change the page array. E.g. removing or adding regions or alter the
   * content.
   *
   * @param array $page A reference to the page array that may be altered.
   * @api
   */
  public function page_alter(array &$page) {}


  /**
   * Hook shutdown().
   *
   * This is the last hook and runs after headers and content is sent to the
   * browser.
   *
   * @param array $info Expects a associative array with the following keys:
   * <pre>
   *    status_code     - The http status code sent to the browser.
   *    content_length  - Not used ATM.
   * </pre>
   *
   * @api
   */
  public function shutdown(array $info) {}


  /**
   * Hook page_render().
   *
   * Runs after rendering all regions but before rendering the page layout.
   *
   * @param array $page
   *
   * @api
   */
  public function page_render(array &$page) {}

}