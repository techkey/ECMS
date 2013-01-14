<?php
// module.class.php

namespace modules;

/**
 *
 */
abstract class module
{

  /**
   * Get relative path.
   *
   * @return string
   */
  public function get_path() {
//    return rtrim(BASE_PATH, '/') . '/' . str_replace('\\', '/', dirname(get_called_class()));
    return BASE_PATH . str_replace('\\', '/', dirname(str_replace('\\', '/', get_called_class()))) . '/';
  }

  /**
   * @return string
   */
  public function get_dir() {
    return __DIR__ . '/' . basename(str_replace('\\', '/', get_called_class())) . '/';
  }

}

