<?php

namespace modules\watchdog;

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
    return db_uninstall_schema($this->schema());
  }

  /**
   * @param string $type
   * @param string|array $data
   * @return bool
   */
  public function add($type, $data) {
    $return = db_insert('watchdog')
      ->fields(array(
        'type' => $type,
        'data' => serialize($data),
        'created' => time(),
      ))
      ->execute();

    return $return;
  }

  /* Hooks ********************************************************************/


  public function menu() {
    $menu['admin/watchdog'] = array(
      'title'            => 'Watchdog',
      'controller'       => 'watchdog::watchdog',
      'access_arguments' => 'admin',
      'menu_name'        => 'system',
    );

    return $menu;
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
      array('data' => 'Data'),
    );

    $rows = array();
    foreach ($results as $result) {
      $rows[] = array(
        $result->wdid,
        $result->type,
        date('H:i:s d-m-Y', $result->created),
        print_r(unserialize($result->data), TRUE),
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

  /* Public routes ************************************************************/

}