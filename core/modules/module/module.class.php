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

  /**
   * Hook menu().
   *
   * @return array
   */
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
   * Installs a module.
   *
   * This method runs the install() method of the module. The module itself
   * keeps responsible for the installation of itself.
   *
   * @param string $module
   *
   * @internal
   */
  private function _module_install($module) {
    $class = get_module($module);
    $method_install = new \ReflectionMethod($class, 'install');
    $method_install->setAccessible(TRUE);
    $b = $method_install->invoke($class);
    if ($b) {
      set_message('Module <em>' . $module . '</em> is installed. ');
    } else {
      set_message('Installation of module <em>' . $module . '</em> failed.', 'error');
    }
  }

  /**
   * @param $module
   * @return string
   */
  public function module_install($module) {
    return get_module_form()->build('module_install_form', $module);
  }

  /**
   * @param array  $form
   * @param array  $form_values
   * @param array  $form_errors
   * @param string $module
   * @return array
   */
  public function module_install_form(array $form, array $form_values, array $form_errors, $module) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_errors;

    $form = array();

    $form['message'] = array(
      '#value' => '<p>Are you sure you want to install module ' . $module . '?</p>',
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Install',
      '#suffix' => '&nbsp;&nbsp;' . l('Cancel', 'admin/modules'),
    );

    return $form;
  }

  /**
   * @param array  $form
   * @param array  $form_values
   * @param string $module
   */
  public function module_install_form_submit(array &$form, array $form_values, $module) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;

    $this->_module_install($module);

    $form['#redirect'] = 'admin/modules';
  }

  /**
   * @param $module
   * @return string
   */
  public function module_uninstall($module) {
    return get_module_form()->build('module_uninstall_form', $module);
  }

  /**
   * @param array $form
   * @param array $form_values
   * @param array $form_errors
   * @param       $module
   * @return array
   */
  public function module_uninstall_form(array $form, array $form_values, array $form_errors, $module) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_errors;

    $form['message'] = array(
      '#value' => '<p>This will also uninstall any database tables and persistent variables of the module.</p><p>Are you sure you want to uninstall module ' . $module . '?</p>',
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Uninstall',
      '#suffix' => '&nbsp;&nbsp;' . l('Cancel', 'admin/modules'),
    );

    return $form;
  }

  /**
   * @param array  $form
   * @param array  $form_values
   * @param string $module
   */
  public function module_uninstall_form_submit(array &$form, array $form_values, $module) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;

//    $schema = get_module($form_values['extra']['module'])->schema();
//    $table_name = key($schema);
//    $b = db_query('DROP TABLE ' . $table_name);
    $b = FALSE;

    if ($b) {
      set_message('Module <em>' . $module . '</em> is uninstalled. ');
    } else {
      set_message('Uninstall of module <em>' . $module . '</em> failed.', 'error');
    }

    $form['#redirect'] = 'admin/modules';
  }

  /**
   * @param $module
   * @return string
   */
  public function module_reinstall($module) {
    return get_module_form()->build('module_reinstall_form', $module);
  }

  /**
   * @param array  $form
   * @param array  $form_values
   * @param array  $form_errors
   * @param string $module
   * @return array
   */
  public function module_reinstall_form(array $form, array $form_values, array $form_errors, $module) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_errors;

    $form = array();

    $form['message'] = array(
      '#value' => '<p>This will also reinstall any database tables and persistent variables of the module.</p><p>Are you sure you want to reinstall module ' . $module . '?</p>',
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Reinstall',
      '#suffix' => '&nbsp;&nbsp;' . l('Cancel', 'admin/modules'),
    );

    return $form;
  }

  /**
   * @param array $form
   * @param array $form_values
   * @param string $module
   */
  public function module_reinstall_submit(array &$form, array $form_values, $module) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;

//    $schema = get_module($form_values['extra']['module'])->schema();
//    $table_name = key($schema);
//    db_query('DROP TABLE ' . $table_name);
//
//    $this->_module_install($form_values['extra']['module']);
    set_message('Reinstall is not implemented yet.', 'warning');

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
      array('data' => 'Namespace',    'data-sort' => 'string'),
      array('data' => 'Used Hooks'),
      array('data' => 'Has Install',  'data-sort' => 'string'),
//      array('data' => 'Table Name',   'data-sort' => 'string'),
//      array('data' => 'Installed',    'data-sort' => 'string'),
//      array('data' => 'Weight',   'data-sort' => 'int'),
//      array('data' => 'Template', 'data-sort' => 'string'),
//      array('data' => 'Actions',      'colspan' => 3),
    );

    $skip_modules = array('system', 'user');

    $count = 0;
    $rows = array();
    foreach ($modules as $module) {
      $module_name = get_class_name($module);
      $can_install = method_exists($module, 'install');
//      $table_name = '';
//      $table_exists = '';
      if ($can_install) {
        /** @noinspection PhpUndefinedMethodInspection */
//        $schema = $module->schema();
//        $table_name = key($schema);
//        $table_exists = db_table_exists($table_name) ? 'Yes' : 'No';
      }

      $hooks = array('__construct', 'init', 'menu', 'page_build', 'page_alter', 'shutdown');
      $used_hooks = array();
      foreach ($hooks as $hook) {
        if (method_exists($module, $hook)) {
          $used_hooks[] = $hook;
        }
      }
      $used_hooks = implode(', ', $used_hooks);

      $fqn = get_class($module);

      $count++;
      $rows[] = array(
        $module_name,
        substr($fqn, 0, strrpos($fqn, '\\')),
        $used_hooks,
        ($can_install) ? 'Yes' : '',
//        $table_name,
//        $table_exists,
//        ($can_install && ($table_exists != 'Yes')) ? l('install', 'admin/module/install/' . $module_name) : '',
//        (($table_exists == 'Yes') && !in_array($module_name, $skip_modules)) ? l('reinstall', 'admin/module/reinstall/' . $module_name) : '',
//        (($table_exists == 'Yes') && !in_array($module_name, $skip_modules)) ? l('uninstall', 'admin/module/uninstall/' . $module_name) : '',
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



