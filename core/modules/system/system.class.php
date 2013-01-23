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
    if (!db_table_exists('variable')) {
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
   * Hook schema().
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
   *    [from]
   *    [to]
   *    [subject]
   *    [body]
   *    [html]      bool - default is TRUE
   *
   * @param array $mail
   * @return bool
   */
  private function mail_send(array $mail) {
    $mail += array(
      'html' => TRUE,
      'action_function' => NULL,
    );

    library_load('phpmailer');
    $mailer = new \PHPMailer();

    if ($mail['action_function']) {
      $mailer->action_function = $mail['action_function'];
    }

//    $mailer->SMTPDebug = TRUE;
    $mailer->Mailer = variable_get('system_mail_mailer', '');
    if ($mailer->Mailer == 'smtp') {
      $mailer->SMTPAuth = variable_get('system_mail_smtpauth', FALSE);
      $mailer->SMTPSecure = variable_get('system_mail_smtpsecure', FALSE);
      $mailer->Port = variable_get('system_mail_port', '');
      $mailer->Host = variable_get('system_mail_host', '');
      $mailer->Username = variable_get('system_mail_username', '');
      $mailer->Password = variable_get('system_mail_password', '');
    }

    $mailer->From = $mail['from'];
    $mailer->AddAddress($mail['to']);
    $mailer->Subject = $mail['subject'];
    $mailer->Body = $mail['body'];

    $mailer->IsHTML($mail['html']);

    $b = $mailer->Send();

    return $b;
  }

  /**
   * @param array $mail
   * @return bool
   */
  public function mail_password_reset(array $mail) {
    $body = file_get_contents($this->get_dir() . 'mails/password_reset.html');
    $body = str_replace('[PASSWORD]', generate_password(), $body);

    $from = variable_get('system_email');

    $mail += array(
      'from' => $from,
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
   * Hook init();
   */
  public function init() {
    library_load('jquery', -10);
    add_js(array(
      'basePath' => BASE_PATH,
    ), 'setting');
    add_js($this->get_path() . 'js/cms.js');
  }

  /**
   * Hook menu().
   *
   * @return array
   */
  public function menu() {
    $menu['admin/theme/clear_cache'] = array(
      'title' => 'Clear theme cache',
      'controller' => 'system:clear_cache',
      'access_arguments' => 'admin',
      'menu_name' => 'system',
    );
    $menu['admin/mail'] = array(
      'title' => 'Mail',
      'controller' => 'system:mail',
      'access_arguments' => 'admin',
      'menu_name' => 'system',
    );
    $menu['admin/phpinfo'] = array(
      'title' => 'PHP Info',
      'controller' => 'system:phpinfo',
      'access_arguments' => 'admin',
      'menu_name' => 'system',
    );
    $menu['admin/setup'] = array(
      'title' => 'Setup',
      'controller' => 'system:setup',
      'access_arguments' => 'admin',
      'menu_name' => 'system',
    );

    $menu['admin/variables'] = array(
      'title' => 'Variables',
      'controller' => 'system:variables',
      'access_arguments' => 'admin',
      'menu_name' => 'system',
    );
    $menu['admin/variable/delete/{name}'] = array(
      'title' => 'Delete variable',
      'controller' => 'system:variable_delete',
      'access_arguments' => 'admin',
      'type' => MENU_CALLBACK,
    );

    return $menu;
  }

  /**
   * Hook block().
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
   * Hook pre_render().
   *
   * @return array
   */
  public function pre_render2() {
    $render_array[] = array(
      'region' => 'footer_bottom',
      'content' => '&copy;2012 3dflat, all rights reserved.',
    );

    return $render_array;
  }

  /* Pivate routes ************************************************************/

  public function clear_cache() {
    get_theme()->clear_cache();
    set_message('Theme cache cleared.');
  }

  /**
   * @return string
   */
  public function mail() {
    $out = '';

    $form['to'] = array(
      '#type' => 'textfield',
      '#title' => 'To',
      '#required' => TRUE,
    );
    $form['from'] = array(
      '#type' => 'textfield',
      '#title' => 'From',
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
    );

    $out .= get_module_form()->build($form);

    return $out;
  }

  /**
   * @param array $form
   * @param array $form_values
   */
  public function mail_submit(array &$form, array $form_values) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;
    $mail = array(
      'to' => $form_values['to'],
      'from' => $form_values['from'],
      'subject' => $form_values['subject'],
      'body' => $form_values['message'],
      'action_function' => array($this, 'action_function'),
    );

    ini_set('sendmail_path', '/usr/sbin/sendmail -i -f admin@3dflat.tk');

    $headers = 'From: admin@3dflat.tk' . "\r\n" .
      'Reply-To: admin@3dflat.tk' . "\r\n" .
      'Sender: admin@3dflat.tk' . "\r\n" .
      'Return-Path: admin@3dflat.tk' . "\r\n" .
      'X-Mailer: PHP/' . phpversion();

    $b = mail($mail['to'], $mail['subject'], $mail['body'], $headers);

//    $b = $this->mail_send($mail);

    if ($b) {
      set_message('Mail sent.');
    } else {
      set_message('Mail failed.', 'error');
    }

    $form['#redirect'] = 'admin/mail';
  }

  public function action_function() {
    set_message(vardump(func_get_args(), TRUE));
  }

  /**
   * Show PHP info.
   *
   * @return string
   */
  public function phpinfo() {
//    ob_start();
//    phpinfo();
//    $out = ob_get_clean();
    $js = <<<'JS'
$(function () {
  var $op = $('object#phpinfo');
//  var doc = $op.get(0).document;
//  var h = $('html', doc).height();
  $op.css({
    width: 940,
    height: 24300
  });
});
JS;

    add_js($js, 'inline');

    $out = '<object id="phpinfo" data="/test2/info.php"></object>';

    return $out;
  }

  /**
   * @return string
   */
  public function setup() {
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
      '#description' => 'This IP overrides maintanance mode. Current IP is: <em>' . $_SERVER['REMOTE_ADDR'] . '</em>',
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

    $form['mail'] = array(
      '#type' => 'fieldset',
      '#title' => 'Mail Settings',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['mail']['mailer'] = array(
      '#type' => 'select',
      '#title' => 'Mailer',
      '#options' => make_array_assoc(array('mail', 'sendmail', 'smtp')),
      '#default_value' => variable_get('system_mail_mailer', 'mail'),
    );

    $form['mail']['smtp'] = array(
      '#type' => 'fieldset',
      '#title' => 'SMTP Settings',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['mail']['smtp']['smtpauth'] = array(
      '#type' => 'checkbox',
      '#title' => 'SMTP Authentication',
      '#default_value' => variable_get('system_mail_smtpauth', FALSE),
    );
    $form['mail']['smtp']['smtpsecure'] = array(
      '#type' => 'select',
      '#title' => 'Connection Security',
      '#options' => make_array_assoc(array('', 'tls', 'ssl')),
      '#default_value' => variable_get('system_mail_smtpsecure', ''),
    );
    $form['mail']['smtp']['port'] = array(
      '#type' => 'number',
      '#title' => 'Port',
      '#default_value' => variable_get('system_mail_port', 25),
      '#attributes' => array(
        'min' => 25,
        'max' => 65535,
      ),
    );
    $form['mail']['smtp']['host'] = array(
      '#type' => 'textfield',
      '#title' => 'Host',
      '#default_value' => variable_get('system_mail_host', ''),
      '#attributes' => array('autocomplete' => 'on'),
    );
    $form['mail']['smtp']['username'] = array(
      '#type' => 'textfield',
      '#title' => 'Username',
      '#default_value' => variable_get('system_mail_username', ''),
//      '#attributes' => array('autocomplete' => 'on'),
    );
    $form['mail']['smtp']['password'] = array(
      '#type' => 'password',
      '#title' => 'Password',
      '#default_value' => variable_get('system_mail_password', ''),
    );

    $form['#attributes'] = array('autocomplete' => 'off');

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Save',
    );

    return get_module_form()->build($form);
  }

  /**
   * @param array $form
   * @param array $form_values
   * @param array $form_errors
   */
  public function setup_validate(array $form, array $form_values, array &$form_errors) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;

    if ($form_values['maintainer_ip'] != '') {
      if (!filter_var($form_values['maintainer_ip'], FILTER_VALIDATE_IP)) {
        $form_errors['maintainer_ip'] = 'Maintainer IP must be a valid IP or empty.';
      }
    }

    if (($form_values['mailer'] == 'smtp') && $form_values['smtpauth']) {
      $options = array(
        'options' => array(
          'min_range' => 25,
          'max_range' => 65535,
        ),
      );
      if (!filter_var($form_values['port'], FILTER_VALIDATE_INT, $options)) {
        $form_errors['port'] = 'Port number must between 25 and 65535.';
      }
      if ($form_values['host'] == '') {
        $form_errors['host'] = 'Field host is required.';
      }
      if ($form_values['username'] == '') {
        $form_errors['username'] = 'Field username is required.';
      }
      if ($form_values['password'] == '') {
        $form_errors['password'] = 'Field password is required.';
      }
    }
  }

  /**
   * @param array $form
   * @param array $form_values
   */
  public function setup_submit(array $form, array $form_values) {
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

    variable_set('system_mail_mailer',     $form_values['mailer']);
    variable_set('system_mail_smtpauth',   $form_values['smtpauth']);
    variable_set('system_mail_smtpsecure', $form_values['smtpsecure']);
    variable_set('system_mail_port',       $form_values['port']);
    variable_set('system_mail_host',       $form_values['host']);
    variable_set('system_mail_username',   $form_values['username']);
    variable_set('system_mail_password',   $form_values['password']);

    set_message('Settings saved.');
  }

  /**
   * @return string
   */
  public function variables() {
    library_load('stupidtable');
    add_js('$(function(){$(".stupidtable").stupidtable()});', 'inline');

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
        'caption'    => $count . ' variables',
        'attributes' => array('class' => array('stupidtable', 'sticky')),
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
    $form['name'] = array(
      '#type' => 'value',
      '#value' => $name,
    );
    $form['markup'] = array(
      '#value' => '<p>This action cannot be undone!</p>',
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Delete',
      '#suffix' => ' ' . l('Cancel', 'admin/variables'),
    );

    return array(
      'page_title' => "Delete variable <em>$name</em>?",
      'content' => get_module_form()->build($form),
    );
  }

  /**
   * @param array $form
   * @param array $form_values
   */
  public function variable_delete_submit(array &$form, array $form_values) {
    variable_del($form_values['name']);

    set_message('Variable deleted.');

    $form['#redirect'] = 'admin/variables';
  }
}
