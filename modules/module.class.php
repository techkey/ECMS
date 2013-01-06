<?php
// module.class.php

namespace modules;

/**
 *
 */
abstract class module
{

  /**
   * @return string
   */
  public function get_path() {
//    return rtrim(BASE_PATH, '/') . '/' . str_replace('\\', '/', dirname(get_called_class()));
    return str_replace('\\', '/', dirname(str_replace('\\', '/', get_called_class())));
  }

  /**
   * @return string
   */
  public function get_base_path() {
    return BASE_PATH . '/' . str_replace('\\', '/', dirname(str_replace('\\', '/', get_called_class())));
  }

  /**
   * @return string
   */
  public function get_dir() {
    return __DIR__ . '';
  }

}

