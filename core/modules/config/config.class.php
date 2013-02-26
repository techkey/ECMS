<?php
// config.class.php

namespace core\modules\config;


/**
 * Static class to handle configuration.
 */
class config
{
  private static $config = array();

  public static function load() {
    $name = BASE_DIR . 'config/config.ini';
    if (file_exists($name)) {
      self::$config = parse_ini_file($name, TRUE);
    }
  }

  /**
   * @return array
   */
  public static function get_all_values() {
    return self::$config;
  }

  /**
   * @param string $name
   * @param mixed $default
   * @return mixed
   */
  public static function get_value($name, $default = NULL) {
    if (strpos($name, '.') === FALSE) {
      return (isset(self::$config[$name])) ? self::$config[$name] : $default;
    }

    $parts = explode('.', $name);
    $config = &self::$config;
    for ($i = 0; $i < count($parts); $i++) {
      if (!isset($config[$parts[$i]])) {
        return $default;
      }
      $config = &$config[$parts[$i]];
    }
    return $config;

//    $return = NULL; // To keep PHPStorm happy :)
//    $var = "self::\$config['" . str_replace(':', "']['", $name) . "']";
//    $code = "\$return = (isset($var)) ? $var : \$default;";
//    eval($code);
//    return $return;
  }
}
