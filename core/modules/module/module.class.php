<?php
/**
 * @file module.class.php
 */

namespace core\modules\module;

use core\modules\core_module;

/**
 *
 */
class module extends core_module {

  /* Hooks ********************************************************************/

  public function menu() {
    $menu['admin/module/install/{module}'] = array(
      'title' => 'Install Module',
      'controller' => 'module:module_install',
      'access_arguments' => 'admin',
      'type' => MENU_CALLBACK,
    );
    $menu['admin/module/reinstall/{module}'] = array(
      'title' => 'Reinstall Module',
      'controller' => 'module:module_reinstall',
      'access_arguments' => 'admin',
      'type' => MENU_CALLBACK,
    );
    $menu['admin/module/uninstall/{module}'] = array(
      'title' => 'Uninstall Module',
      'controller' => 'module:module_uninstall',
      'access_arguments' => 'admin',
      'type' => MENU_CALLBACK,
    );

    $menu['admin/modules'] = array(
      'title' => 'Modules',
      'controller' => 'module:modules',
      'access_arguments' => 'admin',
      'menu_name' => 'system',
    );

    return $menu;
  }


  /* Private routes ***********************************************************/

  /**
   * @param string $module
   */
  private function _module_install($module) {
    $schema = get_module($module)->schema();
    $table_name = key($schema);
    $b = db_install_schema($schema);
    if ($b) {
      set_message($table_name . ' installed. ');
    } else {
      set_message('Install of ' . $table_name . ' failed.', 'error');
    }
  }

  /**
   * @param string $module
   * @return string
   */
  public function module_install($module) {
    $data = array(
      'title' => "Install <em>$module</em>?",
//      'message' => 'This action cannot be undone!',
      'button' => 'Install',
      'cancel' => 'admin/modules',
      'extra' => array('module' => $module),
    );

    return get_module_form()->confirm($data);
  }

  /**
   * @param array $form
   * @param array $form_values
   */
  public function module_install_submit(array &$form, array $form_values) {
    $this->_module_install($form_values['extra']['module']);

    $form['#redirect'] = 'admin/modules';
  }

  /**
   * @param $module
   * @return array|string
   */
  public function module_uninstall($module) {
    $data = array(
      'title' => "Uninstall <em>$module</em>?",
      'message' => 'This action cannot be undone!',
      'button' => 'Uninstall',
      'cancel' => 'admin/modules',
      'extra' => array('module' => $module),
    );

    return get_module_form()->confirm($data);
  }

  /**
   * @param array $form
   * @param array $form_values
   */
  public function module_uninstall_submit(array &$form, array $form_values) {
    $schema = get_module($form_values['extra']['module'])->schema();
    $table_name = key($schema);
    $b = db_query('DROP TABLE ' . $table_name);
    if ($b) {
      set_message($table_name . ' uninstalled. ');
    } else {
      set_message('Uninstall of ' . $table_name . ' failed.', 'error');
    }

    $form['#redirect'] = 'admin/modules';
  }

  /**
   * @param string $module
   * @return string
   */
  public function module_reinstall($module) {
    $data = array(
      'title' => "Re-install <em>$module</em>?",
      'message' => 'This action cannot be undone!',
      'button' => 'Re-install',
      'cancel' => 'admin/modules',
      'extra' => array('module' => $module),
    );

    return get_module_form()->confirm($data);
  }

  /**
   * @param array $form
   * @param array $form_values
   */
  public function module_reinstall_submit(array &$form, array $form_values) {
    $schema = get_module($form_values['extra']['module'])->schema();
    $table_name = key($schema);
    db_query('DROP TABLE ' . $table_name);

    $this->_module_install($form_values['extra']['module']);

    $form['#redirect'] = 'admin/modules';
  }

  /**
   * @return string
   */
  public function modules() {
    library_load('stupidtable');
    add_js('$(function(){$(".stupidtable").stupidtable()});', 'inline');

    $modules = get_module();

    $header = array(
      array('data' => 'Name',         'data-sort' => 'string'),
      array('data' => 'Used Hooks'),
      array('data' => 'Can Install',  'data-sort' => 'string'),
      array('data' => 'Table Name',   'data-sort' => 'string'),
      array('data' => 'Installed',    'data-sort' => 'string'),
//      array('data' => 'Weight',   'data-sort' => 'int'),
//      array('data' => 'Template', 'data-sort' => 'string'),
      array('data' => 'Actions',      'colspan' => 3),
    );

    $count = 0;
    $rows = array();
    foreach ($modules as $module) {
      $module_name = get_class_name($module);
      $can_install = method_exists($module, 'schema');
      $table_name = '';
      $table_exists = '';
      if ($can_install) {
        /** @noinspection PhpUndefinedMethodInspection */
        $schema = $module->schema();
        $table_name = key($schema);
        $table_exists = db_table_exists($table_name) ? 'Yes' : 'No';
      }

      $hooks = array('__construct', 'init', 'menu', 'page_build', 'page_alter', 'shutdown');
      $used_hooks = array();
      foreach ($hooks as $hook) {
        if (method_exists($module, $hook)) {
          $used_hooks[] = $hook;
        }
      }
      $used_hooks = implode(', ', $used_hooks);

      $count++;
      $rows[] = array(
        get_class($module),
        $used_hooks,
        ($can_install) ? 'Yes' : 'No',
        $table_name,
        $table_exists,
        ($can_install && ($table_exists != 'Yes')) ? l('install', 'admin/module/install/' . $module_name) : '',
        (($table_exists == 'Yes') && ($module_name != 'user')) ? l('reinstall', 'admin/module/reinstall/' . $module_name) : '',
        (($table_exists == 'Yes') && ($module_name != 'user')) ? l('uninstall', 'admin/module/uninstall/' . $module_name) : '',
      );
    }

    $ra = array(
      'template' => 'table',
      'vars'     => array(
        'caption'    => $count . ' modules',
        'attributes' => array('class' => array('stupidtable', 'sticky')),
        'header'     => $header,
        'rows'       => $rows,
      ),
    );

    return get_theme()->theme_table($ra);
  }



}



