<?php
// twig.class.php

namespace core\engines\twig;

use core\modules\config\config;

/**
 *
 */
class twig
{
  private $twig;

  /**
   *
   */
  public function __construct() {
    require_once __DIR__ . '/Twig/Autoloader.php';
    \Twig_Autoloader::register();
    $loader = new \Twig_Loader_Filesystem([BASE_DIR . '/core/themes/sunshine/templates', BASE_DIR]);
    $this->twig = new \Twig_Environment($loader, [
      'cache' => __DIR__ . '/cache',
      'debug' => variable_get('system_debug', FALSE),
      'strict_variables' => TRUE,
      'autoescape' => FALSE,
      'auto_reload' => TRUE,
    ]);
    if (variable_get('system_debug', FALSE)) {
      $this->twig->addExtension(new \Twig_Extension_Debug());
    }
  }

  /**
   * @param string $name
   * @param array $context
   * @return string
   */
  public function render($name, $context = []) {
    return $this->twig->render($name . '.twig', $context);
  }

  /**
   * @param string $path
   */
  public function add_template_dir($path) {
    /** @var \Twig_Loader_Filesystem $loader */
    $loader = $this->twig->getLoader();
    $loader->addPath($path);
  }

  public function clear_cache() {
    $this->twig->clearCacheFiles();
  }
}
