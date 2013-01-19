<?php
// session.class.php

namespace core\modules\session;

use core\modules\session\mysession53;
use core\modules\session\mysession54;

/**
 * @author Cornelis Brouwers <cornelis_brouwers@hotmail.com>
 */
class session
{
  private static $flashvars = array();
  private static $forms = NULL;
  private static $form_data = array();
  private static $form_info = array();
  /** @var object $mysession */
  private static $mysession = NULL;

  /**
   * The schema definition.
   */
  public function schema() {
    $schema['session'] = array(
      'fields' => array(
        'sid' => array(
          'type' => 'varchar',
          'length' => 255,
          'unique' => TRUE,
          'not null' => TRUE,
        ),
        'created' => array(
          'type' => 'integer',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ),
        'data' => array(
          'type' => 'text',
          'not null' => TRUE,
        ),
      ),
      'primary key' => array('sid'),
    );

    return $schema;
  }

  /**
   *
   */
  public static function start() {
    if (db_table_exists('session')) {
      if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
        self::$mysession = new mysession54();
        /** @noinspection PhpParamsInspection */
        session_set_save_handler(self::$mysession);
      } else {
        self::$mysession = new mysession53();
        session_set_save_handler(
          array(self::$mysession, 'open'),
          array(self::$mysession, 'close'),
          array(self::$mysession, 'read'),
          array(self::$mysession, 'write'),
          array(self::$mysession, 'destroy'),
          array(self::$mysession, 'gc')
        );
      }
    }
    session_start();
    if (isset($_SESSION['flashvars'])) {
      self::$flashvars = $_SESSION['flashvars'];
      if (isset($_SESSION['keepflashvars'])) {
        unset($_SESSION['keepflashvars']);
      } else {
        unset($_SESSION['flashvars']);
      }
    }

    if (isset($_POST['form_key'])) {
      if (isset($_SESSION['forms'])) {
        if (isset($_POST['form_id']) && isset($_POST['form_key']) &&
           ($_SESSION['forms'][$_POST['form_id']]['key'] == $_POST['form_key'])) {
          self::$forms = $_SESSION['forms'];
          self::$form_data = $_POST;
          self::$form_info = $_SESSION['forms'][$_POST['form_id']];
        }
        else {
          set_message(__LINE__ . ': Possible form hijack!', 'error');
        }
        unset($_POST);
      }
    }
    unset($_SESSION['forms']);
  }

  /**
   * @param string $message
   * @param string $status
   */
  public function set_message($message, $status = 'status') {
    $_SESSION['flashvars'][$status][] = $message;
  }

  /**
   * @param string $status [optional]
   * @return array|null
   */
  public function get_messages($status = NULL) {
    if (!$status) {
      return (isset($_SESSION['flashvars'])) ? $_SESSION['flashvars'] : NULL;
    }
    else {
      return (isset($_SESSION['flashvars'][$status])) ? $_SESSION['flashvars'][$status] : NULL;
    }
  }

  /**
   * @param array $form_info
   */
  public function register_form(array $form_info) {
    $_SESSION['forms'][$form_info['id']] = $form_info;
  }

  /**
   * @return array
   */
  public function get_form_data() {
    return self::$form_data;
  }

  /**
   * @return array
   */
  public function get_form_info() {
    return self::$form_info;
  }

  /**
   * @param int $uid
   */
  public function login($uid) {
    $_SESSION['uid'] = $uid;
  }

  /**
   *
   */
  public function logout() {
    // Unset all of the session variables.
    $_SESSION = array();

    // If it's desired to kill the session, also delete the session cookie.
    // Note: This will destroy the session, and not just the session data!
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finally, destroy the session.
    session_destroy();

    go_to('');
  }

  /* Hooks ********************************************************************/

  /**
   * @return array
   */
  public function menu() {
    $menu['admin/sessions'] = array(
      'title' => 'Sessions',
      'controller' => 'session:sessions',
      'access_arguments' => 'admin',
      'menu_name' => 'system',
    );

    return $menu;
  }

  /* Private routes ***********************************************************/

  /**
   * @return string
   */
  public function sessions() {
    /** @var \SESSION[] $sessions */
    $sessions = db_select('session')->field('*')->execute()->fetchAll(\PDO::FETCH_OBJ);

    $header = array(
      'sid',
      'created',
      'data',
    );

    $rows = array();
    foreach ($sessions as $session) {
      $rows[] = array(
        $session->sid,
        date('H:i:s d-m-Y', $session->created),
        $session->data,
      );
    }

    $ra = array(
      'template' => 'table',
      'vars'     => array(
        'caption' => count($sessions) . ' sessions',
        'header'  => $header,
        'rows'    => $rows,
      ),
    );

    return get_theme()->theme_table($ra);
  }

}
