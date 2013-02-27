<?php
/**
 * @file system.class.php
 *
 * @author Cornelis Brouwers <cornelis_brouwers@hotmail.com>
 */

namespace core\modules\system;

use core\modules\config\config;
use core\modules\core_module;

/**
 *
 */
class system extends core_module {

  private $variables = array();

  /**
   * Cache all variables.
   */
  public function __construct() {
    if (!db_is_active() || !db_table_exists('variable')) {
      return;
    }

    $rows = db_select('variable')
      ->field('*')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
      $this->variables[$row['name']] = unserialize($row['value']);
    }
  }

  /**
   * Implements hook schema().
   *
   * @return array
   */
  public function schema() {
    $schema['variable'] = array(
      'description' => 'The variable table.',
      'fields' => array(
        'name' => array(
          'type'     => 'varchar',
          'length'   => 255,
          'not null' => TRUE
        ),
        'value' => array(
          'type'     => 'text',
          'not null' => TRUE,
        ),
      ),
      'primary key' => array('name'),
    );

    return $schema;
  }

  /**
   * Implements hook install().
   */
  private function install() {
    db_install_schema($this->schema());
  }

  /**
   * @param string $name
   * @param null   $default
   * @return mixed
   */
  public function variable_get($name, $default = NULL) {
    return isset($this->variables[$name]) ? $this->variables[$name] : $default;
  }

  /**
   * @param string $name
   * @param mixed  $value
   */
  public function variable_set($name, $value) {
    $exists = array_key_exists($name, $this->variables);
    $this->variables[$name] = $value;

    // Serialize all values.
    $value = serialize($value);

    if ($exists) {
      db_update('variable')
        ->fields(array('value' => $value))
        ->condition('name', $name)
        ->execute();
    } else {
      db_insert('variable')
        ->fields(array('name' => $name, 'value' => $value))
        ->execute();
    }
  }

  /**
   * @param string $name
   */
  public function variable_del($name) {
    if (array_key_exists($name, $this->variables)) {
      unset($this->variables[$name]);

      db_delete('variable')
        ->condition('name', $name)
        ->execute();
    }
  }


  /* Email functions **********************************************************/

  /**
   * Attempts to send a email.
   *
   * mail:
   *    [from]      string - Default is the site name and email.
   *    [to]
   *    [subject]
   *    [body]
   *    [html]      bool - Default is TRUE.
   *
   * @param array $mail
   * @return bool
   */
  private function mail_send(array $mail) {
    $mail += array(
      'from' => variable_get('system_email', ''),
      'html' => TRUE,
      'action_function' => NULL,
    );

    if ($mail['from'] == '') {
      set_message('Cannot send mail because email <em>from</em> is empty.', 'error');
      return FALSE;
    }

    $headers = '';
    $headers .= 'From: ' . $mail['from'] . "\r\n";
    $headers .= 'To: ' . $mail['to'] . "\r\n";
    $headers .= 'Return-Path: ' . $mail['from'] . "\r\n";
    $headers .= 'MIME-Version: 1.0' ."\r\n";
    $headers .= 'Content-Type: text/html; charset=utf-8' . "\r\n";
    $headers .= 'Content-Transfer-Encoding: 8bit'. "\r\n";

    $b = mail($mail['to'], $mail['subject'], $mail['body'], $headers);

    return $b;
  }

  /**
   * @param array  $mail
   * @param string $password
   * @return bool
   */
  public function mail_password_reset(array $mail, $password) {
    $body = file_get_contents($this->get_dir() . 'mails/password_reset.html');
    $body = str_replace('[PASSWORD]', $password, $body);

//    $from = variable_get('system_email');

    $mail += array(
//      'from' => $from,
      'subject' => 'Password reset',
      'body' => $body,
    );

    return $this->mail_send($mail);
  }

  /**
   * @param array  $mail
   * @param string $code
   * @return bool
   */
  public function mail_email_confirm(array $mail, $code) {
    $body = file_get_contents($this->get_dir() . 'mails/email_confirm.html');

    $link = BASE_URL . '/user/email_confirm/' . base64_encode($code);
    $link = l($link, $link);

    $body = str_replace('[LINK]', $link, $body);

    $from = variable_get('system_email');

    $mail += array(
      'from' => $from,
      'subject' => 'Email confirm',
      'body' => $body,
    );

    return $this->mail_send($mail);
  }

/* Hooks **********************************************************************/

  /**
   * Implements hook init();
   */
  public function init() {
    add_js(array(
      'basePath' => BASE_PATH,
      'libraryPath' => LIBRARY_PATH,
    ), 'setting');
    add_js($this->get_path() . 'js/cms.js');
  }

  /**
   * Implements hook menu().
   *
   * @return array
   */
  public function menu() {
    $menu['admin/theme/clear_cache'] = array(
      'title' => 'Clear theme cache',
      'controller' => 'system::clear_cache',
      'access_arguments' => 'admin',
      'menu_name' => 'system',
    );
    $menu['admin/mail'] = array(
      'title' => 'Mail',
      'controller' => 'system::mail',
      'access_arguments' => 'admin',
      'menu_name' => 'system',
    );
    $menu['admin/phpinfo'] = array(
      'title' => 'PHP Info',
      'controller' => 'system::phpinfo',
      'access_arguments' => 'admin',
      'menu_name' => 'system',
    );
    $menu['admin/setup'] = array(
      'title' => 'Setup',
      'controller' => 'system::setup',
      'access_arguments' => 'admin',
      'menu_name' => 'system',
    );

    $menu['admin/variables'] = array(
      'title' => 'List Variables',
      'controller' => 'system::variables',
      'access_arguments' => 'admin',
      'menu_name' => 'system',
    );
    $menu['admin/variable/delete/{name}'] = array(
      'title' => 'Delete variable',
      'controller' => 'system::variable_delete',
      'access_arguments' => 'admin',
      'type' => MENU_CALLBACK,
    );

    return $menu;
  }

  /**
   * Implements hook block().
   *
   * @return array
   */
  public function block() {
    $block = array();
    // Put the navigation menu somewhere.
    $menu = get_module_menu()->get_menu('navigation');
    if ($menu) {
      $vars['menu']['title'] = 'Navigation menu';
      $vars['menu']['links'] = array();
      $vars['menu']['attributes'] = build_attribute_string(array('class' => array('menu', 'vertical')));
      foreach ($menu as $link) {
//        $vars['menu']['links'][] = array(
//          'title'      => $link['title'],
//          'path'       => BASE_PATH . $link['path'],
//        );
        $vars['menu']['links'][] = l($link['title'], $link['path']);
      }

      $block['navigation_menu'] = array(
        'title' => 'Navigation menu',
        'template' => 'menu',
        'vars' => $vars,
        'region' => 'sidebar_first',
      );
    }

    if (get_user()->uid == 1) {
      // Put the admin menu somewhere.
      $menu = get_module_menu()->get_menu('system');
      $vars['menu']['title'] = 'Admin menu';
      $vars['menu']['links'] = array();
      $vars['menu']['attributes'] = build_attribute_string(array('class' => array('menu', 'vertical')));
      foreach ($menu as $link) {
//        $vars['menu']['links'][] = array(
//          'title'      => $link['title'],
//          'path'       => BASE_PATH . $link['path'],
//        );
        $vars['menu']['links'][] = l($link['title'], $link['path']);
      }

      $block['admin_menu'] = array(
        'title' => 'Admin menu',
        'template' => 'menu',
        'vars' => $vars,
        'region' => 'sidebar_second',
      );
    }

    // Put the main menu in the header.
    $menu = get_module_menu()->get_menu('main');
    if ($menu) {
      $vars['menu']['title'] = 'Main menu';
      $vars['menu']['links'] = array();
      $vars['menu']['attributes'] = build_attribute_string(array('class' => array('menu', 'horizontal')));
      foreach ($menu as $link) {
//        $vars['menu']['links'][] = array(
//          'title'      => $link['title'],
//          'path'       => BASE_PATH . $link['path'],
//        );
        $vars['menu']['links'][] = l($link['title'], $link['path']);
      }

      $block['main_menu'] = array(
  //      'title' => 'Main menu',
        'template' => 'menu',
        'vars' => $vars,
        'region' => 'header',
      );
    }

    return $block;
  }

  /**
   * Implements hook page_alter().
   *
   * @param array $page The final render array.
   */
  public function page_render(array &$page) {
    $page['footer_bottom'] =  '<span id="footer-left">&copy;2013 3dflat, all rights reserved.</span>';
    $page['footer_bottom'] .= '<span id="footer-center">';
    $page['footer_bottom'] .= 'Page build time: ' . number_format(microtime(TRUE) - START_TIME, 3);
    $page['footer_bottom'] .= ' Memory usage: ' . number_format(memory_get_usage());
    $page['footer_bottom'] .= ' Memory peak usage: ' . number_format(memory_get_peak_usage());
    $page['footer_bottom'] .= '</span>';
    $page['footer_bottom'] .= '<span id="footer-right">&#097;&#100;&#109;&#105;&#110;&#064;&#051;&#100;&#102;&#108;&#097;&#116;&#046;&#116;&#107;</span>';
  }

  /* Private routes ************************************************************/

  public function clear_cache() {
    get_theme()->clear_cache();
    set_message('Theme cache cleared.');
  }

  /**
   * @return string
   */
  public function mail() {
    return get_module_form()->build('mail_form');
  }

  /**
   * @return string
   */
  public function mail_form() {
    $form['to'] = array(
      '#type' => 'textfield',
      '#title' => 'To',
      '#required' => TRUE,
    );
    $form['from'] = array(
      '#type' => 'textfield',
      '#title' => 'From',
      '#default_value' => variable_get('system_sitename', '') . ' <' . variable_get('system_email', '') . '>',
      '#required' => TRUE,
    );
    $form['subject'] = array(
      '#type' => 'textfield',
      '#title' => 'Subject',
      '#required' => TRUE,
    );
    $form['message'] = array(
      '#type' => 'textarea',
      '#title' => 'Message',
      '#required' => TRUE,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Send',
    );

    return $form;
  }

  /**
   * @param array $form
   * @param array $form_values
   * @param array $form_errors
   */
  public function mail_form_validate(array $form, array $form_values, array &$form_errors) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;

    if ($form_values['to'] == '') {
      $form_errors['to'] = 'Field <em>To</em> cannot be empty.';
    }
    if ($form_values['from'] == '') {
      $form_errors['from'] = 'Field <em>From</em> cannot be empty.';
    }
    if ($form_values['subject'] == '') {
      $form_errors['subject'] = 'Field <em>Subject</em> cannot be empty.';
    }
    if ($form_values['message'] == '') {
      $form_errors['message'] = 'Field <em>Message</em> cannot be empty.';
    }
  }

  /**
   * @param array $form
   * @param array $form_values
   */
  public function mail_form_submit(array &$form, array $form_values) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;

    $mail = array(
      'to' => $form_values['to'],
      'from' => $form_values['from'],
      'subject' => $form_values['subject'],
      'body' => $form_values['message'],
      'action_function' => array($this, 'action_function'),
    );

    $b = $this->mail_send($mail);

    if ($b) {
      set_message('Mail sent.');
    } else {
      set_message('Mail failed.', 'error');
    }

    $form['#redirect'] = 'admin/mail';
  }

  /**
   * Show PHP info.
   *
   * @return string
   */
  public function phpinfo() {
    add_css($this->get_path() . 'css/phpinfo.css');

    ob_start();
    phpinfo();
    $out = ob_get_clean();

    $out = preg_replace('/^.*<body.*?>/is', '', $out);
    $out = preg_replace('/<\/body>.*/is', '', $out);
    $out = preg_replace('/;/is', '; ', $out);

    return '<div id="phpinfo">' . $out . '</div>';
  }

  /**
   * @return string
   */
  public function setup() {
    return get_module_form()->build('setup_form');
  }

  /**
   * @return string
   */
  public function setup_form() {
    $form['system'] = array(
      '#type' => 'fieldset',
      '#title' => 'System Settings',
      '#collapsible' => TRUE,
    );
    $form['system']['maintenance'] = array(
      '#type' => 'checkbox',
      '#title' => 'Maintenance',
      '#default_value' => variable_get('system_maintenance', TRUE),
    );
    $form['system']['maintainer_ip'] = array(
      '#type' => 'textfield',
      '#title' => 'Maintainer IP',
      '#default_value' => variable_get('system_maintainer_ip', ''),
      '#description' => 'This IP overrides maintenance mode. Current IP is: <em>' . $_SERVER['REMOTE_ADDR'] . '</em>',
    );

    $form['system']['debug'] = array(
      '#type' => 'checkbox',
      '#title' => 'Debug',
      '#default_value' => variable_get('system_debug', FALSE),
    );
    $form['system']['password_crypt'] = array(
      '#type' => 'checkbox',
      '#title' => 'Encrypt Password',
      '#default_value' => variable_get('system_password_crypt', TRUE),
    );
    $form['system']['password_minlength'] = array(
      '#type' => 'number',
      '#title' => 'Minimum Length Password',
      '#default_value' => variable_get('system_password_minlength', 8),
      '#size' => 4,
      '#attributes' => array(
        'min' => 4,
        'max' => 32,
      ),
    );
    $form['system']['password_maxlength'] = array(
      '#type' => 'number',
      '#title' => 'Maximum Length Password',
      '#default_value' => variable_get('system_password_maxlength', 32),
      '#size' => 4,
      '#attributes' => array(
        'min' => 4,
        'max' => 32,
      ),
    );

    $form['site'] = array(
      '#type' => 'fieldset',
      '#title' => 'Site Settings',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['site']['name'] = array(
      '#type' => 'textfield',
      '#title' => 'Name',
      '#default_value' => variable_get('system_sitename', 'ECMS'),
    );
    $form['site']['email'] = array(
      '#type' => 'textfield',
      '#title' => 'Email',
      '#default_value' => variable_get('system_email', 'mail@example.com'),
    );

    $themes = glob(BASE_DIR . 'core/themes/*', GLOB_ONLYDIR);
    $themes = array_map('basename', $themes);
    $themes = make_array_assoc($themes);
    $form['site']['theme'] = array(
      '#type' => 'select',
      '#title' => 'Theme',
      '#options' => $themes,
      '#default_value' => variable_get('system_theme', 'darkstar'),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Save',
    );

    return $form;
  }

  /**
   * @param array $form
   * @param array $form_values
   * @param array $form_errors
   */
  public function setup_form_validate(array $form, array $form_values, array &$form_errors) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;

    if ($form_values['maintainer_ip'] != '') {
      if (!filter_var($form_values['maintainer_ip'], FILTER_VALIDATE_IP)) {
        $form_errors['maintainer_ip'] = 'Maintainer IP must be a valid IP or empty.';
      }
    }
  }

  /**
   * @param array $form
   * @param array $form_values
   */
  public function setup_form_submit(array $form, array $form_values) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;

    variable_set('system_maintenance',   $form_values['maintenance']);
    variable_set('system_maintainer_ip', $form_values['maintainer_ip']);
    variable_set('system_debug',         $form_values['debug']);

    variable_set('system_password_crypt',     $form_values['password_crypt']);
    variable_set('system_password_minlength', $form_values['password_minlength']);
    variable_set('system_password_maxlength', $form_values['password_maxlength']);

    variable_set('system_sitename', $form_values['name']);
    variable_set('system_email',    $form_values['email']);
    variable_set('system_theme',    $form_values['theme']);

    set_message('Settings saved.');
  }

  /**
   * Shows a table list with all variables.
   *
   * @return string
   */
  public function variables() {
    library_load('stupidtable');

    $header = array(
      array('data' => 'Name',  'data-sort' => 'string'),
      array('data' => 'Value', 'data-sort' => 'string'),
      array('data' => 'Actions',      'colspan' => 1),
    );

    $count = 0;
    $rows = array();
    foreach ($this->variables as $name => $value) {
      $count++;
      if (is_bool($value)) {
        $value = ($value) ? 'TRUE' : 'FALSE';
      }
      if (is_array($value)) {
        $value = vardump($value, TRUE);
      }
      $rows[] = array(
        $name,
        $value,
        l('delete', 'admin/variable/delete/' . $name),
      );
    }

    $ra = array(
      'template' => 'table',
      'vars'     => array(
        'attributes' => array('class' => array('table', 'stupidtable', 'sticky')),
        'caption'    => $count . ' variables',
        'header'     => $header,
        'rows'       => $rows,
      ),
    );

    return get_theme()->theme_table($ra);
  }

  /**
   * @param string $name
   * @return array
   */
  public function variable_delete($name) {
    return get_module_form()->build('variable_delete_form', $name);
  }

  /**
   * @param array  $form
   * @param array  $form_values
   * @param array  $form_errors
   * @param string $name
   * @return array
   */
  public function variable_delete_form(array $form, array $form_values, array $form_errors, $name) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_errors;

    $form = array();

    $form['markup'] = array(
      '#value' => '<p>Are you sure you want to deletes variable <em>' . $name . '</em>?</p><p>This action cannot be undone!</p>',
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Delete',
      '#suffix' => ' ' . l('Cancel', 'admin/variables'),
    );

    return $form;
  }

  /**
   * @param array  $form
   * @param array  $form_values
   * @param string $name
   */
  public function variable_delete_form_submit(array &$form, array $form_values, $name) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form_values;

    variable_del($name);

    set_message('Variable <em>' . $name . '</em> deleted.');

    $form['#redirect'] = 'admin/variables';
  }
}
