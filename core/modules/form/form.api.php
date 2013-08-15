<?php
/**
 * @file form.api.php
 *
 * @api
 */

/**
 * Form creation and processing.
 *
 * Class form_api
 */
class form_api {

  /**
   * Validate handler.
   *
   * @param array $form
   * @param array $form_values
   * @param array $form_errors
   * @param mixed $args,...    [optional]
   */
  public function FORM_ID_validate(array &$form, array &$form_values, array &$form_errors, $args = NULL) {}

  /**
   * Submit handler.
   *
   * @param array $form
   * @param array $form_values
   * @param mixed $args,...    [optional]
   */
  public function FORM_ID_submit(array $form, array $form_values, $args = NULL) {}

  /**
   * Hook form_FORM_ID_alter().
   *
   * Runs on a specific form before the form is rendered.
   *
   * @param array  $form
   * @param array  $form_values
   * @param string $form_name
   */
  public function form_FORM_ID_alter(array &$form, array $form_values, $form_name) {}

  /**
   * Hook form_alter().
   *
   * Runs on all forms before the form is rendered.
   *
   * @param array  $form
   * @param array  $form_values
   * @param string $form_name
   */
  public function form_alter(array &$form, array $form_values, $form_name) {}


}
