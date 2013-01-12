<?php

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
          'unsigned' => TRUE,
          'not null' => TRUE
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
        'role'      => array(
          'type'     => 'varchar',
          'length'   => 255,
          'not null' => TRUE
        ),
      ),
//        'primary key' => array('uid'),
    );
// todo: move to install
// $this->add('admin', 'admin000', 'admin@localmail.com', 1, 'admin');

    return $schema;
  }

  /**
   * Get the current logged in user or the guest account.
   *
   * @return \USER Returns the current loggedin user object or the guest account.
   */
  public function get_loggedin_user() {
    if (isset($_SESSION['uid'])) {
      $this->user = $this->get_user_by_uid($_SESSION['uid']);
    }
    else {
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
   */
  private function add($username, $password, $email, $activated = 0, $role = 'member') {
    if (variable_get('system_password_crypt', TRUE)) {
      $password = crypt($password);
    }
    db_insert('users')->fields(array(
      'username'  => $username,
      'password'  => $password,
      'email'     => $email,
      'created'   => time(),
      'ip'        => $_SERVER['REMOTE_ADDR'],
      'last_ip'   => $_SERVER['REMOTE_ADDR'],
      'activated' => $activated,
      'role'      => $role,
    ))->execute();
  }

  /**
   * Update a user account.
   *
   * @param int $uid
   * @param string $password
   * @param string $email
   * @param int $activated
   * @param string $role
   */
  private function update($uid, $password = NULL, $email = NULL, $activated = NULL, $role = NULL) {

    $db = db_update('users');
    if ($password  !== NULL) {
      if (variable_get('system_password_crypt', TRUE)) {
        $db->fields(array('password'  => crypt($password)));
      } else {
        $db->fields(array('password'  => $password));
      }
    }
    if ($email     !== NULL) $db->fields(array('email'     => $email));
    if ($activated !== NULL) $db->fields(array('activated' => $activated));
    if ($role      !== NULL) $db->fields(array('role'      => $role));
    $db->condition('uid', $uid)->execute();
  }

  /**
   * Get all users.
   *
   * @return \USER[] Returns a array of user objects.
   */
  private function get_all_users() {
    $users = db_select('users')->field('*')->execute()
      ->fetchAll(\PDO::FETCH_OBJ);

    return $users;
  }

  /**
   * @param int $uid
   * @return \USER|bool Returns a user object or FALSE if username doesn't exists.
   */
  private function get_user_by_uid($uid) {
    $user = db_select('users')->field('*')->condition('uid', $uid)->execute()
      ->fetch(\PDO::FETCH_OBJ);

    return $user;
  }

  /**
   * @param string $email
   * @return \USER|bool Returns a user object or FALSE if username doesn't exists.
   */
  private function get_user_by_email($email) {
    $user = db_select('users')->field('*')->condition('email', $email)->execute()
      ->fetch(\PDO::FETCH_OBJ);

    return $user;
  }

  /**
   * @param string $username
   * @return \USER|bool Returns a user object or FALSE if username doesn't exists.
   */
  private function get_user_by_username($username) {
    $user = db_select('users')->field('*')->condition('username', $username)->execute()
      ->fetch(\PDO::FETCH_OBJ);

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
    $user = $this->get_user_by_username($username);
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


/* Hooks **********************************************************************/

  /**
   * hook init()
   */
  public function init() {
    add_css($this->get_path() . '/user.css', array('weight' => 1));
  }

  /**
   * hook menu()
   *
   * @return array
   */
  public function menu() {
    $menu['/admin/users'] = array(
      'title'            => 'Users',
      'controller'       => 'user:list_users',
      'access_arguments' => 'admin',
      'type'             => MENU_NORMAL_ITEM,
      'menu_name'        => 'system',
    );
    $menu['/admin/users/edit/{uid}'] = array(
      'title'            => 'Edit user',
      'controller'       => 'user:edit',
      'access_arguments' => 'admin',
      'type'             => MENU_CALLBACK,
    );

    $menu['/user/login'] = array(
      'title'            => 'Login',
      'controller'       => 'user:login',
      'type'             => MENU_CALLBACK,
    );
    $menu['/user/logout'] = array(
      'title'            => 'Logout',
      'controller'       => 'session:logout',
      'type'             => MENU_CALLBACK,
    );
    $menu['/user/register'] = array(
      'title'            => 'Register',
      'controller'       => 'user:register_user',
      'type'             => MENU_CALLBACK,
    );
    $menu['/user/edit'] = array(
      'title'            => ' My account',
      'controller'       => 'user:edit_user',
      'type'             => MENU_CALLBACK,
    );
    $menu['/user/password_reset'] = array(
      'title'            => 'Password reset',
      'controller'       => 'user:password_reset',
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
    $user = $this->get_loggedin_user();
    $content = 'Welcome ' . $user->username;
    if ($user->uid == 0) {
      $content .= ': ' . l('login', '/user/login') . ' or ' . l('register', '/user/register');
    } else {
      $content .= ': ' . l('logout', '/user/logout') . ' | ' . l('my account', '/user/edit');
    }

    $block['user'] = array(
      'region' => 'header',
      'content' => $content,
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
        $user->role,
        l('edit', '/admin/users/edit/' . $user->uid),
        ($user->uid == 1) ? '' : l('delete', '/admin/users/delete/' . $user->uid),
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
   * Route controller to add (register) a user.
   *
   * @return string
   */
  public function add_user() {
    $data['username'] = array(
      '#type'        => 'textfield',
      '#title'       => 'Username',
      '#description' => 'Your <em>username</em>.',
      '#required'    => TRUE,
      '#size'        => 32,
    );
    $minlength = variable_get('system_password_minlength', 8);
    $maxlength = variable_get('system_password_maxlength', 32);
    $data['password'] = array(
      '#type'        => 'password',
      '#title'       => 'Password',
      '#description' => "Your <em>password</em>.<br />Must be between $minlength and $maxlength characters.",
      '#required'    => TRUE,
      '#size'        => 32,
      '#minlength'   => $minlength,
      '#maxlength'   => $maxlength,
    );
    $data['email']    = array(
      '#type'        => 'email',
      '#title'       => 'Email',
      '#description' => 'Your <em>email address</em>.',
      '#required'    => TRUE,
      '#size'        => 32,
    );
    $data['role'] = array(
      '#type'          => 'select',
      '#title'         => 'Role',
//      '#default_value' => $user['role'],
      '#options'       => make_array_assoc(array('admin', 'member')),
      '#description'   => 'The assigned <em>role</em> of the user.',
      '#required'      => TRUE,
    );
    $data['activated'] = array(
      '#type'          => 'checkbox',
      '#title'         => 'Activated',
      '#default_value' => 0,
//      '#description'   => 'The assigned <em>role</em> of the user.',
    );
    $data['submit']   = array(
      '#type' => 'submit',
    );

    return get_module_form()->build($data);
  }

  /**
   * @param array $form
   * @param array $form_values
   * @param array $form_errors
   */
  public function add_user_validate(array &$form, array $form_values, array &$form_errors) {
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
  public function add_user_submit(array &$form, array $form_values) {
    $this->add($form_values['username'], $form_values['password'], $form_values['email']);
    set_message('User registered.');
    $form['#redirect'] = 'admin/users';
  }

  /**
   * Route controller to edit a user.
   *
   * @param int $uid
   * @return string
   */
  public function edit($uid) {
    $user = $this->get_user_by_uid($uid);
    /*
        $data['username'] = array(
          '#type' => 'textfield',
          '#title' => 'Username',
          '#default_value' => $user['username'],
    //      '#description' => 'Your <em>username</em>.',
          '#required' => TRUE,
          '#size' => 32,
        ];
    //*/
    $data['uid'] = array(
      '#type'  => 'value',
      '#value' => $uid,
    );
    $data['username'] = array(
      '#type'  => 'value',
      '#value' => $user->username,
    );
    $minlength = variable_get('system_password_minlength', 8);
    $maxlength = variable_get('system_password_maxlength', 32);
    $data['password'] = array(
      '#type'          => 'password',
      '#title'         => 'Password',
      '#default_value' => $user->password,
      '#description'   => "Must be between $minlength and $maxlength characters.<br />Leave empty to keep the same password.",
      '#size'          => 32,
      '#minlength'     => $minlength,
      '#maxlength'     => $maxlength,
    );
    $data['email'] = array(
      '#type'          => 'textfield',
      '#title'         => 'Email',
      '#default_value' => $user->email,
      '#description'   => '<em>Email address</em> of the user.',
      '#size'          => 32,
      '#required'      => TRUE,
      '#attributes'    => array('type' => 'email'),
    );
    $data['role'] = array(
      '#type'          => 'select',
      '#title'         => 'Role',
      '#default_value' => $user->role,
      '#options'       => make_array_assoc(array('admin', 'member')),
      '#description'   => 'The assigned <em>role</em> of the user.',
      '#required'      => TRUE,
    );
    $data['activated'] = array(
      '#type'          => 'checkbox',
      '#title'         => 'Activated',
      '#default_value' => $user->activated,
//      '#description'   => 'The assigned <em>role</em> of the user.',
    );
    $data['submit'] = array(
      '#type'   => 'submit',
      '#value'  => 'Update',
      '#suffix' => '&nbsp;&nbsp;' . l('Cancel', 'admin/users'),
    );

    return array(
      'page_title' => "Edit user <em>{$user->username}</em>",
      'content' => get_module_form()->build($data),
    );
  }

  /**
   * @param array $form
   * @param array $form_values
   * @param array $form_errors
   */
  public function edit_validate(array &$form, array $form_values, array &$form_errors) {
    $this->validate_email($form_values, $form_errors);
    if (!$form_errors) {
      $user = $this->get_user_by_email($form_values['email']);
      if ($user && ($user->username != $form['username']['#value'])) {
        $form_errors['email'] = 'Email address is already registered.';
      }
      if ($form_values['password'] && ($form_values['password'] == $user->username)) {
        $form_errors['email'] = 'Password cannot be the same as username.';
      }
    }
  }

  /**
   * @param array $form
   * @param array $form_values
   */
  public function edit_submit(array &$form, array $form_values) {
    $password = ($form_values['password']) ? $form_values['password'] : NULL;
    $activated = (isset($form_values['activated'])) ? 1 : 0;
    $this->update($form['uid']['#value'], $password, $form_values['email'], $activated, $form_values['role']);

    set_message('User record is updated.');
    $form['#redirect'] = 'admin/users';
  }




/* Public route controllers ***************************************************/

  /**
   * Route controller for the user to register himself.
   *
   * @return string
   */
  public function register_user() {
    $data['username'] = array(
      '#type'        => 'textfield',
      '#title'       => 'Username',
      '#description' => 'Your <em>username</em>.',
      '#required'    => TRUE,
      '#size'        => 32,
    );
    $minlength = variable_get('system_password_minlength', 8);
    $maxlength = variable_get('system_password_maxlength', 32);
    $data['password'] = array(
      '#type'        => 'password',
      '#title'       => 'Password',
      '#description' => "Your <em>password</em>.<br />Must be between $minlength and $maxlength characters.",
      '#required'    => TRUE,
      '#size'        => 32,
      '#minlength'   => $minlength,
      '#maxlength'   => $maxlength,
    );
    $data['email']    = array(
      '#type'        => 'email',
      '#title'       => 'Email',
      '#description' => 'Your <em>email address</em>.',
      '#required'    => TRUE,
      '#size'        => 32,
    );
    $data['submit']   = array(
      '#type' => 'submit',
      '#suffix' => '&nbsp;&nbsp;' . l('Cancel', '/'),
    );

    return get_module_form()->build($data);
  }

  /**
   * @param array $form
   * @param array $form_values
   * @param array $form_errors
   */
  public function register_user_validate(array &$form, array $form_values, array &$form_errors) {
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
  public function register_user_submit(array &$form, array $form_values) {
    $this->add($form_values['username'], $form_values['password'], $form_values['email']);
    set_message('User registered.');
    $form['#redirect'] = '/';
  }

  /**
   * Route controller for the user to edit his registration info.
   *
   * @return string
   */
  public function edit_user() {
    $user = $this->get_loggedin_user();

    $data['uid'] = array(
      '#type'  => 'value',
      '#value' => $user->uid,
    );
    $data['username'] = array(
      '#type'  => 'value',
      '#value' => $user->username,
    );
    $minlength = variable_get('system_password_minlength', 8);
    $maxlength = variable_get('system_password_maxlength', 32);
    $data['password'] = array(
      '#type'          => 'password',
      '#title'         => 'Password',
      '#default_value' => $user->password,
      '#description'   => "Must be between $minlength and $maxlength characters.<br />Leave empty to keep the same password.",
      '#size'          => 32,
      '#minlength'     => $minlength,
      '#maxlength'     => $maxlength,
    );
    $data['email'] = array(
      '#type'          => 'textfield',
      '#title'         => 'Email',
      '#default_value' => $user->email,
      '#description'   => 'Your <em>email address</em>.',
      '#size'          => 32,
      '#required'      => TRUE,
      '#attributes'    => array('type' => 'email'),
    );
    $data['submit'] = array(
      '#type'   => 'submit',
      '#value'  => 'Update',
      '#suffix' => '&nbsp;&nbsp;' . l('Cancel', '/home'),
    );

    return array(
      'page_title' => 'View/edit your account',
      'content' => get_module_form()->build($data)
    );
  }

  /**
   * @param array $form
   * @param array $form_values
   * @param array $form_errors
   */
  public function edit_user_validate(array &$form, array $form_values, array &$form_errors) {
    $this->validate_email($form_values, $form_errors);
    if (!$form_errors) {
      $user = $this->get_user_by_email($form_values['email']);
      if ($user && ($user['username'] != $form['username']['#value'])) {
        $form_errors['email'] = 'Email address is already registered.';
      }
      if ($form_values['password'] && ($form_values['password'] == $user['username'])) {
        $form_errors['email'] = 'Password cannot be the same as username.';
      }
    }
  }

  /**
   * Submit handler.
   *
   * @param array $form
   * @param array $form_values
   */
  public function edit_user_submit(array &$form, array $form_values) {
    $password = ($form_values['password']) ? $form_values['password'] : NULL;
    $activated = (isset($form_values['activated'])) ? 1 : 0;
    $this->update($form['uid']['#value'], $password, $form_values['email'], $activated, $form_values['role']);

    set_message('User record is updated.');
    $form['#redirect'] = '/home';
  }

  /**
   * Login.
   *
   * @return string
   */
  public function login() {
    $data['username'] = array(
      '#type'        => 'textfield',
      '#title'       => 'Username',
      '#description' => 'Your <em>username</em>.',
      '#required'    => TRUE,
      '#size'        => 32,
    );
    $forgot = (variable_get('system_maintenance', TRUE)) ? '' : l(' Forgot?', '/user/password_reset');
    $data['password'] = array(
      '#type'        => 'password',
      '#title'       => 'Password',
      '#description' => 'Your <em>password</em>.' . $forgot,
      '#required'    => TRUE,
      '#size'        => 32,
    );
    $data['submit']   = array(
      '#type' => 'submit',
      '#suffix' => '&nbsp;&nbsp;' . l('Cancel', '/'),
    );

    return get_module_form()->build($data);
  }

  /**
   * @param array $form
   * @param array $form_values
   * @param array $form_errors
   */
  public function login_validate(array &$form, array $form_values, array &$form_errors) {
    if (!$form_errors) {
      $uid = $this->is_login_valid($form_values['username'], $form_values['password']);
      if (!$uid) {
        $form_errors['username'] = 'Login failed. Please check your username and password.';
      }
      else {
        $form['uid'] = array('#type' => 'value', '#value' => $uid);
      }
    }
  }

  /**
   * @param array $form
   * @param array $form_values
   */
  public function login_submit(array &$form, array $form_values) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;

    db_update('users')
      ->fields(array(
        'last_login' => time(),
        'last_ip' => $_SERVER['REMOTE_ADDR'],
      ))
      ->condition('uid', $form['uid']['#value'])
      ->execute();
    get_module_session()->login($form['uid']['#value']);
  }

  /**
   * @return array
   */
  public function password_reset() {
    $data['description'] = array(
      '#value' => 'After submitting the form we send you a new password.',
    );
    $data['email'] = array(
      '#type'          => 'email',
      '#title'         => 'Email',
//      '#default_value' => '',
      '#description'   => 'Your <em>email address</em>.',
      '#size'          => 32,
      '#required'      => TRUE,
//      '#attributes'    => array('type' => 'email'],
    );
    $data['submit'] = array(
      '#type'   => 'submit',
      '#value'  => 'Submit',
      '#suffix' => '&nbsp;&nbsp;' . l('Cancel', '/'),
    );

    return array(
      'page_title' => 'Password reset',
      'content' => get_module_form()->build($data)
    );
  }

  /**
   * @param array $form
   * @param array $form_values
   * @param array $form_errors
   */
  public function password_reset_validate(array &$form, array $form_values, array &$form_errors) {
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
  public function password_reset_submit(array &$form, array $form_values) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;

    get_module_system()->mail_password_reset(array('to' => $form_values['email']));
    set_message('Email has been send.');
  }
}

