<?php
/**
 * @file form.api.php
 *
 * @api
 */

class form_api {

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
