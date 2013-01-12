<?php
/**
 * @file log.class.php
 */

namespace modules\log;

use geoip;

/**
 *
 */
class log {

  /**
   * The schema definition.
   */
  public function schema() {
    $schema['log'] = array(
      'description' => 'The log table.',
      'fields' => array(
        'lid' => array(
          'type'     => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE
        ),
        'response_time' => array(
          'type'     => 'integer',
          'not null' => TRUE
        ),
        'entry' => array(
          'type'     => 'text',
          'not null' => TRUE
        ),
/*
        'request_time' => array(
          'type'     => 'integer',
          'not null' => TRUE
        ),
        'remote_addr' => array(
          'type'     => 'varchar',
          'length'   => 255,
          'not null' => TRUE
        ),
        'request_uri' => array(
          'type'     => 'text',
          'not null' => TRUE,
        ),
        'http_response_code' => array(
          'type'     => 'integer',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ),
        'http_user_agent' => array(
          'type'     => 'text',
          'not null' => TRUE,
        ),
        'geo_data' => array(
          'type'     => 'text',
          'not null' => TRUE,
        ),
//*/
      ),
//        'primary key' => array('lid'],
    );

    return $schema;
  }

  /**
   *
   */
  public function add($entry) {
    db_insert('log')
      ->fields(array(
        'response_time' => time(),
        'entry' => $entry,
      ))
      ->execute();
  }

  /* Hooks ********************************************************************/

  /**
   * @param array $array
   */
  public function shutdown(array $array) {
    if (!variable_get('log_enable', FALSE)) {
      return;
    }
    if ((get_user()->uid == 1) && ($array['status_code'] == 200)) {
      $excludes = variable_get('log_excludes', array());
      foreach ($excludes as $exclude) {
        $pattern = "#$exclude$#";
        if (preg_match($pattern, $_SERVER['REQUEST_URI'])) {
          return;
        }
      }
    }
//    vardump(headers_sent());
//    vardump(getallheaders());
//    vardump(headers_list());
//    vardump(apache_request_headers());
//    vardump(apache_response_headers());

    $log_entry  = $_SERVER['REMOTE_ADDR'] . ' - - ';
    $log_entry .= '[' . date('d/M/Y:H:i:s O') . '] ';
    $log_entry .= '"' . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'] . ' ' . $_SERVER['SERVER_PROTOCOL'] . '" ';
    $log_entry .= $array['status_code'] . ' ' . '-'/*$array['content_length']*/ . ' ';
    $log_entry .= '"' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '-') . '" ';
    $log_entry .= '"' . $_SERVER['HTTP_USER_AGENT'] . '"';

    $this->add($log_entry);
  }

  public function menu() {
    $menu['/admin/log'] = array(
      'title' => 'Log',
      'controller' => 'log:log',
      'access_arguments' => 'admin',
      'menu_name' => 'system',
    );
    $menu['/admin/log/settings'] = array(
      'title' => 'Log Settings',
      'controller' => 'log:settings',
      'access_arguments' => 'admin',
      'menu_name' => 'system',
    );

    $menu['/admin/log/download'] = array(
      'title' => 'Download',
      'controller' => 'log:download',
      'access_arguments' => 'admin',
      'type' => MENU_CALLBACK,
    );
    $menu['/admin/log/view/{lid}'] = array(
      'title' => 'View record',
      'controller' => 'log:view_record',
      'access_arguments' => 'admin',
      'type' => MENU_CALLBACK,
    );

    return $menu;
  }

  /* Private routes ***********************************************************/

  /**
   * @return string
   */
  public function settings() {
    $form['log'] = array(
      '#type' => 'fieldset',
      '#title' => 'Settings',
    );
    $form['log']['enable'] = array(
      '#type' => 'checkbox',
      '#title' => 'Enable',
      '#default_value' => variable_get('log_enable', FALSE),
    );
/*
    $form['log']['routes'] = array(
      '#type' => 'fieldset',
      '#title' => 'Include/Exclude Routes',
    );
    $form['log']['routes']['include'] = array(
      '#type' => 'radio',
      '#title' => 'Include',
      '#default_value' => variable_get('log_include', TRUE),
    );
    $form['log']['routes']['exclude'] = array(
      '#type' => 'radio',
      '#title' => 'Exclude',
      '#default_value' => !variable_get('log_include', TRUE),
    );
//*/
    $excludes = variable_get('log_excludes', array());
    $excludes = implode("\n", $excludes);
    $excludes = str_replace('(.*?)', '*', $excludes);
    $form['log']['excludes'] = array(
      '#type' => 'textarea',
      '#title' => 'Exclude Routes',
      '#default_value' => $excludes,
      '#description' => 'One per line, wildcard * allowed.',
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Save',
    );

    return get_module_form()->build($form);
  }

  /**
   * @param array $form
   * @param array $form_values
   */
  public function settings_submit(array $form, array $form_values) {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $tmp = $form;

    variable_set('log_enable', $form_values['enable']);

    $excludes = str_replace(' ', '', $form_values['excludes']);
    $excludes = str_replace("\r\n", "\n", $excludes);
    $excludes = str_replace('*', '(.*?)', $excludes);
    $excludes = explode("\n", $excludes);
    variable_set('log_excludes', $excludes);

    set_message('Log settings saved.');
  }

  /**
   * @return string
   */
  public function log() {
    $out = '';

    $out .= l('download log', '/admin/log/download');

    $count = db_query('SELECT COUNT(*) FROM log')->fetchColumn();
    /** @var \LOG[] $results */
    $results = db_select('log')
      ->field('*')
      ->pager(50)
//      ->orderby('response_time', 'DESC')
      ->execute()
      ->fetchAll(\PDO::FETCH_OBJ);

    $header = array(
      'LID',
      'Response Time',
      'Entry',
      'Actions',
    );

    $rows = array();
    foreach ($results as $result) {
      $rows[] = array(
        $result->lid,
        array('data' => date('H:i:s d-m-Y', $result->response_time), 'style' => 'white-space: nowrap;'),
        $result->entry,
        l('view', '/admin/log/view/' . $result->lid),
      );
    }

    $ra = array(
      'template' => 'table',
      'vars' => array(
        'caption' => $count . ' records',
        'header' => $header,
        'rows' => $rows,
        'attributes' => array('class' => array('list-table', 'sticky')),
      ),
    );

    $out .= get_theme()->theme_table($ra) . get_theme()->theme_pager(array());

    return $out;
  }

  public function download() {
    $rows = db_select('log')
      ->field('entry')
      ->execute();

    $log = '';
    /** @noinspection PhpAssignmentInConditionInspection */
    while ($entry = $rows->fetchColumn()) {
      $log .= $entry . "\n";
    }
//    header('Content-Type: text/plain');
    header('Content-Type: application/gzip');
//    header('Content-Encoding: gzip');
    header('Content-Disposition: attachment; filename="log.gz"');
    echo gzencode($log, 9);
    exit;
  }

  /**
   * @param int $lid
   * @return null|string
   */
  public function view_record($lid) {
    /** @var \LOG $record */
    $record = db_select('log')
      ->field('*')
      ->condition('lid', $lid)
      ->execute()
      ->fetch(\PDO::FETCH_OBJ);

    $out = vardump($record, TRUE) . '<p>' . l('back', '/admin/log') . '</p>';

    return $out . phpversion();
  }

  /* Public routes ************************************************************/

}