<?php
/**
 * @file user.class.php
 */

namespace core\modules\user;

use core\modules\config\config;
use stdClass;
use core\modules\core_module;

/**
 * Class to handle users.
 */
class user extends core_module
{
  /**
   * @var \USER
   */
  private $user = NULL;

  /**
   * The schema definition.
   */
  public function schema() {
    $schema['users'] = array(
      'description' => 'The users table.',
      'fields'      => array(
        'uid'        => array(
          'type'     => 'serial',
        ),
        'username'   => array(
          'type'     => 'varchar',
          'length'   => 255,
          'not null' => TRUE
        ),
        'password'   => array(
          'type'     => 'varchar',
          'length'   => 255,
          'not null' => TRUE
        ),
        'email'      => array(
          'type'     => 'varchar',
          'length'   => 255,
          'not null' => TRUE
        ),
        'created'    => array(
          'type'     => 'integer',
          'not null' => TRUE
        ),
        'ip'         => array(
          'type'     => 'varchar',
          'length'   => 255,
          'not null' => TRUE
        ),
        'last_login' => array(
          'type'     => 'integer',
          'not null' => TRUE,
          'default'  => 0
        ),
        'last_ip'    => array(
          'type'     => 'varchar',
          'length'   => 255,
          'not null' => TRUE
        ),
        'activated'  => array(
          'type'     => 'tinyint',
          'not null' => TRUE,
        ),
        'blocked'    => array(
          'type'     => 'tinyint',
          'not null' => TRUE,
        ),
        'role'      => array(
          'type'     => 'varchar',
          'length'   => 255,
          'not null' => TRUE
        ),
      ),
      'primary key' => array('uid'),
      'indexes' => array(
        'username' => array('username'),
        'email' => array('email'),
        'created' => array('created'),
      ),
    );

    return $schema;
  }

  /**
   * Hook install().
   */
  private function install() {
    db_install_schema($this->schema());
  }

  /**
   * Get the current logged in user or the guest account.
   *
   * @return \USER Returns the current logged in user object or the guest account.
   */
  public function get_loggedin_user() {
    if ($this->user) {
      return $this->user;
    }

    if (db_is_active() && isset($_SESSION['uid']) && db_table_exists('users')) {
      $this->user = $this->get_user_by_uid($_SESSION['uid']);
    } else {
      $this->user = new stdClass();
      $this->user->uid = 0;
      $this->user->username = 'guest';
      $this->user->role = '';
    }
    return $this->user;
  }

  /**
   * Add a user.
   *
   * @param string $username
   * @param string $password
   * @param string $email
   * @param int $activated [optional]
   * @param string $role [optional]
   * @return bool|int
   */
  private function add($username, $password, $email, $activated = 0, $role = 'member') {
    if (variable_get('system_password_crypt', TRUE)) {
      $password = crypt($password);
    }
    $id = db_insert('users')->fields(array(
      'username'  => $username,
      'password'  => $password,
      'email'     => $email,
      'created'   => time(),
      'ip'        => $_SERVER['REMOTE_ADDR'],
      'last_ip'   => $_SERVER['REMOTE_ADDR'],
      'activated' => $activated,
      'blocked'   => 0,
      'role'      => $role,
    ))->execute();

    return $id;
  }

  /**
   * Update a user account.
   *
   * @param int    $uid
   * @param string $password
   * @param string $email
   * @param bool   $activated
   * @param bool   $blocked
   * @param string $role
   */
  private function update($uid, $password = NULL, $email = NULL, $activated = NULL, $blocked = NULL, $role = NULL) {

    $db = db_update('users');
    if ($password  !== NULL) {
      if (variable_get('system_password_crypt', TRUE)) {
        $db->fields(array('password'  => crypt($password)));
      } else {
        $db->fields(array('password'  => $password));
      }
    }
    if ($email     !== NULL) $db->fields(array('email'     => $email));
    if ($activated !== NULL) $db->fields(array('activated' => (int)$activated));
    if ($blocked   !== NULL) $db->fields(array('blocked'   => (int)$blocked));
    if ($role      !== NULL) $db->fields(array('role'      => $role));
    $db->condition('uid', $uid)->execute();
  }

  /**
   * Get all users.
   *
   * @return array
   * @return \USER[] Returns a array of user objects.
   */
  private function get_all_users() {
    $users = db_select('users')->field('*')->execute()
      ->fetchAll(\PDO::FETCH_OBJ);

    foreach ($users as &$user) {
      unset($user->password);
    }

    return $users;
  }

  /**
   * @param int $uid
   * @return \USER|bool Returns a user object or FALSE if username doesn't exists.
   */
  private function get_user_by_uid($uid) {
    /** @var \USER $user */
    $user = db_select('users')->field('*')->condition('uid', $uid)->execute()
      ->fetch(\PDO::FETCH_OBJ);

    unset($user->password);

    return $user;
  }

  /**
   * @param string $email
   * @return \USER|bool Returns a user object or FALSE if username doesn't exists.
   */
  private function get_user_by_email($email) {
    $user = db_select('users')->field('*')->condition('email', $email)->execute()
      ->fetch(\PDO::FETCH_OBJ);

    unset($user->password);

    return $user;
  }

  /**
   * @param string $username
   * @return \USER|bool Returns a user object or FALSE if username doesn't exists.
   */
  private function get_user_by_username($username) {
    $user = db_select('users')->field('*')->condition('username', $username)->execute()
      ->fetch(\PDO::FETCH_OBJ);

    unset($user->password);

    return $user;
  }

  /**
   * Helper function to validate email addresses.
   *
   * @param array $form_values
   * @param array $form_errors
   */
  private function validate_email(array $form_values, array &$form_errors) {
    if (!filter_var($form_values['email'], FILTER_VALIDATE_EMAIL)) {
      $form_errors['email'] = 'Not a valid email address.';
    } else {
      $hostname = substr($form_values['email'], strpos($form_values['email'], '@') + 1);
      if (!getmxrr($hostname, $mxhosts)) {
        $form_errors['email'] = 'MX record not found.';
      }
    }
  }

  /**
   * @param string $username
   * @param string $password
   * @return int|bool Returns the user id (uid) or FALSE if login is not valid.
   */
  private function is_login_valid($username, $password) {
    /** @var \USER $user */
    $user = db_select('users')
      ->field('*')
      ->condition('username', $username)
      ->execute()
      ->fetchObject();

    if ($user) {
      if (variable_get('system_password_crypt', TRUE)) {
        if (crypt($password, $user->password) == $user->password) {
          return $user->uid;
        }
      }
      else {
        if ($user->password == $password) {
          return $user->uid;
        }
      }
    }
    return FALSE;
  }

  /**
   * Activate or deactivate a account.
   *
   * @param int  $uid
   * @param bool $activate
   */
  private function activate($uid, $activate = TRUE) {
    $this->update($uid, NULL, NULL, $activate);
  }

  /**
   * Check if a account is activated.
   *
   * @param int $uid
   * @return bool
   */
  private function is_activated($uid) {
    $a = db_select('users')
      ->field('activated')
      ->condition('uid', $uid)
      ->execute()
      ->fetchColumn();

    return (bool)$a;
  }

  /**
   * Block or unblock a account.
   *
   * @param int  $uid
   * @param bool $block
   */
  private function block_account($uid, $block = TRUE) {
    $this->update($uid, NULL, NULL, NULL, $block);
  }

  /**
   * Check if a account is blocked.
   *
   * @param int $uid
   * @return bool
   */
  private function is_blocked($uid) {
    $a = db_select('users')
      ->field('blocked')
      ->condition('uid', $uid)
      ->execute()
      ->fetchColumn();

    return (bool)$a;
  }

/* Hooks **********************************************************************/

  /**
   * hook init()
   */
  public function init() {
    add_css($this->get_path() . 'user.css', array('weight' => 1));
    add_css($this->get_path() . 'css/style.css', array('pages' => array('user/*', 'admin/user/*')));
  }

  /**
   * hook menu()
   *
   * @return array
   */
  public function menu() {
    if (defined('INSTALL')) {
      return array();
    }

    $menu['admin/users'] = array(
      'title'            => 'Users',
      'controller'       => 'user:list_users',
      'access_arguments' => 'admin',
      'type'             => MENU_NORMAL_ITEM,
      'menu_name'        => 'system',
    );
    $menu['admin/user/edit/{uid}'] = array(
      'title'            => 'Edit User',
      'controller'       => 'user:edit',
      'access_arguments' => 'admin',
      'type'             => MENU_CALLBACK,
    );
    $menu['admin/user/delete/{uid}'] = array(
      'title'            => 'Delete User',
      'controller'       => 'user:delete',
      'access_arguments' => 'admin',
      'type'             => MENU_CALLBACK,
    );

    $menu['user/login'] = array(
      'title'            => 'Login',
      'controller'       => 'user:login',
      'type'             => MENU_CALLBACK,
    );
    $menu['user/logout'] = array(
      'title'            => 'Logout',
      'controller'       => 'session:logout',
      'type'             => MENU_CALLBACK,
    );
    $menu['user/register'] = array(
      'title'            => 'Register',
      'controller'       => 'user:register_user',
      'type'             => MENU_CALLBACK,
    );
    $menu['user/edit'] = array(
      'title'            => ' My Account',
      'controller'       => 'user:edit_user',
      'type'             => MENU_CALLBACK,
    );
    $menu['user/password_reset'] = array(
      'title'            => 'Password Reset',
      'controller'       => 'user:password_reset',
      'type'             => MENU_CALLBACK,
    );
    $menu['user/email_confirm/{code}'] = array(
      'title'            => 'Email Confirmation',
      'controller'       => 'user:email_confirm',
      'type'             => MENU_CALLBACK,
    );

    return $menu;
  }

  /**
   * Hook block().
   *
   * @return array
   */
  public function block() {
    if (defined('INSTALL')) {
      return array();
    }

    $user = $this->get_loggedin_user();
    $src = BASE_PATH . 'core/misc/user_icon16.png';
    $content = '<' . "img src='$src' style='margin-right: 5px; vertical-align: middle;' alt='' />";
    $content .= $user->username;
    if ($user->uid == 0) {
      $dest = request_path();
      if ($dest && (strpos($dest, 'user/email_') === FALSE)) {
        $dest = '?destination=' . $dest;
      } else {
        $dest = '';
      }
      $content .= ' ' . l('login', 'user/login' . $dest) . ' ' . l('register', 'user/register');
    } else {
      $content .= ' ' . l('logout', 'user/logout') . ' ' . l('my account', 'user/edit');
    }

    $block['user'] = array(
      'region' => 'header',
      'content' => $content,
      'visibility' => BLOCK_VISIBILITY_NOTLISTED,
      'pages' => array('user/login', 'user/register'),
    );

    return $block;
  }

/* Private route controllers **************************************************/

  /**
   * Route controller to view all users.
   *
   * @return string
   */
  public function list_users() {
    library_load('stupidtable');
    add_js('$(function(){$(".stupidtable").stupidtable()});', 'inline');

    $users  = $this->get_all_users();
    $header = array(
      array('data' => 'UID',          'data-sort' => 'int'),
      array('data' => 'User Name',    'data-sort' => 'string'),
      array('data' => 'Email',        'data-sort' => 'string'),
      array('data' => 'Date Created', 'data-sort' => 'int'),
      array('data' => 'Last Login',   'data-sort' => 'int'),
      array('data' => 'Last IP',      'data-sort' => 'string'),
      array('data' => 'Activated',    'data-sort' => 'int'),
      array('data' => 'Blocked',      'data-sort' => 'int'),
      array('data' => 'Role',         'data-sort' => 'string'),
      array('data' => 'actions',      'colspan' => 2),
    );
    $rows   = array();
    foreach ($users as $user) {
      $rows[] = array(
        $user->uid,
        $user->username,
        $user->email,
        date('H:i:s d-m-Y', $user->created),
        ($user->last_login) ? date('H:i:s d-m-Y', $user->last_login) : '-',
        $user->last_ip,
        $user->activated,
        $user->blocked,
        $user->role,
        l('edit', 'admin/user/edit/' . $user->uid),
        ($user->uid == 1) ? '' : l('delete', 'admin/user/delete/' . $user->uid),
      );
    }

    $ra = array(
      'template' => 'table',
      'vars'     => array(
        'caption' => count($users) . ' users',
        'header' => $header,
        'rows'   => $rows,
        'attributes' => array('class' => array('stupidtable')),
      ),
    );

    return get_theme()->theme_table($ra);
  }

  /**
   * @return string
   */
  public function add_user() {
    return get_module_form()->build('add_user_form');
  }

  /**
   * Route controller to add (register) a user.
   *
   * @return array
   */
  public function add_user_form() {
    $form['username'] = array(
      '#type'        => 'textfield',
      '#title'       => 'Username',
      '#description' => 'Your <em>username</em>.',
      '#required'    => TRUE,
      '#size'        => 32,
    );
    $minlength = variable_get('system_password_minlength', 8);
    $maxlength = variable_get('system_password_maxlength', 32);
    $form['password'] = array(
      '#type'        => 'password',
      '#title'       => 'Password',
      '#description' => "Your <em>password</em>.<br />Must be between $minlength and $maxlength characters.",
      '#required'    => TRUE,
      '#size'        => 32,
      '#minlength'   => $minlength,
      '#maxlength'   => $maxlength,
    );
    $form['email']    = array(
      '#type'        => 'email',
      '#title'       => 'Email',
      '#description' => 'Your <em>email address</em>.',
      '#required'    => TRUE,
      '#size'        => 32,
    );
    $form['role'] = array(
      '#type'          => 'select',
      '#title'         => 'Role',
//      '#default_value' => $user['role'],
      '#options'       => make_array_assoc(array('admin', 'member')),
      '#description'   => 'The assigned <em>role</em> of the user.',
      '#required'      => TRUE,
    );
    $form['activated'] = array(
      '#type'          => 'checkbox',
      '#title'         => 'Activated',
      '#default_value' => 0,
//      '#description'   => 'The assigned <em>role</em> of the user.',
    );
    $form['submit']   = array(
      '#type' => 'submit',
    );

    return $form;
  }

  /**
   * @param array $form
   * @param array $form_values
   * @param array $form_errors
   */
  public function add_user_form_validate(array &$form, array $form_values, array &$form_errors) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;

    $this->validate_email($form_values, $form_errors);
    if (!$form_errors) {
      $user = $this->get_user_by_username($form_values['username']);
      if ($user) {
        $form_errors['username'] = 'Username is already registered.';
      }
      $user = $this->get_user_by_email($form_values['email']);
      if ($user) {
        $form_errors['email'] = 'Address is already registered.';
      }
      if ($form_values['password'] == $form_values['username']) {
        $form_errors['email'] = 'Password cannot be the same as username.';
      }
    }
  }

  /**
   * @param array $form
   * @param array $form_values
   */
  public function add_user_form_submit(array &$form, array $form_values) {
    $this->add($form_values['username'], $form_values['password'], $form_values['email']);
    set_message('User registered.');
    $form['#redirect'] = 'admin/users';
  }

  /**
   * @param $uid
   * @return array
   */
  public function edit($uid) {
    add_css($this->get_path() . 'css/edit.css');
    $username = $this->get_user_by_uid($uid)->username;

    return array(
      'content_title' => "Edit user <em>$username</em>",
      'content' =>  get_module_form()->build('edit_form', $uid),
    );
  }

  /**
   * Route controller to edit a user.
   *
   * @param array $form
   * @param array $form_values
   * @param array $form_errors
   * @param int   $uid
   * @return array
   */
  public function edit_form(array $form, array $form_values, array $form_errors, $uid) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_errors;

    $user = $this->get_user_by_uid($uid);

    $form = array(
//      '#attributes' => array('novalidate' => 'novalidate'),
    );

    $form['fs'] = array(
      '#type' => 'fieldset',
      '#title' => 'Edit User',
    );
    $form['fs']['username'] = array(
      '#type'  => 'value',
      '#value' => $user->username,
    );
    $minlength = variable_get('system_password_minlength', 8);
    $maxlength = variable_get('system_password_maxlength', 32);
    $form['fs']['password'] = array(
      '#type'          => 'password',
      '#title'         => 'Password',
      '#default_value' => '',
      '#description'   => "Must be between $minlength and $maxlength characters.<br />Leave empty to keep the same password.",
      '#size'          => 32,
      '#minlength'     => $minlength,
      '#maxlength'     => $maxlength,
    );
    $form['fs']['email'] = array(
      '#type'          => 'textfield',
      '#title'         => 'Email',
      '#default_value' => $user->email,
      '#description'   => '<em>Email address</em> of the user.',
      '#size'          => 32,
      '#required'      => TRUE,
      '#attributes'    => array('type' => 'email'),
    );

    if ($uid != 1) {
      $form['fs']['role'] = array(
        '#type'          => 'select',
        '#title'         => 'Role',
        '#default_value' => $user->role,
        '#options'       => make_array_assoc(array('admin', 'member')),
        '#description'   => 'The assigned <em>role</em> of the user.',
        '#required'      => TRUE,
      );
      $form['fs']['activated'] = array(
        '#type'          => 'checkbox',
        '#title'         => 'Activated',
        '#default_value' => $user->activated,
      );
      $form['fs']['blocked'] = array(
        '#type'          => 'checkbox',
        '#title'         => 'Blocked',
        '#default_value' => $user->blocked,
      );
    }

    $form['submit'] = array(
      '#type'   => 'submit',
      '#value'  => 'Update',
      '#suffix' => '<span class="cancel-submit">' . l('Cancel', 'admin/users') . '</span>',
    );

    return $form;
  }

  /**
   * @param array $form
   * @param array $form_values
   * @param array $form_errors
   * @param int   $uid
   */
  public function edit_form_validate(array &$form, array $form_values, array &$form_errors, $uid) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;

    $user = $this->get_user_by_uid($uid);

    if ($form_values['password'] && ($form_values['password'] == $user->username)) {
      $form_errors['email'] = 'Password cannot be the same as username.';
    }

    if ($form_values['email'] != $user->email) {
      $this->validate_email($form_values, $form_errors);
    }
  }

  /**
   * @param array $form
   * @param array $form_values
   * @param int   $uid
   */
  public function edit_form_submit(array &$form, array $form_values, $uid) {
    $password = ($form_values['password']) ? $form_values['password'] : NULL;
    if ($uid != 1) {
      $activated = $form_values['activated'];
      $blocked = $form_values['blocked'];
      $this->update($uid, $password, $form_values['email'], $activated, $blocked, $form_values['role']);
    } else {
      $this->update($uid, $password, $form_values['email']);
    }
    $name = $this->get_user_by_uid($uid)->username;
    set_message('User record for <em>' . $name . '</em> is updated.');
    $form['#redirect'] = 'admin/users';
  }

  /**
   * @param $uid
   * @return string
   */
  public function delete($uid) {
    $username = $this->get_user_by_uid($uid)->username;

    return array(
      'content_title' => "Delete user <em>$username</em>",
      'content' => get_module_form()->build('delete_form', $uid),
    );
  }

  /**
   * Delete a user.
   *
   * @param array $form
   * @param array $form_values
   * @param array $form_errors
   * @param       $uid
   * @return array
   */
  public function delete_form(array $form, array $form_values, array $form_errors, $uid) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_errors;

    $form = array();

    $name = $this->get_user_by_uid($uid)->username;
    $form['message'] = array(
      '#value' => '<p>This action can not be undone! Are you sure you want to delete user <em>' . $name . '</em>?</p>'
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Delete',
      '#suffix' => '<span class="cancel-submit">' . l('Cancel', 'admin/users') . '</span>',
    );

    return $form;
  }

  /**
   * @param array  $form
   * @param array  $form_values
   * @param string $uid
   */
  public function delete_form_submit(array &$form, array $form_values, $uid) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;

    db_delete('users')
      ->condition('uid', $uid)
      ->execute();

    set_message('User ' . $uid . ' is deleted.');

    $form['#redirect'] = 'admin/users';
  }

/* Public route controllers ***************************************************/

  /**
   * @return int
   */
  public function register_user() {
    if (get_user()->uid > 0) {
      return 'Please ' . l('logout', 'user/logout') . ' first.';
    }

    add_css($this->get_path() . 'css/register.css');
    return get_module_form()->build('register_user_form');
  }

  /**
   * Route controller for the user to register himself.
   *
   * @return string
   */
  public function register_user_form() {

    $form['#attributes'] = array('autocomplete' => 'off');

    $form['fs'] = array(
      '#type'        => 'fieldset',
      '#title'       => 'Register',
      '#description' => 'After submitting we send you a email with a confirmation link that you can use to activate your account.',
    );
    $form['fs']['username'] = array(
      '#type'        => 'textfield',
      '#title'       => 'Username',
      '#description' => 'Your <em>username</em>.',
      '#required'    => TRUE,
      '#size'        => 32,
      '#attributes'  => array('autocomplete' => 'on'),
    );

    $minlength = variable_get('system_password_minlength', 8);
    $maxlength = variable_get('system_password_maxlength', 32);
    $form['fs']['password'] = array(
      '#type'        => 'password',
      '#title'       => 'Password',
      '#description' => "Your <em>password</em>.<br />Must be between $minlength and $maxlength characters.",
      '#required'    => TRUE,
      '#size'        => 32,
      '#minlength'   => $minlength,
      '#maxlength'   => $maxlength,
    );
    $form['fs']['email']    = array(
      '#type'        => 'email',
      '#title'       => 'Email',
      '#description' => 'Your <em>email address</em>.',
      '#required'    => TRUE,
      '#size'        => 32,
      '#attributes'  => array('autocomplete' => 'on'),
    );

    $form['submit']   = array(
      '#type' => 'submit',
      '#value' => 'Register',
      '#suffix' => '<span class="cancel-submit">' . l('Cancel', '') . '</span>',
    );

    return $form;
  }

  /**
   * @param array $form
   * @param array $form_values
   * @param array $form_errors
   */
  public function register_user_form_validate(array &$form, array $form_values, array &$form_errors) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;

    $this->validate_email($form_values, $form_errors);
    if (!$form_errors) {
      $user = $this->get_user_by_username($form_values['username']);
      if ($user) {
        $form_errors['username'] = 'Username is not available. Please choose another one.';
      }
      $user = $this->get_user_by_email($form_values['email']);
      if ($user) {
        $form_errors['email'] = 'Email address is already registered. Please use another one.';
      }
      if ($form_values['password'] == $form_values['username']) {
        $form_errors['email'] = 'Password cannot be the same as username.';
      }
    }
  }

  /**
   * @param array $form
   * @param array $form_values
   * @return string
   */
  public function register_user_form_submit(array $form, array $form_values) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;

    $id = $this->add($form_values['username'], $form_values['password'], $form_values['email']);
    $mail = array(
      'to' => $form_values['email'],
    );
    $code = 'uid=' . $id . '&email=' . $form_values['email'];
    get_module_system()->mail_email_confirm($mail, $code);

    return 'A request for confirmation is sent, please check your email.';
  }

  /**
   * @param $code
   * @return array|string
   */
  public function email_confirm($code) {
    $code = base64_decode($code);
    if ($code !== FALSE) {
      parse_str($code, $tmp);
      if (isset($tmp['uid']) && isset($tmp['email'])) {
        if (filter_var($tmp['email'], FILTER_VALIDATE_EMAIL)) {
          /** @var \USER $user */
          $user = $this->get_user_by_uid((int)$tmp['uid']);
          if ($user) {
            if ($user->email == $tmp['email']) {
              if ($user->activated) {
                return array(
                  'page_title' => 'Activated',
                  'content' => 'This account was already activated. You can disregard the activation link and may login. Thank you.',
                );
              } else {
                $this->activate((int)$tmp['uid']);
                return array(
                  'page_title' => 'Activated',
                  'content' => 'This account is now activated, you may login. Thank you.',
                );
              }
            }
          }
        }
      }
    }

    return 'The confirmation code does not match, please try again.';
  }

  /**
   * @return int
   */
  public function edit_user() {
    add_css($this->get_path() . 'css/edit.css');

    return array(
      'page_title' => 'View/edit your account',
      'content' => get_module_form()->build('edit_user_form')
    );
  }

  /**
   * Route controller for the user to edit his registration info.
   *
   * @return array
   */
  public function edit_user_form() {
    $user = $this->get_loggedin_user();

    $form['uid'] = array(
      '#type'  => 'value',
      '#value' => $user->uid,
    );
    $form['username'] = array(
      '#type'  => 'value',
      '#value' => $user->username,
    );

    $form['fs'] = array(
      '#type'        => 'fieldset',
      '#title'       => 'Your Account',
    );

    $minlength = variable_get('system_password_minlength', 8);
    $maxlength = variable_get('system_password_maxlength', 32);
    $form['fs']['password'] = array(
      '#type'          => 'password',
      '#title'         => 'Password',
      '#description'   => "Must be between $minlength and $maxlength characters.<br />Leave empty to keep the same password.",
      '#size'          => 32,
      '#minlength'     => $minlength,
      '#maxlength'     => $maxlength,
    );
/*
    $form['fs']['email'] = array(
      '#type'          => 'textfield',
      '#title'         => 'Email',
      '#default_value' => $user->email,
      '#description'   => 'Your <em>email address</em>.',
      '#size'          => 32,
      '#required'      => TRUE,
      '#attributes'    => array('type' => 'email'),
    );
*/
    $form['submit'] = array(
      '#type'   => 'submit',
      '#value'  => 'Update',
      '#suffix' => '<span class="cancel-submit">' . l('Cancel', '') . '</span>',
    );

    return $form;
  }

  /**
   * @param array $form
   * @param array $form_values
   * @param array $form_errors
   */
  public function edit_user_form_validate(array &$form, array $form_values, array &$form_errors) {
    if ($form_values['password'] && ($form_values['password'] == $form['username']['#value'])) {
      $form_errors['email'] = 'Password cannot be the same as username.';
    }
  }

  /**
   * Submit handler.
   *
   * @param array $form
   * @param array $form_values
   */
  public function edit_user_form_submit(array &$form, array $form_values) {
    $password = ($form_values['password']) ? $form_values['password'] : NULL;
    if ($password) {
      $this->update($form['uid']['#value'], $password);
      set_message('Your account is updated.');
    } else {
      set_message('There was nothing that needed a update.');
    }
    $form['#redirect'] = '';
  }

  /**
   * @return string
   */
  public function login() {
    if (get_user()->uid > 0) {
      return 'Please ' . l('logout', 'user/logout') . ' first.';
    } else {
      add_css($this->get_path() . 'css/login.css');
      return get_module_form()->build('login_form');
    }
  }

  /**
   * Login.
   *
   * @return string
   */
  public function login_form() {

    $form = array(
//      '#attributes' => array('autocomplete' => 'off'),
    );

    $form['fs'] = array(
      '#type'  => 'fieldset',
      '#title' => 'Login',
    );
    $form['fs']['username'] = array(
      '#type'        => 'textfield',
      '#title'       => 'Username',
      '#description' => 'Your <em>username</em>.',
      '#required'    => TRUE,
      '#size'        => 32,
    );

    $forgot = (variable_get('system_maintenance', TRUE)) ? '' : l(' Forgot?', 'user/password_reset');
    $form['fs']['password'] = array(
      '#type'        => 'password',
      '#title'       => 'Password',
      '#description' => 'Your <em>password</em>.' . $forgot,
      '#required'    => TRUE,
      '#size'        => 32,
    );

    $form['submit']   = array(
      '#type'   => 'submit',
      '#value'  => 'Login',
      '#suffix' => '<span class="cancel-submit">' . l('Cancel', '') . ' or ' . l('register', 'user/register') . '</span>',
    );

    return $form;
  }

  /**
   * @param array $form
   * @param array $form_values
   * @param array $form_errors
   */
  public function login_form_validate(array &$form, array $form_values, array &$form_errors) {
    if (!$form_errors) {
      $uid = $this->is_login_valid($form_values['username'], $form_values['password']);
      if (!$uid) {
        $form_errors['username'] = 'Login failed. Please check your username and password.';
      } else {
        $form['uid'] = array('#type' => 'value', '#value' => $uid);
      }
    }
  }

  /**
   * @param array $form
   * @param array $form_values
   * @return array|null
   */
  public function login_form_submit(array &$form, array $form_values) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;

    if (!$this->is_activated($form['uid']['#value'])) {
      return array(
        'page_title' => 'Not Activated',
        'content' => 'This account is not activated because the email address is not confirmed, please check your email for a email confirmation link.',
      );
    }

    if ($this->is_blocked($form['uid']['#value'])) {
      return array(
        'page_title' => 'Blocked',
        'content' => 'This account is blocked, please contact the admin.',
      );
    }

    db_update('users')
      ->fields(array(
        'last_login' => time(),
        'last_ip' => $_SERVER['REMOTE_ADDR'],
      ))
      ->condition('uid', $form['uid']['#value'])
      ->execute();
    get_module_session()->login($form['uid']['#value']);

    $form['#redirect'] = '';

    return NULL;
  }

  /**
   *
   */
  public function password_reset() {
    return array(
      'page_title' => 'Password reset',
      'content' => get_module_form()->build('password_reset_form')
    );
  }

  /**
   * @return array
   */
  public function password_reset_form() {

    $form['fs'] = array(
      '#type'        => 'fieldset',
      '#title'       => 'Login',
      '#description' => 'After submitting the form we send you a new password.',
    );
    $form['fs']['email'] = array(
      '#type'          => 'email',
      '#title'         => 'Email',
      '#description'   => 'Your <em>email address</em>.',
      '#size'          => 32,
      '#required'      => TRUE,
    );

    $form['submit'] = array(
      '#type'   => 'submit',
      '#value'  => 'Reset Password',
      '#suffix' => '&nbsp;&nbsp;' . l('Cancel', ''),
    );

    return $form;
  }

  /**
   * @param array $form
   * @param array $form_values
   * @param array $form_errors
   */
  public function password_reset_form_validate(array &$form, array $form_values, array &$form_errors) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;

    $this->validate_email($form_values, $form_errors);
    if (!$form_errors) {
      $user = $this->get_user_by_email($form_values['email']);
      if (!$user) {
        $form_errors['email'] = 'Email address is unknown, please check your email address.';
      }
    }
  }

  /**
   * @param array $form
   * @param array $form_values
   */
  public function password_reset_form_submit(array &$form, array $form_values) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;

    $email = $form_values['email'];
    $password = generate_password();

    // Update the user record with a new password.
    $this->update($this->get_user_by_email($email)->uid, $password);

    // Send email with the new password to the user.
    get_module_system()->mail_password_reset(array('to' => $email), $password);
    set_message('Email has been send, please check your email and use your new password to login.');

    $form['#redirect'] = 'user/login';
  }

}

