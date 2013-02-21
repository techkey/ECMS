<?php

namespace core\modules\session;

use SessionHandlerInterface;

/**
 *
 */
class mysession54 implements SessionHandlerInterface {

  /**
   * @param string $save_path
   * @param string $session_id
   * @return bool
   */
  public function open($save_path, $session_id) {
    $return = TRUE;

    return $return;
  }

  /**
   * @return bool
   */
  public function close() {
    // Nothing to do.
    return TRUE;
  }

  /**
   * @param string $session_id
   * @param string $session_data
   * @return bool
   */
  public function write($session_id, $session_data) {
    if (!db_select('session')->field('sid')->condition('sid', $session_id)->execute()->fetchColumn()) {
      $return = (bool)db_insert('session')
        ->fields(array(
          'sid' => $session_id,
          'created' => time(),
          'data' => $session_data,
        ))
        ->execute();
    } else {
      $return = (bool)db_update('session')
        ->fields(array('data' => $session_data))
        ->condition('sid', $session_id)
        ->execute();
    }
    return $return;
  }

  /**
   * @param string $session_id
   * @return string
   */
  public function read($session_id) {
    $return = (string)db_select('session')
      ->field('data')
      ->condition('sid', $session_id)
      ->execute()
      ->fetchColumn();

    return $return;
  }

  /**
   * @param string $session_id
   * @return bool
   */
  public function destroy($session_id) {
    db_delete('session')
      ->condition('sid', $session_id)
      ->execute();

    return TRUE;
  }

  /**
   * @param int $maxlifetime
   * @return bool
   */
  public function gc($maxlifetime) {
    db_delete('session')->condition('created', time() - $maxlifetime, '<')->execute();

    return TRUE;
  }
}

