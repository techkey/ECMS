<?php
/**
 * @file install.class.php
 */

namespace core\modules\install;

use core\modules\config\config;

/**
 * Class install
 *
 * @package core\modules\install
 */
class install {

  /**  */
  public function __construct() {
    define('INSTALL', TRUE);
  }

//  public function init() {
//  }

  /**
   * @return array
   */
  public function menu() {
    $menu['install'] = array(
      'title' => 'Install',
      'controller' => 'install::install',
      'type' => MENU_CALLBACK,
    );
    $menu['install2'] = array(
      'title' => 'Install',
      'controller' => 'install::install2',
      'type' => MENU_CALLBACK,
    );

    return $menu;
  }

  /**
   * @return string
   */
  public function install() {
    $fname = 'config/config.ini';
    if ((file_exists($fname) && is_writeable($fname)) || is_writeable('config/')) {
      set_message('OK, the config file is writable or can be created.');
    } else {
      set_message('The config file is not writable. On successful install the content of the config file will be displayed so that you can copy and past that into the config file manual.', 'warning');
    }

    $messages = get_module_session()->get_messages();
    $context['messages'] = $messages;
    $status = get_theme()->fetch('messages', $context);

    return $status . get_module_form()->build('install_form');
  }

  /**
   * @param array $form
   * @param array $form_values
   * @return array
   */
  public function install_form(array $form, array $form_values) {
    $form['#attributes'] = array(
      'autocomplete' => 'off',
    );

    $form['database'] = array(
      '#type' => 'fieldset',
      '#title' => 'Database',
      '#description' => <<<DBT
        The database type. Just a few things to mention:
        <ul>
          <li>SQLite3 - The directory where the SQLite3 database is to be stored much be writable.</li>
          <li>MySQL - The database must exist. ECMS does not create the database, it just create the tables.</li>
        </ul>
DBT
    );
    $form['database']['type'] = array(
      '#type' => 'select',
      '#title' => 'Type',
      '#options' => array('sqlite3' => 'SQLite3', 'mysql' => 'MySQL', 'postgresql' => 'PostgreSQL'),
      '#default_value' => config::get_value('database.type', 'sqlite3'),
      '#description' => 'The database to use.',
    );

    ////////////////////////////////////////////////////////////////////////////
    // SQLite3 section.

    $form['database']['sqlite3'] = array(
      '#type' => 'fieldset',
      '#title' => 'SQLite3',
      '#description' => <<<DBT
        The file to use as database may be a absolute or a relative path.
        A absolute path must begin with a / (slash for *nix) or a drive letter (e.g d:\\ or d:/ for Windows).
        A relative path is relative from where the index.php file of ECMS is located and is automaticly converted to a absolute path.
DBT
    );

    if (isset($form_values['sqlite3_filepath']) && ($form_values['sqlite3_filepath'] != '') && !isset($form_errors['sqlite3_filepath'])) {
      $filepath = $form_values['sqlite3_filepath'];
    } else {
      $filepath = config::get_value('database.sqlite3_filepath', '');
    }
    if ($filepath) {
      if (file_exists($filepath) && filesize($filepath)) {
        $form['database']['sqlite3']['sqlite3_warning'] = array(
          '#value' => '<ul class="warning-messages"><li>SQLite3 database file exists and is not empty, you might consider making a backup of the database.</li></ul>',
        );
      }
    }

    $form['database']['sqlite3']['sqlite3_filepath'] = array(
      '#type' => 'textfield',
      '#title' => 'Filepath',
      '#default_value' => config::get_value('database.sqlite3_filepath', ''),
      '#description' => 'The file to use as database.',
      '#attributes' => array('autocomplete' => 'on'),
    );

    ////////////////////////////////////////////////////////////////////////////
    // MySQL section.

    $form['database']['mysql'] = array(
      '#type' => 'fieldset',
      '#title' => 'MySQL',
      '#description' => <<<DBT
        The name of the MySQL database, must exist. You might use your hosting control panel to create one.
DBT
    );

    if (isset($form_values['database_name']) && $form_values) {
      $database = $form_values['database_name'];
      $username = $form_values['username'];
      $password = $form_values['password'];
    } else {
      $database = config::get_value('database.database_name', '');
      $username = config::get_value('database.username', '');
      $password = config::get_value('database.password', '');
    }
    if ($database != '') {
      try {
        $flags = ini_get('error_reporting');
        ini_set('error_reporting', $flags & ~E_WARNING);
        $pdo    = new \PDO("mysql:dbname=$database", $username, $password);
        ini_set('error_reporting', $flags);
        $result = $pdo->query('SHOW TABLES FROM ' . $database);
        $a      = $result->fetchAll();
        if ($a) {
          $form['database']['mysql']['mysql_warning'] = array(
            '#value' => '<ul class="warning-messages"><li>MySQL database is not empty, you might consider making a backup of the database.</li></ul>',
          );
        }
      } catch (\Exception $e) {
        $form['database']['mysql']['mysql_warning'] = array(
          '#value' => '<ul class="warning-messages"><li>' . $e->getMessage() . '</li></ul>',
        );
      }
    }

    $form['database']['mysql']['database_name'] = array(
      '#type' => 'textfield',
      '#title' => 'Database Name',
      '#default_value' => config::get_value('database.database_name', ''),
      '#description' => 'The name of the database.',
      '#attributes' => array('autocomplete' => 'on'),
    );
    $form['database']['mysql']['username'] = array(
      '#type' => 'textfield',
      '#title' => 'Username',
      '#default_value' => config::get_value('database.username', ''),
      '#description' => 'The username of the database to connect.',
      '#attributes' => array('autocomplete' => 'on'),
    );
    $form['database']['mysql']['password'] = array(
      '#type' => 'password',
      '#title' => 'Password',
      '#default_value' => config::get_value('database.password', ''),
      '#description' => 'The password of the database to connect.',
    );



    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Install',
    );

    return $form;
  }

  /**
   * @param array $form
   * @param array $form_values
   * @param array $form_errors
   */
  public function install_form_validate(array $form, array &$form_values, array &$form_errors) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;

    if ($form_values['type'] == 'sqlite3') {
      $filepath = $form_values['sqlite3_filepath'];
      if (($filepath[0] == '/') || ($filepath[1] == ':')) {
      } else {
        $filepath = BASE_DIR . $filepath;
        $form_values['sqlite3_filepath'] = $filepath;
      }

      if ($filepath == '') {
        $form_errors['sqlite3_filepath'] = 'File path to the SQLite3 database cannot be empty.';
      } else {
        if (!file_exists(dirname($filepath))) {
          $form_errors['sqlite3_filepath'] = 'File path does not exist.';
        } else {
          if (!is_writable(dirname($filepath))) {
            $form_errors['sqlite3_filepath'] = 'File path is not writable.';
          }
        }
      }
    } else {
      $database = $form_values['database_name'];
      $username = $form_values['username'];
      $password = $form_values['password'];
      if ($database == '') {
        $form_errors['database_name'] = 'Database name field cannot be empty.';
      }
      if ($username == '') {
        $form_errors['username'] = 'Username field cannot be empty.';
      }
      if ($password == '') {
        $form_errors['password'] = 'Password field cannot be empty.';
      }
      if (!$form_errors) {
        try {
          $xdebug = extension_loaded('xdebug');
          if ($xdebug) {
            $xdebug = xdebug_is_enabled();
            xdebug_disable();
          }
          /** @noinspection PhpUsageOfSilenceOperatorInspection */
          @new \PDO("mysql:dbname=$database", $username, $password);
          if ($xdebug) {
            xdebug_enable();
          }
          set_message('Database connection succeeded.');
        } catch (\PDOException $e) {
          set_message('Database connection failed.' . $e->getMessage(), 'error');
        }
      }
    }
  }

  /**
   * @param array $form
   * @param array $form_values
   * @return string
   */
  public function install_form_submit(array &$form, array $form_values) {
    $BASE_PATH = BASE_PATH;
    $_SESSION['config']['values'] = $form_values;
    $_SESSION['config']['file'] = <<<CFG
; config.ini

[system]
basepath = $BASE_PATH

[database]
type = '{$form_values['type']}'
; for sqlite3, this must be a absolute filepath
sqlite3_filepath = '{$form_values['sqlite3_filepath']}'
; for others
database_name = '{$form_values['database_name']}'
username = '{$form_values['username']}'
password = '{$form_values['password']}'

CFG;

    if ($form_values['type'] == 'sqlite3') {
      touch($form_values['sqlite3_filepath']);
    }

    $form['#redirect'] = 'install2';
  }

  /**
   * Install all core modules.
   */
  private function install_modules() {
    // Get all core modules.
    $modules = load_core_modules();

    foreach($modules as $name => $class) {
      // Check if the module has a table defined.
      if (method_exists($class, 'install')) {
        /** @noinspection PhpUndefinedMethodInspection */
        $method_install = new \ReflectionMethod($class, 'install');
        $method_install->setAccessible(TRUE);
        $method_install->invoke($class);

        // Add default super admin user.
        if ($name == 'user') {
          $method_add = new \ReflectionMethod($class, 'add');
          $method_add->setAccessible(TRUE);
          $method_add->invoke($class, 'admin', 'admin000', 'admin@localmail.com', 1, 'admin');
        }
      }
    }
  }

  /**
   * @return string
   */
  public function install2() {
    if (!isset($_SESSION['config']) || ($_SESSION['config'] == '')) {
      go_to('install');
    }

    $values = $_SESSION['config']['values'];
    $file = $_SESSION['config']['file'];
    unset($_SESSION['config']);

    $fname = 'config/config.ini';
    if ((file_exists($fname) && is_writeable($fname)) || is_writeable('config/')) {
      if (file_exists($fname)) {
        copy($fname, $fname . '.bak');
      }
      file_put_contents($fname, $file);
      $out = '<p>The config file is written.</p>';
    } else {
      $out = '<pre style="background: #CCCCCC;">' . $file . '</pre>';
      $out .= '<p>Above is the config that you must copy and past into config/config.ini before visiting the site.</p>';
    }



    get_dbase()->connect($values);

    $this->install_modules();

    $out .= '<h3>ECMS is installed.</h3>';
    $out .= '<p>The core/install/install.ini file must be removed or renamed for the site to function!</p>';
    $out .= '<p>Visit the ' . l('site', '') . ' or ' . l('login', 'user/login') . '.</p>';

    return $out;
  }
}