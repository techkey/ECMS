<?php
// autoloader.php

/**
 *
 */
class autoloader
{
  private $namespaces = array();

  /**
   *
   */
  public function __construct() {
    spl_autoload_register('\autoloader::loader');
  }

  /**
   * @param string $class
   */
  private function loader($class) {
//    if (strpos($class, 'Twig_') === 0) {
//      return;
//    }
//    if (strpos($class, 'Smarty_') === 0) {
//      return;
//    }

    $parts = explode('\\', $class);

    if (!in_array($parts[0], array('core', 'modules'))) {
      return;
    }

    foreach ($this->namespaces as /* $namespace => */ $directory) {
      for ($i = 1; $i <= count($parts); $i++) {
        $dir = $directory;
        for ($j = 0; $j < $i; $j++) {
          $dir .=  '/' . $parts[$j];
        }
//        var_dump($dir . '.class.php');
        if (file_exists($dir . '.class.php')) {
          /** @noinspection PhpIncludeInspection */
          include $dir . '.class.php';
          return;
        }
      }
    }
    echo("Class '$class' not found.<br>");
  }

  /**
   * @param string $namespace
   * @param string $directory
   */
  public function add($namespace, $directory) {
    $this->namespaces[$namespace] = realpath($directory);
//    var_dump($this->namespaces);
  }

}

/**
 * @return autoloader
 */
function get_loader() {
  static $loader = NULL;
  if ($loader) {
    return $loader;
  } else {
    $loader = new autoloader();
    return $loader;
  }
}
