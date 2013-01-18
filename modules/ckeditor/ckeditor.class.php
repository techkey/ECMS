<?php
/**
 * @file ckeditor.class.php
 */

namespace modules\ckeditor;

use core\modules\module\module;

/**
 *
 */
class ckeditor extends module {


  /* Hooks ********************************************************************/

  /**
   * Hook init().
   */
  public function init() {
    $setting = array(
      'ckeditor' => array(
        'enterMode' => variable_get('ckeditor_enter_mode', 'CKEDITOR.ENTER_BR'),
      ),
    );
    add_js($setting, 'setting');
  }

  /**
   * Hook menu().
   *
   * @return array
   */
  public function menu() {
    $menu['admin/ckeditor'] = array(
      'title' => 'CKEditor',
      'controller' => 'ckeditor:settings',
      'access_arguments' => 'admin',
      'menu_name' => 'system'
    );
    $menu['admin/ckfinder'] = array(
      'title' => 'CKFinder',
      'controller' => 'ckeditor:ckfinder',
      'access_arguments' => 'admin',
      'menu_name' => 'system'
    );

    return $menu;
  }

  /* Private routes ***********************************************************/

  /**
   * @return string
   */
  public function settings() {
    $out = '';

    $form['inline'] = array(
      '#type' => 'fieldset',
      '#title' => 'Inline',
    );
    $form['inline']['enter_mode'] = array(
      '#type' => 'radios',
      '#title' => 'Enter mode',
      '#description' => 'Sets the behavior of the <em>Enter</em> key.',
      '#options' => array(1 => 'p', 2 => 'br', 3 => 'div'),
      '#default_value' => variable_get('ckeditor_enter_mode', 1),
      '#field_prefix' => '<div>',
      '#field_suffix' => '</div>',
    );
    $form['inline']['buttons'] = array(
      '#type' => 'checkboxes',
      '#title' => 'Buttons',
      '#description' => 'Buttons to show.',
      '#options' => make_array_assoc(array('htmlSource', 'Bold', 'Italic', 'Underline')),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Save',
    );

    $out .= get_module_form()->build($form);

    return $out;
  }

  /**
   * @param array $form
   * @param array $form_values
   */
  public function settings_submit(array $form, array $form_values) {
//    $enter_modes = array('CKEDITOR.ENTER_P', 'CKEDITOR.ENTER_BR', 'CKEDITOR.ENTER_DIV');
//    $enter_mode = $enter_modes[$form_values['enter_mode']];
    variable_set('ckeditor_enter_mode', $form_values['enter_mode']);
    set_message('Settings saved.');
  }

  /**
   * @return string
   */
  public function ckfinder() {
    library_load('ckfinder');
    require_once BASE_DIR . 'library/ckfinder/core/ckfinder_php5.php';

    $finder = new \CKFinder() ;
    $finder->BasePath = BASE_PATH . 'library/ckfinder/' ;	// The path for the installation of CKFinder (default = "/ckfinder/").
//    $finder->SelectFunction = 'ShowFileInfo' ;
    // The default height is 400.
    $finder->Height = 600;
    return $finder->CreateHtml() ;
  }
}
