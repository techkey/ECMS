<?php
/*
 * module.class.php
*/

namespace core;

/**
 * A base class that user modules can extend to get extra functionality.
 */
abstract class module {

  /**
   * Get relative path.
   *
   * NOTE: A slash is appended.
   *
   * @return string
   */
  public function get_path() {
//    return rtrim(BASE_PATH, '/') . '/' . str_replace('\\', '/', dirname(get_called_class()));
    return BASE_PATH . str_replace('\\', '/', dirname(str_replace('\\', '/', get_called_class()))) . '/';
  }

  /**
   * Get the module directory.
   *
   * NOTE: A slash is appended.
   *
   * @return string Returns the module directory.
   */
  public function get_dir() {
    return BASE_DIR . '/modules/' . basename(str_replace('\\', '/', get_called_class())) . '/';
  }

}

