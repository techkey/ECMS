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

  private $system_modules = array('block', 'form', 'menu', 'module', 'router', 'session', 'system', 'user', 'watchdog');

  /* Hooks ********************************************************************/

  public function schema() {
    $schema['modules'] = array(
      'description' => 'Keeps track of the install status of modules',
      'fields'      => array(
        'name'    => array(
          'type'     => 'varchar',
          'length'   => 255,
          'not null' => TRUE,
        ),
        'installed' => array(
          'type'     => 'tinyint',
          'not null' => TRUE,
        ),
        'enabled'   => array(
          'type'     => 'tinyint',
          'not null' => TRUE,
        ),
      ),
      'primary key' => array('name'),
      'unique keys' => array(
        'module' => array('name'),
      ),
    );

    return $schema;
  }

  /**
   * @return bool
   */
  private function install() {
    $b = db_install_schema($this->schema());
    if ($b) {
      $this->update_db();
      foreach ($this->system_modules as $module) {
        $this->set_module_status($module, TRUE);
        $this->_enable_module($module);
      }
    }

    return $b;
  }

//  public function init() {
//    $this->install();
//  }

  /**
   * Hook menu().
   *
   * @return array
   */
  public function menu() {
    $menu['admin/module/install/{module}']   = array(
      'title'            => 'Install Module',
      'controller'       => 'module::install_module',
      'access_arguments' => 'admin',
      'type'             => MENU_CALLBACK,
    );
    $menu['admin/module/reinstall/{module}'] = array(
      'title'            => 'Reinstall Module',
      'controller'       => 'module::reinstall_module',
      'access_arguments' => 'admin',
      'type'             => MENU_CALLBACK,
    );
    $menu['admin/module/uninstall/{module}'] = array(
      'title'            => 'Uninstall Module',
      'controller'       => 'module::uninstall_module',
      'access_arguments' => 'admin',
      'type'             => MENU_CALLBACK,
    );
    $menu['admin/module/update/{module}'] = array(
      'title'            => 'Update Module',
      'controller'       => 'module::update_module',
      'access_arguments' => 'admin',
      'type'             => MENU_CALLBACK,
    );
    $menu['admin/module/enable/{module}']   = array(
      'title'            => 'Enable Module',
      'controller'       => 'module::enable_module',
      'access_arguments' => 'admin',
      'type'             => MENU_CALLBACK,
    );
    $menu['admin/module/disable/{module}'] = array(
      'title'            => 'Disable Module',
      'controller'       => 'module::disable_module',
      'access_arguments' => 'admin',
      'type'             => MENU_CALLBACK,
    );

    $menu['admin/modules'] = array(
      'title'            => 'List Modules',
      'controller'       => 'module::modules',
      'access_arguments' => 'admin',
      'menu_name'        => 'system',
    );

    return $menu;
  }

  private function update_db() {
    $modules = get_module();
    unset($modules['install']);
    $modules = array_keys($modules);
    $modules_in_db = $this->get_module_names();
    $diff = array_diff($modules, $modules_in_db);
    foreach ($diff as $module) {
      db_insert('modules')
        ->fields(array(
          'name'    => $module,
          'installed' => 0,
          'enabled'   => 0,
        ))
        ->execute();
    }
  }

  /**
   * @param string $module
   * @param bool   $installed
   */
  private function set_module_status($module, $installed) {
    db_update('modules')
      ->fields(array('installed' => (int)$installed))
      ->condition('name', $module)
      ->execute();
  }

  /**
   * Enables or disables a module.
   *
   * @internal
   *
   * @param string $module
   * @param bool   $enable [optional]
   */
  private function _enable_module($module, $enable = TRUE) {
    db_update('modules')
      ->fields(array('enabled' => (int)$enable))
      ->condition('name', $module)
      ->execute();
  }

  /**
   * @return string[]
   */
  private function get_module_names() {
    /** @var string[] $names */
    $names = db_select('modules')
      ->field('name')
      ->execute()
      ->fetchAll(\PDO::FETCH_COLUMN);

    return $names;
  }

  /**
   * Get all modules from the database.
   *
   * @return \MODULE[] Returns a associative array with module objects keyed
   *                   with the module name.
   */
  private function get_modules() {
    /** @var \MODULE[] $modules */
    $modules = db_select('modules')
      ->field('*')
      ->execute()
      ->fetchAll(\PDO::FETCH_OBJ);

    return $modules;
  }

  /**
   * Check if a module is enabled.
   *
   * @param string $module
   * @return bool Returns TRUE if the module is enabled or FALSE if not.
   */
  public function is_enabled($module) {
    $b = db_select('modules')
      ->field('enabled')
      ->condition('name', $module)
      ->execute()
      ->fetchColumn();

    return (bool)$b;
  }

  /**
   * Get all enabled module records from the database.
   *
   * @return \MODULE[] Returns a array of objects representing enabled modules.
   */
  public function get_enabled_modules() {
    /** @var \MODULE[] $modules */
    $modules = db_select('modules')
      ->field('*')
      ->condition('enabled', 1)
      ->execute()
      ->fetchAll(\PDO::FETCH_OBJ);

    return $modules;
  }

  /**
   * Get all enabled module names.
   *
   * @return string[] Returns a array of strings representing the names of enabled modules.
   */
  public function get_enabled_module_names() {
    if (defined('INSTALL')) {
      return array();
    }

    /** @var string[] $modules */
    $modules = db_select('modules')
      ->field('name')
      ->condition('enabled', 1)
      ->execute()
      ->fetchAll(\PDO::FETCH_COLUMN);

    return $modules;
  }

  /**
   * Get all disabled module names.
   *
   * @return string[] Returns a array of strings representing the names of disabled modules.
   */
  public function get_disabled_module_names() {
    if (defined('INSTALL')) {
      return array();
    }

    /** @var string[] $modules */
    $modules = db_select('modules')
      ->field('name')
      ->condition('enabled', 0)
      ->execute()
      ->fetchAll(\PDO::FETCH_COLUMN);

    return $modules;
  }

  /**
   * Get all enabled module instances.
   *
   * @return object[] Returns a associative array of module instances keyed by the module name.
   */
  public function get_enabled_module_instances() {
    /** @var object[] $instances */
    static $instances = array();

    if ($instances) {
      return $instances;
    }

    // 'name' => object
    $modules = get_module();
    // 0 => 'name'
    $enabled_modules = $this->get_enabled_module_names();

    foreach ($enabled_modules as $module_name) {
      $instances[$module_name] = $modules[$module_name];
    }

    return $instances;
  }

  /* Private routes ***********************************************************/

  /**
   * Installs a module.
   *
   * This method runs the install() method of the module. The module itself
   * keeps responsible for the installation of itself.
   *
   * @internal
   *
   * @param string $module
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
   * @param string $module
   * @return string
   */
  public function install_module($module) {
    return get_module_form()->build('install_module_form', $module);
  }

  /**
   * @param array  $form
   * @param array  $form_values
   * @param string $module
   * @return array
   */
  public function install_module_form(array $form, array $form_values, $module) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;

    $form = array();

    $form['message'] = array(
      '#value' => "<p>Are you sure you want to install module <em>$module</em>?</p>",
    );
    $form['submit'] = array(
      '#type'   => 'submit',
      '#value'  => 'Install',
      '#suffix' => '<span class="cancel-submit">' . l('Cancel', 'admin/modules') . '</span>',
    );

    return $form;
  }

  /**
   * @param array  $form
   * @param array  $form_values
   * @param string $module
   */
  public function install_module_form_submit(array &$form, array $form_values, $module) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;

    $class = get_module($module);
    $method = new \ReflectionMethod($class, 'install');
    $method->setAccessible(TRUE);
    $b = $method->invoke($class);
    if ($b) {
      $this->set_module_status($module, TRUE);
      set_message("Module <em>$module</em> is installed.");
    } else {
      set_message("Installation of module <em>$module</em> failed.", 'error');
    }
    $form['#redirect'] = 'admin/modules';
  }

  /**
   * @param string $module
   * @return string
   */
  public function uninstall_module($module) {
    return get_module_form()->build('uninstall_module_form', $module);
  }

  /**
   * @param array  $form
   * @param array  $form_values
   * @param string $module
   * @return array
   */
  public function uninstall_module_form(array $form, array $form_values, $module) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;

    $form = array();

    $form['message'] = array(
      '#value' => '<p>Are you sure you want to uninstall module ' . $module . '?</p>',
    );
    $form['submit'] = array(
      '#type'   => 'submit',
      '#value'  => 'Uninstall',
      '#suffix' => '<span class="cancel-submit">' . l('Cancel', 'admin/modules') . '</span>',
    );

    return $form;
  }

  /**
   * @param array  $form
   * @param array  $form_values
   * @param string $module
   */
  public function uninstall_module_form_submit(array &$form, array $form_values, $module) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;

    $this->_enable_module($module, FALSE);

    $class = get_module($module);
    $method = new \ReflectionMethod($class, 'uninstall');
    $method->setAccessible(TRUE);
    $b = $method->invoke($class);
    if ($b) {
      $this->set_module_status($module, FALSE);
      set_message("Module <em>$module</em> is uninstalled.");
    } else {
      set_message("Uninstall of module <em>$module</em> failed.", 'error');
    }
    $form['#redirect'] = 'admin/modules';
  }

  /**
   * @param string $module
   * @return string
   */
  public function update_module($module) {
    return get_module_form()->build('update_module_form', $module);
  }

  /**
   * @param array  $form
   * @param array  $form_values
   * @param string $module
   * @return array
   */
  public function update_module_form(array $form, array $form_values, $module) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;

    $form = array();

    $form['message'] = array(
      '#value' => '<p>Are you sure you want to update module ' . $module . '?</p>',
    );
    $form['submit'] = array(
      '#type'   => 'submit',
      '#value'  => 'Update',
      '#suffix' => '<span class="cancel-submit">' . l('Cancel', 'admin/modules') . '</span>',
    );

    return $form;
  }

  /**
   * @param array  $form
   * @param array  $form_values
   * @param string $module
   */
  public function update_module_form_submit(array &$form, array $form_values, $module) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;

    $class = get_module($module);
    $method = new \ReflectionMethod($class, 'update');
    $method->setAccessible(TRUE);
    $b = $method->invoke($class);
    if ($b) {
      set_message("Module <em>$module</em> is updated.");
    } else {
      set_message("Update of module <em>$module</em> failed.", 'error');
    }
    $form['#redirect'] = 'admin/modules';
  }

  /**
   * @param string $module
   * @return string
   */
  public function enable_module($module) {
    return get_module_form()->build('enable_module_form', $module);
  }

  /**
   * @param array  $form
   * @param array  $form_values
   * @param string $module
   * @return array
   */
  public function enable_module_form(array $form, array $form_values, $module) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;

    $form = array();

    $form['message'] = array(
      '#value' => "<p>Are you sure you want to enable module <em>$module</em>?</p>",
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Enable',
      '#suffix' => '<span class="cancel-submit">' . l('Cancel', 'admin/modules') . '</span>',
    );

    return $form;
  }

  /**
   * @param array  $form
   * @param array  $form_values
   * @param string $module
   */
  public function enable_module_form_submit(array &$form, array $form_values, $module) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;

    $this->_enable_module($module);
    set_message("Module <em>$module</em> is enabled.");
    $form['#redirect'] = 'admin/modules';
  }

  /**
   * @param string $module
   * @return string
   */
  public function disable_module($module) {
    return get_module_form()->build('disable_module_form', $module);
  }

  /**
   * @param array  $form
   * @param array  $form_values
   * @param string $module
   * @return array
   */
  public function disable_module_form(array $form, array $form_values, $module) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;

    $form = array();

    $form['message'] = array(
      '#value' => "<p>Are you sure you want to disable module <em>$module</em>?</p>",
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Disable',
      '#suffix' => '<span class="cancel-submit">' . l('Cancel', 'admin/modules') . '</span>',
    );

    return $form;
  }

  /**
   * @param array  $form
   * @param array  $form_values
   * @param string $module
   */
  public function disable_module_form_submit(array &$form, array $form_values, $module) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;

    $this->_enable_module($module, FALSE);
    set_message("Module <em>$module</em> is disabled.");
    $form['#redirect'] = 'admin/modules';
  }

  /**
   * @return string
   */
  public function modules() {
    $this->update_db();

    library_load('stupidtable');

    // Get all modules.
    $modules = $this->get_modules();

    $header = array(
      array('data' => 'Name',         'data-sort' => 'string'),
      array('data' => 'Namespace',    'data-sort' => 'string'),
      array('data' => 'Hooks'),
      array('data' => 'Tables'),
      array('data' => 'Actions',      'colspan' => 4),
    );

    $count = 0;
    $rows = array();
    foreach ($modules as $module) {
      $class = get_module($module->name);
      $schema_exists = method_exists($class, 'schema');
      $install_exists = method_exists($class, 'install');
      $uninstall_exists = method_exists($class, 'uninstall');
      $update_exists = method_exists($class, 'update');
      $settings_exists = method_exists($class, 'settings');

      $tables = '';
      if ($schema_exists) {
        /** @noinspection PhpUndefinedMethodInspection */
        $schema = $class->schema();
        $tables = array_keys($schema);
        $tables = implode(', ', $tables);
      }

      $hooks = array(
        '__construct', 'init', 'menu', 'page_build', 'page_alter', 'shutdown',
        'route_alter', 'form_alter', 'menu_link_presave'
      );
      $used_hooks = array();
      foreach ($hooks as $hook) {
        if (method_exists($class, $hook)) {
          $used_hooks[] = $hook;
        }
      }
      $used_hooks = implode(', ', $used_hooks);

      $fqn = get_class($class);

      $install_link = '';

      if (in_array($module->name, $this->system_modules)) {
        if (!$module->installed && $install_exists) {
          $install_link = l('install', 'admin/module/install/' . $module->name);
          $enable_link = 'disabled';
        } elseif (!$module->enabled) {
          $install_link = 'installed';
          $enable_link = l('enable', 'admin/module/enable/' . $module->name);
        } else {
          $install_link = 'installed';
          $enable_link = 'enabled';
        }
      } else {
        if ($install_exists) {
          if ($module->enabled) {
            $install_link = ($module->installed) ? 'installed' : 'not installed';
          } else {
            $install_link = ($module->installed) ? l('uninstall', 'admin/module/uninstall/' . $module->name) : l('install', 'admin/module/install/' . $module->name);
          }
        }

        if ($install_exists && !$module->installed) {
          $enable_link = ($module->enabled) ? 'enabled' : 'disabled';
        } else {
          $enable_link = ($module->enabled) ? l('disable', 'admin/module/disable/' . $module->name) : l('enable', 'admin/module/enable/' . $module->name);
        }
      }

      if ($update_exists && $module->installed) {
        $update_link = l('update', 'admin/module/update/' . $module->name);
      } else {
        $update_link = '';
      }

      if ($settings_exists && $module->enabled) {
        $settings_link = l('settings', 'admin/' . $module->name . '/settings?destination=admin/modules');
      } else {
        $settings_link = '';
      }

      $count++;
      $rows[] = array(
        $module->name,
        substr($fqn, 0, strrpos($fqn, '\\')),
        $used_hooks,
        $tables,
        $install_link,
        $enable_link,
        $update_link,
        $settings_link,
      );
    }

    $ra = array(
      'template' => 'table',
      'vars'     => array(
        'caption'    => $count . ' modules',
        'attributes' => array('class' => array('table',  'stupidtable', 'sticky')),
        'header'     => $header,
        'rows'       => $rows,
      ),
    );

    return get_theme()->theme_table($ra);
  }



}



