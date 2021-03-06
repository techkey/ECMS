<?php
// module.class.php

namespace core\modules;

/**
 *
 */
abstract class core_module
{

  /**
   * @return string
   */
  public function get_path() {
    return BASE_PATH . str_replace('\\', '/', dirname(str_replace('\\', '/', get_called_class()))) . '/';
  }

  /**
   * @return string
   */
  public function get_dir() {
    return BASE_DIR . str_replace('\\', '/', dirname(str_replace('\\', '/', get_called_class()))) . '/';
  }

}
