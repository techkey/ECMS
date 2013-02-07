<?php
/**
 * @file phpmailer.class.php
 */

namespace modules\phpmailer;

/**
 * Class phpmailer
 *
 * @package modules\phpmailer
 */
class phpmailer {

  /**
   * @return string
   */
  public function setup() {
    return get_module_form()->build('setup_form');
  }

  /**
   * @return array
   */
  public function setup_form() {

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
    );
    $form['mail']['smtp']['password'] = array(
      '#type' => 'password',
      '#title' => 'Password',
      '#default_value' => variable_get('system_mail_password', ''),
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
  public function setup_form_submit(array $form, array $form_values) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;

    variable_set('system_mail_mailer',     $form_values['mailer']);
    variable_set('system_mail_smtpauth',   $form_values['smtpauth']);
    variable_set('system_mail_smtpsecure', $form_values['smtpsecure']);
    variable_set('system_mail_port',       $form_values['port']);
    variable_set('system_mail_host',       $form_values['host']);
    variable_set('system_mail_username',   $form_values['username']);
    variable_set('system_mail_password',   $form_values['password']);

    set_message('Settings saved.');
  }


}
