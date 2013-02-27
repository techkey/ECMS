<?php

namespace core\modules\watchdog;

/**
 *
 */
class watchdog {

  /**
   * The schema definition.
   */
  public function schema() {
    $schema['watchdog'] = array(
      'description' => 'The watchdog table.',
      'fields'      => array(
        'wdid'        => array(
          'type'     => 'serial',
        ),
        'count'    => array(
          'type'     => 'integer',
          'unsigned' => TRUE,
          'not null' => TRUE
        ),
        'created'    => array(
          'type'     => 'integer',
          'unsigned' => TRUE,
          'not null' => TRUE
        ),
        'type'         => array(
          'type'     => 'varchar',
          'length'   => 255,
          'not null' => TRUE
        ),
        'data' => array(
          'type'     => 'text',
          'not null' => TRUE,
        ),
      ),
      'primary key' => array('wdid'),
    );

    return $schema;
  }

  /**
   * Implements hook install().
   *
   * @return bool
   */
  private function install() {
    return db_install_schema($this->schema());
  }

  /**
   * Implements hook uninstall().
   *
   * @return bool
   */
  private function uninstall() {
    variable_del('watchdog_notify');
    return db_uninstall_schema($this->schema());
  }

  /**
   * Add a entry into the watchdog table.
   *
   * @param string|array $data
   * @param string       $type The type, can be 'error', 'warning' or 'status'.
   * @return bool
   */
  public function add($data, $type) {
    $data = serialize($data);

    /** @var \WATCHDOG $wd */
    $wd = db_select('watchdog')
      ->fields(array('wdid', 'count'))
      ->condition('data', $data)
      ->condition('type', $type)
      ->execute()
      ->fetchObject();

    if ($wd) {
      $return = db_update('watchdog')
        ->fields(array(
          'count' => $wd->count + 1,
          'created' => time(),
        ))
        ->condition('wdid', $wd->wdid)
        ->execute();
    } else {
      $return = db_insert('watchdog')
        ->fields(array(
          'created' => time(),
          'count' => 1,
          'type' => $type,
          'data' => $data,
        ))
        ->execute();
    }

    return (bool)$return;
  }

  /**
   * @param int $wdid
   * @return string
   */
  public function delete($wdid) {
    db_delete('watchdog')->condition('wdid', $wdid)->execute();
    set_message("Watchdog entry $wdid is deleted.");
    go_to('admin/watchdog');
  }

  /* Hooks ********************************************************************/

  /**
   * Implements hook menu().
   *
   * @return mixed
   */
  public function menu() {
    $menu['admin/watchdog'] = array(
      'title'            => 'Watchdog',
      'controller'       => 'watchdog::watchdog',
      'access_arguments' => 'admin',
      'menu_name'        => 'system',
    );
    $menu['admin/watchdog/settings'] = array(
      'title'            => 'Watchdog Settings',
      'controller'       => 'watchdog::settings',
      'access_arguments' => 'admin',
      'menu_name'        => 'system',
    );

    $menu['admin/watchdog/delete/{wdid}'] = array(
      'title'            => 'Watchdog delete entry',
      'controller'       => 'watchdog::delete',
      'access_arguments' => 'admin',
      'type'             => MENU_CALLBACK,
    );

    return $menu;
  }

  /**
   * Implements hook page_alter().
   *
   * @param array $page
   */
  public function page_alter(array $page) {
    if (variable_get('watchdog_notify', TRUE)) {
      $count = db_select('watchdog')
        ->field('COUNT(*)')
        ->condition('type', 'status', '!=')
        ->execute()
        ->fetchColumn();

      if ($count) {
        set_message("There are $count watchdog error/warning messages.", 'warning');
      }
    }
  }

  /* Private routes ***********************************************************/

  /**
   * @return string
   */
  public function watchdog() {
    library_load('stupidtable');

    /** @var \WATCHDOG[] $results */
    $results = db_select('watchdog')
      ->field('*')
      ->orderby('created', 'DESC')
      ->execute()
      ->fetchAll(\PDO::FETCH_OBJ);

    $header = array(
      array('data' => 'wdid',    'data-sort' => 'integer'),
      array('data' => 'Type',    'data-sort' => 'string'),
      array('data' => 'Created', 'data-sort' => 'integer'),
      array('data' => 'Count',   'data-sort' => 'integer'),
      array('data' => 'Data'),
      array('data' => 'Actions'),
    );

    $rows = array();
    foreach ($results as $result) {
      $rows[] = array(
        'data' => array(
          $result->wdid,
          $result->type,
          array(
            'data' => date('Y-m-d H:i:s', $result->created),
            'style' => 'white-space: nowrap;',
          ),
          $result->count,
          array(
            'data' => "<div class='{$result->type}-messages' style='border: none; line-height: 20px'>" . print_r(unserialize($result->data), TRUE) . '</div>',
            'style' => 'padding: 0;',
          ),
          l('delete', 'admin/watchdog/delete/' . $result->wdid),
        ),
        'style' => '',
      );
    }

    $ra = array(
      'template' => 'table',
      'vars' => array(
        'caption' => count($results) . ' records',
        'header' => $header,
        'rows' => $rows,
        'attributes' => array('class' => array('stupidtable', 'table', 'sticky')),
      ),
    );

    return get_theme()->theme_table($ra);
  }

  /**
   * @return string
   */
  public function settings() {
    return get_module_form()->build('settings_form');
  }

  public function settings_form() {
    $form['notify'] = array(
      '#type'          => 'checkbox',
      '#title'         => 'Notify',
      '#default_value' => variable_get('watchdog_notify', TRUE),
    );
    $form['submit'] = array(
      '#type'  => 'submit',
      '#value' => 'Save',
    );

    return $form;
  }

  /**
   * @param array $form
   * @param array $form_values
   */
  public function settings_form_submit(array &$form, array $form_values) {
    variable_set('watchdog_notify', $form_values['notify']);
    set_message('Watchdog settings are saved.');
  }

  /* Public routes ************************************************************/

}